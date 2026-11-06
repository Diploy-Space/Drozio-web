<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\SessionKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Profile\UpdateRequest;
use App\Services\New\CustomerService;
use App\Services\New\OrderService;
use App\Services\New\ShippingAddressService;
use App\Services\New\WishlistService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Auction\app\Services\AuctionAccountDeletionGuardService;

class ProfileController extends Controller
{
    private const AUCTION_MODULE = 'Auction';

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly WishlistService $wishlistService,
        private readonly OrderService $orderService,
        private readonly ShippingAddressService $shippingAddressService,
    ) {}

    public function show(): View
    {
        $viewName = themeViewName(key: 'user_profile');
        $customerId = (int) auth('customer')->id();
        $customer = $this->customerService->find(id: $customerId);

        return view($viewName, [
            'customer_detail' => $customer,
            'addresses' => $this->shippingAddressService->getList(dataLimit: 'all', filters: ['customer_id' => $customerId]),
            'wishlists' => $this->wishlistService->countForCustomer(customerId: $customerId),
            'total_order' => $this->orderService->count(filters: ['customer_id' => $customerId]),
            'total_loyalty_point' => $customer->loyalty_point,
            'totalWalletBalance' => $customer->wallet_balance,
        ]);
    }

    public function account(): View
    {
        return view(themeViewName(key: 'user_account'), [
            'customerDetail' => $this->customerService->find(id: auth('customer')->id()),
            'nameMaxLength' => UpdateRequest::NAME_MAX_LENGTH,
            'nameLimitMessage' => translate('You have reached the :max character limit', ['max' => UpdateRequest::NAME_MAX_LENGTH]),
        ]);
    }

    public function update(UpdateRequest $request): RedirectResponse
    {
        $this->customerService->updateProfile(
            id: auth('customer')->id(),
            data: $request->only(['f_name', 'l_name', 'phone', 'email', 'password']),
            image: $request->file('image'),
        );

        ToastMagic::info(translate('Updated successfully'));

        return redirect()->back();
    }

    public function delete(string $id): RedirectResponse
    {
        $customerId = auth('customer')->id();

        if ($customerId === null || $customerId != $id) {
            ToastMagic::warning(translate('Access denied').'!!');

            return back();
        }

        if ($this->orderService->count(filters: ['customer_id' => $customerId, 'order_status' => OrderService::ONGOING_STATUSES]) > 0) {
            ToastMagic::warning(translate('You cannot delete account due ongoing order'));

            return redirect()->back();
        }

        $auctionBlocker = $this->getAuctionDeletionBlocker(customerId: (int) $customerId);

        if ($auctionBlocker !== null) {
            ToastMagic::warning(translate($auctionBlocker));

            return redirect()->back();
        }

        auth()->guard('customer')->logout();
        session()->forget(SessionKey::WISH_LIST);

        $this->customerService->delete(id: $customerId);

        ToastMagic::info(translate('Your account deleted successfully'));

        return redirect()->route('home');
    }

    private function getAuctionDeletionBlocker(int $customerId): ?string
    {
        if (! getCheckAddonPublishedStatus(moduleName: self::AUCTION_MODULE)) {
            return null;
        }

        return app(AuctionAccountDeletionGuardService::class)->blockingReason($customerId);
    }
}
