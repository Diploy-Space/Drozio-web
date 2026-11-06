<?php

namespace App\Http\Controllers\Admin\Deliveryman;

use App\Enums\WebConfigKey;
use App\Events\CashCollectEvent;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\DeliveryMan\CashCollectRequest;
use App\Services\New\DeliveryManService;
use App\Services\New\DeliveryManTransactionService;
use App\Services\New\DeliverymanWalletService;
use App\Traits\PushNotificationTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryManCashCollectController extends BaseController
{
    use PushNotificationTrait;

    public function __construct(
        private readonly DeliveryManService $deliveryManService,
        private readonly DeliverymanWalletService $deliverymanWalletService,
        private readonly DeliveryManTransactionService $deliveryManTransactionService,
    ) {}

    /**
     * @return View Index function is the starting point of a controller
     *              Index function is the starting point of a controller
     */
    public function index(?Request $request, ?string $type = null): View
    {
        $deliveryMan = $this->deliveryManService->find(id: $request['id'], relations: ['wallet']);
        $transactions = $this->deliveryManTransactionService->getList(
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            page: $request['page'],
            filters: ['delivery_man_id' => $request['id']],
            sortBy: DeliveryManTransactionService::SORT_LATEST,
        );

        return view('admin-views.delivery-man.earning-statement.collect-cash', compact('deliveryMan', 'transactions'));
    }

    public function getCashReceive(CashCollectRequest $request, $id): RedirectResponse
    {
        $wallet = $this->deliverymanWalletService->findBy(params: ['delivery_man_id' => $id]);
        if (empty($wallet) || currencyConverter(amount: $request['amount']) > $wallet['cash_in_hand']) {
            ToastMagic::warning(translate('Receive amount cannot be more than cash in hand'));

            return back();
        }
        $deliveryMan = $this->deliveryManService->find(id: $id);
        $this->deliveryManTransactionService->create(data: [
            'delivery_man_id' => $deliveryMan['id'],
            'user_id' => 0,
            'user_type' => 'admin',
            'credit' => currencyConverter(amount: $request['amount']),
            'transaction_type' => 'cash_in_hand',
        ]);
        $this->deliverymanWalletService->deductCashInHand(
            deliverymanId: (int) $id,
            amount: currencyConverter(amount: $request['amount']),
        );
        if (! empty($deliveryMan['fcm_token'])) {
            CashCollectEvent::dispatch('cash_collect_by_admin_message', 'delivery_man', $deliveryMan['app_language'] ?? getDefaultLanguage(), currencyConverter(amount: $request['amount']), $deliveryMan['fcm_token']);
        }
        ToastMagic::success(translate('Amount received successfully'));

        return back();
    }
}
