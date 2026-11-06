<?php

namespace App\Http\Controllers\Admin\Order;

use App\Enums\ExportFileNames\Admin\RefundRequest as RefundRequestExportFile;
use App\Events\RefundEvent;
use App\Exports\RefundRequestExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Order\RefundStatusUpdateRequest;
use App\Services\New\AdminWalletService;
use App\Services\New\CustomerService;
use App\Services\New\LoyaltyPointTransactionService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderDetailsRewardsService;
use App\Services\New\OrderService;
use App\Services\New\RefundRequestService;
use App\Services\New\RefundStatusService;
use App\Services\New\RefundTransactionService;
use App\Services\New\VendorWalletService;
use App\Services\New\WalletTransactionService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RefundController extends BaseController
{
    public function __construct(
        private readonly RefundRequestService $refundRequestService,
        private readonly CustomerService $customerService,
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly AdminWalletService $adminWalletService,
        private readonly VendorWalletService $vendorWalletService,
        private readonly RefundStatusService $refundStatusService,
        private readonly RefundTransactionService $refundTransactionService,
        private readonly LoyaltyPointTransactionService $loyaltyPointTransactionService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
        private readonly WalletTransactionService $walletTransactionService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $refundList = $this->refundRequestService->getList(
            searchValue: $request['searchValue'],
            dataLimit: getWebConfig(name: 'pagination_limit'),
            relations: ['order', 'order.seller', 'order.deliveryMan', 'product', 'customer', 'orderDetails'],
            filters: $this->getListFilters(request: $request, status: $type),
            sortBy: RefundRequestService::SORT_NEWEST_ID,
        );

        return view('admin-views.refund.list', [
            'refundList' => $refundList,
            'status' => $type,
            'walletStatus' => getWebConfig(name: 'wallet_status'),
            'walletAddRefund' => getWebConfig(name: 'wallet_add_refund'),
            'orderedProductNames' => $this->refundRequestService->getOrderedProductNames(refunds: $refundList->items()),
        ]);
    }

    public function getDetailsView(string|int $id): View|RedirectResponse
    {
        $refund = $this->refundRequestService->find(
            id: $id,
            relations: ['order.seller.shop', 'order.deliveryMan', 'product', 'orderDetails', 'refundStatus'],
        );

        if ($refund === null || $refund->orderDetails === null || $refund->order === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $order = $refund->order;
        $refundDetails = $this->orderDetailService->getRefundDetails(orderDetail: $refund->orderDetails, order: $order);

        return view('admin-views.refund.details', [
            'refund' => $refund,
            'order' => $order,
            'subtotal' => $refundDetails['sub_total'],
            'couponDiscount' => $refundDetails['coupon_discount'],
            'referralDiscount' => $refundDetails['referral_discount'],
            'refundAmount' => $refund->amount,
            'walletStatus' => getWebConfig(name: 'wallet_status'),
            'walletAddRefund' => getWebConfig(name: 'wallet_add_refund'),
            'orderedProductName' => $this->refundRequestService->getOrderedProductDetails(refund: $refund)['name'] ?? null,
            'digitalFileDownloadPath' => $this->refundRequestService->getDigitalFileDownloadPath(refund: $refund),
            'vendorContact' => $this->getVendorContact(order: $order),
        ]);
    }

    public function updateRefundStatus(RefundStatusUpdateRequest $request): JsonResponse
    {
        $refund = $this->refundRequestService->find(id: $request['id']);

        if ($refund === null) {
            return response()->json(['error' => translate('Not found')]);
        }

        if ($this->refundRequestService->isRefunded(refund: $refund)) {
            return response()->json(['error' => translate('When refund status refunded').','.translate('Then you can\'t change refund status').'.']);
        }

        $customer = $this->customerService->find(id: $refund->customer_id);

        if ($customer === null) {
            return response()->json(['error' => translate('This account has been deleted you cannot modify the status').'.']);
        }

        $status = $request['refund_status'];
        $loyaltyPoint = $this->orderDetailsRewardsService->getLoyaltyPointForOrderDetail(orderDetailId: $refund->order_details_id);

        if ($this->refundRequestService->hasInsufficientLoyaltyPoint(customerLoyaltyPoint: $customer->loyalty_point, refundLoyaltyPoint: $loyaltyPoint, status: $status)) {
            return response()->json(['error' => translate('Customer does not have sufficient loyalty points to take a refund for this order').'.']);
        }

        $order = $this->orderService->find(id: $refund->order_id);

        if ($order === null) {
            return response()->json(['error' => translate('Not found')]);
        }

        $orderDetail = DB::transaction(function () use ($request, $refund, $order, $status, $loyaltyPoint): ?Model {
            if ($status === RefundRequestService::STATUS_REFUNDED) {
                $this->payOutRefund(refund: $refund, order: $order, paymentMethod: $request['payment_method'], loyaltyPoint: $loyaltyPoint);
            }

            $orderDetail = $this->orderDetailService->update(
                id: $refund->order_details_id,
                data: ['refund_request' => $this->refundRequestService->getOrderDetailRefundFlagIncludingRefunded(status: $status)],
            );

            $this->refundRequestService->updateStatusByAdmin(
                refund: $refund,
                status: $status,
                approvedNote: $request['approved_note'],
                rejectedNote: $request['rejected_note'],
                paymentInfo: $request['payment_info'],
            );

            $this->createStatusLogs(refund: $refund, status: $status, request: $request);

            return $orderDetail;
        });

        event(new RefundEvent(status: $status, order: $order, refund: $refund, orderDetails: $orderDetail));

        return response()->json(['message' => translate('Refund status updated').'.']);
    }

    public function exportList(Request $request, string $status): BinaryFileResponse
    {
        $refundList = $this->refundRequestService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: ['order.seller.shop', 'order.deliveryMan', 'orderDetails', 'customer'],
            filters: $this->getListFilters(request: $request, status: $status),
            sortBy: RefundRequestService::SORT_NEWEST_ID,
        );

        return Excel::download(new RefundRequestExport([
            'data-from' => 'admin',
            'refundList' => $refundList,
            'rows' => $this->refundRequestService->getExportRows(refunds: $refundList),
            'search' => $request['searchValue'],
            'status' => $status,
            'from' => $request['from_date'],
            'to' => $request['to_date'],
            'filter_By' => $request->get('type', 'all'),
        ]), RefundRequestExportFile::EXPORT_XLSX);
    }

    private function getListFilters(Request $request, ?string $status): array
    {
        return array_filter([
            'status' => $status,
            RefundRequestService::FILTER_DATE_FROM => $request['from_date'],
            RefundRequestService::FILTER_DATE_TO => $request['to_date'],
            RefundRequestService::FILTER_ORDER_SELLER_IS => $request['type'],
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function payOutRefund(Model $refund, Model $order, ?string $paymentMethod, int $loyaltyPoint): void
    {
        if ($order->seller_is === RefundRequestService::SELLER_IS_ADMIN) {
            $this->adminWalletService->decrementInHouseEarning(adminId: $order->seller_id, amount: $refund->amount);
        } else {
            $this->vendorWalletService->decrementTotalEarning(sellerId: $order->seller_id, amount: $refund->amount);
        }

        $this->refundTransactionService->createForRefund(refund: $refund, order: $order, paymentMethod: $paymentMethod);

        if ($this->refundRequestService->shouldCreditCustomerWallet(paymentMethod: $paymentMethod)) {
            $this->creditCustomerWallet(
                customerId: $refund->customer_id,
                amount: (float) usdToDefaultCurrency(amount: $refund->amount),
                transactionType: WalletTransactionService::TRANSACTION_TYPE_ORDER_REFUND,
                reference: WalletTransactionService::TRANSACTION_TYPE_ORDER_REFUND,
            );
        }

        if ($loyaltyPoint > 0 && getWebConfig(name: 'loyalty_point_status') == 1) {
            $this->recordLoyaltyPointEntry(
                customerId: $refund->customer_id,
                amount: (float) $loyaltyPoint,
                transactionType: LoyaltyPointTransactionService::TRANSACTION_TYPE_REFUND_ORDER,
                reference: (string) $refund->order_id,
            );
        }
    }

    private function creditCustomerWallet(int|string $customerId, float $amount, string $transactionType, string $reference, ?string $paymentMethod = null): bool
    {
        $customer = getWebConfig(name: 'wallet_status') == 1 ? $this->customerService->find(id: $customerId) : null;

        if ($customer === null) {
            return false;
        }

        $walletTransaction = $this->walletTransactionService->createWalletEntry(
            customerId: $customer->id,
            currentBalance: (float) $customer->wallet_balance,
            amount: $amount,
            transactionType: $transactionType,
            reference: $reference,
            paymentMethod: $paymentMethod,
        );

        return $walletTransaction !== null && $this->customerService->updateWalletBalance(
            id: $customer->id,
            walletBalance: $walletTransaction->balance + $walletTransaction->admin_bonus,
        );
    }

    private function recordLoyaltyPointEntry(int|string $customerId, float $amount, string $transactionType, string $reference): void
    {
        $customer = $this->customerService->find(id: $customerId);

        if ($customer === null) {
            return;
        }

        $pointTransaction = $this->loyaltyPointTransactionService->createPointEntry(
            customerId: $customer->id,
            currentPoint: (float) $customer->loyalty_point,
            amount: $amount,
            transactionType: $transactionType,
            reference: $reference,
        );

        if ($pointTransaction !== null) {
            $this->customerService->updateWhere(params: ['id' => $customer->id], data: ['loyalty_point' => $pointTransaction->balance]);
        }
    }

    private function createStatusLogs(Model $refund, string $status, RefundStatusUpdateRequest $request): void
    {
        $adminId = auth('admin')->id();

        if ($status === RefundRequestService::STATUS_REFUNDED && $this->refundRequestService->needsApprovalBeforeRefund(refund: $refund)) {
            $this->refundStatusService->createForStatusChange(
                refundRequestId: $refund->id,
                changedBy: RefundRequestService::CHANGED_BY_ADMIN,
                changedById: $adminId,
                status: RefundRequestService::STATUS_APPROVED,
                message: $request['approved_note'] ?? translate('Auto approved before refund'),
            );
        }

        $this->refundStatusService->createForStatusChange(
            refundRequestId: $refund->id,
            changedBy: RefundRequestService::CHANGED_BY_ADMIN,
            changedById: $adminId,
            status: $status,
            message: $this->refundRequestService->getStatusChangeNote(
                status: $status,
                approvedNote: $request['approved_note'],
                rejectedNote: $request['rejected_note'],
                paymentInfo: $request['payment_info'],
            ),
        );
    }

    private function getVendorContact(Model $order): ?array
    {
        if ($order->seller_is === RefundRequestService::SELLER_IS_SELLER) {
            if ($order->seller === null) {
                return null;
            }

            return [
                'shop_name' => $order->seller->shop?->name,
                'email' => $order->seller->email,
                'phone' => $order->seller->phone,
                'is_verified' => $order->seller->shop !== null && $order->seller->hasVerifiedBadge(),
            ];
        }

        return [
            'shop_name' => getInHouseShopConfig(key: 'name'),
            'email' => getWebConfig(name: 'company_email'),
            'phone' => getInHouseShopConfig(key: 'contact'),
            'is_verified' => false,
        ];
    }
}
