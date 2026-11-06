<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Enums\AuthErrorCode;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Profile\DeviceTokenUpdateRequest;
use App\Http\Requests\Customer\Profile\UpdateApiRequest as ProfileUpdateRequest;
use App\Http\Requests\Shared\Profile\LanguageRequest;
use App\Http\Resources\V6\Customer\CustomerProfileResource;
use App\Services\New\CustomerService;
use App\Services\New\GuestUserService;
use App\Services\New\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\Auction\app\Enums\OwnerType;
use Modules\Auction\app\Services\New\AccountDeletionGuardService;
use Modules\Auction\app\Services\New\ProductService as AuctionProductService;
use Modules\Auction\app\Services\New\SavedProductService as AuctionSavedProductService;

class AccountController extends BaseApiController
{
    private const PROFILE_COUNTS = ['orders', 'referredCustomers'];

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly GuestUserService $guestUserService,
        private readonly OrderService $orderService,
    ) {}

    #[ApiDoc(
        summary: 'The signed-in customer profile',
        description: 'Returns the profile with its order and referral counts, including the retail and wholesale order counts, and the auction counts the app decides its auction menu from: auctions the customer owns, auctions they joined as a bidder and auctions they saved. show_auction_menu_for_customer is true while the auction feature is on, and stays true after it is turned off for a customer who already has any of that activity. All three counts are zero and the flag false when the Auction add-on is not published. Internal columns such as the temporary token, push tokens and stored card details are not exposed.',
        tag: 'Customer',
        status: [401],
        guard: 'api',
    )]
    public function getProfile(Request $request): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => $this->profileResource(customerId: (int) $request->user()->id)],
        );
    }

    #[ApiDoc(
        summary: 'Update the signed-in customer profile',
        description: 'Changing the phone or email clears the matching verified flag. Send the image as multipart with _method=PUT.',
        tag: 'Customer',
        status: [401, 422],
        guard: 'api',
    )]
    public function updateProfile(ProfileUpdateRequest $request): JsonResponse
    {
        $this->customerService->update(
            id: $request->user()->id,
            data: $request->validated(),
            image: $request->file('image'),
        );

        return $this->responseFormatter(
            config: config('response.profile_updated'),
            content: ['data' => $this->profileResource(customerId: (int) $request->user()->id)],
        );
    }

    #[ApiDoc(
        summary: 'Change the app language for the signed-in customer',
        tag: 'Account',
        status: [401, 422],
        guard: 'api',
    )]
    public function updateLanguage(LanguageRequest $request): JsonResponse
    {
        $this->customerService->update(
            id: $request->user()->id,
            data: ['app_language' => $request['current_language']],
        );

        return $this->responseFormatter(
            config: config('response.language_updated_successfully'),
            content: ['app_language' => $request['current_language']],
        );
    }

    #[ApiDoc(
        summary: 'Store the device push token',
        description: 'Saves the Firebase token against the signed-in customer, or against the guest identified by guest_id when the caller is not signed in.',
        tag: 'Customer',
        status: [401, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function updateDeviceToken(DeviceTokenUpdateRequest $request): JsonResponse
    {
        $customer = auth('api')->user();
        $token = $request['cm_firebase_token'];

        $updated = $customer !== null
            ? $this->customerService->update(id: $customer->id, data: ['cm_firebase_token' => $token])
            : $this->guestUserService->update(id: $request['guest_id'], data: ['fcm_token' => $token]);

        if ($updated === null) {
            return $this->authError(
                config: config('response.device_account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        return $this->responseFormatter(config: config('response.device_token_updated'));
    }

    #[ApiDoc(
        summary: 'Delete the signed-in customer account',
        description: 'Refuses while the customer has ongoing orders, or unfinished auction obligations when the Auction add-on is active. On success every access token is revoked and the account is removed permanently.',
        tag: 'Customer',
        status: [401, 403],
        guard: 'api',
    )]
    public function deleteAccount(Request $request): JsonResponse
    {
        $customerId = (int) $request->user()->id;

        $blockingReason = $this->deletionBlockingReason(customerId: $customerId);

        if ($blockingReason !== null) {
            return $this->authError(
                config: config($blockingReason),
                errorCode: AuthErrorCode::ACCOUNT_DELETE_BLOCKED,
            );
        }

        $this->customerService->deleteAuthAccessTokens(id: $customerId);
        $this->customerService->delete(id: $customerId);

        return $this->responseFormatter(config: config('response.account_deleted'));
    }

    private function profileResource(int $customerId): CustomerProfileResource
    {
        return new CustomerProfileResource(
            resource: $this->customerService->find(id: $customerId, relations: [], withCount: self::PROFILE_COUNTS),
            orderCountsByBusinessModule: $this->orderService->getCustomerOrderCountsByBusinessModule(customerId: $customerId),
            auctionProfileData: $this->getAuctionProfileData(customerId: $customerId),
        );
    }

    private function getAuctionProfileData(int $customerId): array
    {
        if (! getCheckAddonPublishedStatus(moduleName: 'Auction')) {
            return [
                'auction_products_count' => 0,
                'auction_bids_count' => 0,
                'auction_saved_products_count' => 0,
                'show_auction_menu_for_customer' => false,
            ];
        }

        $auctionProductService = app(AuctionProductService::class);

        $activityCounts = [
            'auction_products_count' => $auctionProductService->count(filters: ['owner_type' => OwnerType::CUSTOMER, 'owner_id' => $customerId]),
            'auction_bids_count' => $auctionProductService->countBidParticipation(userId: $customerId),
            'auction_saved_products_count' => app(AuctionSavedProductService::class)->count(filters: ['user_id' => $customerId]),
        ];

        $isAuctionFeatureEnabled = (int) getWebConfig(name: 'auction_feature_status') === 1;

        return $activityCounts + [
            'show_auction_menu_for_customer' => $isAuctionFeatureEnabled || array_sum($activityCounts) > 0,
        ];
    }

    private function deletionBlockingReason(int $customerId): ?string
    {
        $ongoingOrders = $this->orderService->getStatusSummaryForCustomer(customerId: $customerId)['ongoing'];

        if ($ongoingOrders > 0) {
            return 'response.account_delete_ongoing_order';
        }

        if (! getCheckAddonPublishedStatus(moduleName: 'Auction')) {
            return null;
        }

        return app(AccountDeletionGuardService::class)->blockingReason($customerId);
    }
}
