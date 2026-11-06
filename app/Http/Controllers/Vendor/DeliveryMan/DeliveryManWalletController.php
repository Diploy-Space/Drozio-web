<?php

namespace App\Http\Controllers\Vendor\DeliveryMan;

use App\Events\CashCollectEvent;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\DeliveryMan\WalletRequest;
use App\Services\New\DeliveryManService;
use App\Services\New\DeliveryManTransactionService;
use App\Services\New\DeliverymanWalletService;
use App\Services\New\OrderService;
use App\Services\New\OrderStatusHistoryService;
use App\Traits\CommonTrait;
use App\Traits\PushNotificationTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DeliveryManWalletController extends BaseController
{
    use CommonTrait;
    use PushNotificationTrait;

    private const TRANSACTION_TYPE_CASH_IN_HAND = 'cash_in_hand';

    private const USER_TYPE_SELLER = 'seller';

    public function __construct(
        private readonly DeliveryManService $deliveryManService,
        private readonly DeliverymanWalletService $deliverymanWalletService,
        private readonly DeliveryManTransactionService $deliveryManTransactionService,
        private readonly OrderService $orderService,
        private readonly OrderStatusHistoryService $orderStatusHistoryService,
    ) {}

    public function index(?Request $request, string|int|null $type = null): \Illuminate\Contracts\View\View|Collection|LengthAwarePaginator|null|callable|\Illuminate\Http\RedirectResponse
    {
        return $this->getView(id: $type);
    }

    public function getView(string|int $id): RedirectResponse|View
    {
        $deliveryMan = $this->findVendorDeliveryMan(id: $id);
        $withdrawAbleBalance = isset($deliveryMan->wallet)
            ? $this->delivery_man_withdrawable_balance($deliveryMan['id'])
            : null;

        return view('vendor-views.delivery-man.wallet.index', compact('deliveryMan', 'withdrawAbleBalance'));
    }

    public function getOrderHistory(Request $request, string|int $id): RedirectResponse|View
    {
        $searchValue = $request['search'];
        $deliveryMan = $this->findVendorDeliveryMan(id: $id);
        $orders = $this->orderService->getList(
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: 'pagination_limit'),
            page: $request['page'],
            filters: [
                'delivery_man_id' => $id,
                OrderService::FILTER_DELIVERYMAN_SELLER_ID => auth('seller')->id(),
            ],
            sortBy: OrderService::SORT_NEWEST,
        );

        return view('vendor-views.delivery-man.wallet.order-history', compact('deliveryMan', 'orders', 'searchValue'));
    }

    public function getOrderStatusHistory(string|int $order): View
    {
        $histories = $this->orderStatusHistoryService->getList(filters: ['order_id' => $order]);

        return view('vendor-views.delivery-man.wallet._order-status-history', compact('histories'));
    }

    public function getEarningListView(Request $request, string|int $id): RedirectResponse|View
    {
        $searchValue = $request['search'];
        $deliveryMan = $this->findVendorDeliveryMan(id: $id);
        $orders = $this->orderService->getList(
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: 'pagination_limit'),
            page: $request['page'],
            filters: [
                'delivery_man_id' => $id,
                'seller_id' => auth('seller')->id(),
            ],
        );
        $totalEarn = self::delivery_man_total_earn($id);
        $withdrawableBalance = self::delivery_man_withdrawable_balance($id);

        return view('vendor-views.delivery-man.wallet.earning', compact('deliveryMan', 'orders', 'totalEarn', 'withdrawableBalance', 'searchValue'));
    }

    public function getCashCollectView(Request $request, string|int $id): View
    {
        $deliveryMan = $this->findVendorDeliveryMan(id: $id);
        $transactions = $this->deliveryManTransactionService->getList(
            dataLimit: getWebConfig(name: 'pagination_limit'),
            page: $request['page'],
            filters: [
                'delivery_man_id' => $deliveryMan['id'],
                'transaction_type' => self::TRANSACTION_TYPE_CASH_IN_HAND,
            ],
            sortBy: DeliveryManTransactionService::SORT_LATEST,
        );

        return view('vendor-views.delivery-man.wallet.cash-collect', compact('deliveryMan', 'transactions'));
    }

    public function collectCash(WalletRequest $request, string|int $id): RedirectResponse
    {
        $deliveryMan = $this->deliveryManService->findBy(params: [
            'seller_id' => auth('seller')->id(),
            'id' => $id,
        ]);

        if (empty($deliveryMan)) {
            ToastMagic::warning(translate('Delivery man not found'));

            return redirect()->back();
        }

        $wallet = $this->deliverymanWalletService->findBy(params: ['delivery_man_id' => $id]);
        $amount = currencyConverter(amount: $request['amount'] ?? 0);

        if (empty($wallet) || $amount > $wallet['cash_in_hand']) {
            ToastMagic::warning(translate('Receive amount cannot be more than cash in hand').'!');

            return redirect()->back();
        }

        $this->deliveryManTransactionService->create(data: [
            'delivery_man_id' => $id,
            'user_id' => auth('seller')->id(),
            'user_type' => self::USER_TYPE_SELLER,
            'credit' => $amount,
            'transaction_type' => self::TRANSACTION_TYPE_CASH_IN_HAND,
        ]);

        if (! $this->deliverymanWalletService->deductCashInHand(deliverymanId: (int) $id, amount: $amount)) {
            ToastMagic::error(translate('Failed to receive amount').'!');

            return back();
        }

        if (! empty($deliveryMan['fcm_token'])) {
            CashCollectEvent::dispatch('cash_collect_by_seller_message', 'delivery_man', $deliveryMan['app_language'] ?? getDefaultLanguage(), $amount, $deliveryMan['fcm_token']);
        }

        ToastMagic::success(translate('Amount received successfully').'!');

        return back();
    }

    private function findVendorDeliveryMan(string|int $id): mixed
    {
        return $this->deliveryManService->findBy(
            params: ['seller_id' => auth('seller')->id(), 'id' => $id],
            relations: ['wallet'],
        );
    }
}
