<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderService;
use App\Services\New\OrderStatusHistoryService;
use App\Services\New\ProductRetailService;
use App\Traits\PdfGenerator;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CustomerOrderActionController extends Controller
{
    use PdfGenerator;

    private const ACCOUNT_PAYMENT_VIEW = 'web-views.users-profile.account-payment';

    private const INVOICE_RELATIONS = ['seller', 'latestEditHistory', 'shipping', 'details.product'];

    private const INVOICE_FILE_PREFIX = 'order_invoice_';

    private const PDF_TYPE_INVOICE = 'invoice';

    private const PDF_REQUEST_FROM_WEB = 'web';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderStatusHistoryService $orderStatusHistoryService,
        private readonly ProductRetailService $productRetailService,
    ) {}

    public function payment(): View|RedirectResponse
    {
        if (! auth('customer')->check()) {
            return redirect()->route('home');
        }

        return view(self::ACCOUNT_PAYMENT_VIEW);
    }

    public function invoice(int|string $id): ?RedirectResponse
    {
        $ownerFilters = $this->getOwnerFilters(includeGuest: true);
        $order = $ownerFilters === null
            ? null
            : $this->orderService->findBy(params: ['id' => $id, ...$ownerFilters], relations: self::INVOICE_RELATIONS);

        if ($order === null) {
            return $this->invalidOrderResponse();
        }

        $invoiceView = view(themeViewName(key: 'order_invoice'), [
            'order' => $order,
            'invoiceSettings' => getWebConfig(name: 'invoice_settings'),
        ]);

        $this->generatePdf(
            view: $invoiceView,
            filePrefix: self::INVOICE_FILE_PREFIX,
            filePostfix: $order['id'],
            pdfType: self::PDF_TYPE_INVOICE,
            requestFrom: self::PDF_REQUEST_FROM_WEB,
        );

        return null;
    }

    public function cancel(int|string $id): RedirectResponse
    {
        $ownerFilters = $this->getOwnerFilters(includeGuest: false);
        $order = $ownerFilters === null
            ? null
            : $this->orderService->findBy(params: ['id' => $id, ...$ownerFilters]);

        if ($order === null) {
            return $this->invalidOrderResponse();
        }

        if ($order['payment_method'] == OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY && $order['order_status'] == OrderService::STATUS_PENDING) {
            $this->restoreStockForCanceledOrder(orderId: $order['id']);
            $this->orderStatusHistoryService->create(data: [
                'order_id' => $order['id'],
                'user_id' => auth('customer')->id(),
                'user_type' => OrderStatusHistoryService::USER_TYPE_CUSTOMER,
                'status' => OrderService::STATUS_CANCELED,
                'cause' => null,
            ]);
            $this->orderStatusHistoryService->deleteOutdatedHistory(orderId: $order['id'], orderStatus: OrderService::STATUS_CANCELED);
            $this->orderService->updateWhere(params: ['id' => $order['id']], data: ['order_status' => OrderService::STATUS_CANCELED]);
            ToastMagic::success(translate('Canceled successfully'));
        } elseif ($order['payment_method'] == OrderService::PAYMENT_METHOD_OFFLINE) {
            ToastMagic::error(translate('The order status cannot be updated as it is an offline payment'));
        } else {
            ToastMagic::error(translate('Status not changable now'));
        }

        return back();
    }

    private function restoreStockForCanceledOrder(int|string $orderId): void
    {
        $orderDetails = $this->orderDetailService->getList(dataLimit: 'all', filters: ['order_id' => $orderId]);

        foreach ($orderDetails as $orderDetail) {
            $stockColumns = $this->productRetailService->updateStockForOrderDetail(orderDetail: $orderDetail, status: OrderService::STATUS_CANCELED);

            if ($stockColumns) {
                $this->orderDetailService->updateWhere(params: ['id' => $orderDetail['id']], data: $stockColumns);
            }
        }
    }

    private function getOwnerFilters(bool $includeGuest): ?array
    {
        if (auth('customer')->check()) {
            return ['customer_id' => auth('customer')->id(), 'is_guest' => '0'];
        }

        if ($includeGuest && session('guest_id')) {
            return ['customer_id' => session('guest_id'), 'is_guest' => 1];
        }

        return null;
    }

    private function invalidOrderResponse(): RedirectResponse
    {
        ToastMagic::warning(translate('Invalid order'));

        return back();
    }
}
