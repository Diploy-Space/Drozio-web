<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Enums\AuthErrorCode;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Shared\Profile\LanguageRequest;
use App\Http\Requests\Vendor\Profile\FcmTokenRequest;
use App\Http\Requests\Vendor\Profile\UpdateRequest;
use App\Http\Resources\V6\Vendor\ProfileResource;
use App\Services\New\Auth\VendorAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CouponService;
use App\Services\New\DeliveryManService;
use App\Services\New\OrderService;
use App\Services\New\OrderTransactionService;
use App\Services\New\ProductRetailService;
use App\Services\New\ProductService;
use App\Services\New\VendorService;
use App\Services\New\VendorWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\Auction\app\Enums\ApprovalStatus;
use Modules\Auction\app\Enums\OwnerType;
use Modules\Auction\app\Services\New\ProductService as AuctionProductService;
use Modules\Auction\app\Services\New\VendorWalletStatsService;

class ProfileController extends BaseApiController
{
    /** @var array<int, string> */
    private const BANK_FIELDS = ['bank_name', 'branch', 'account_no', 'holder_name'];

    /** @var array<int, string> */
    private const AUCTION_WALLET_AMOUNT_KEYS = [
        'auction_total_earning',
        'auction_total_vat',
        'auction_total_shipping',
        'auction_withdrawable_balance',
        'auction_pending_withdraw',
        'auction_total_commission_given',
        'auction_pending_commission',
        'auction_total_pending_amount',
    ];

    public function __construct(
        private readonly VendorService $vendorService,
        private readonly VendorAuthService $vendorAuthService,
        private readonly ProductRetailService $productService,
        private readonly OrderService $orderService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly VendorWalletService $vendorWalletService,
        private readonly OrderTransactionService $orderTransactionService,
        private readonly DeliveryManService $deliveryManService,
        private readonly CouponService $couponService,
    ) {}

    #[ApiDoc(
        summary: 'Get the signed-in vendor profile',
        description: 'Returns the vendor with wallet balances, product and order counts, verification status, retail and wholesale free-delivery settings, and the auction wallet totals and menu flags the app renders. Auction amounts are zero and both auction flags false when the Auction add-on is not published. Credential columns are never included.',
        tag: 'Profile (Vendor)',
        status: [401, 403],
        guard: 'seller',
    )]
    public function getProfile(Request $request): JsonResponse
    {
        $sellerId = (int) $request->attributes->get('seller')->id;
        $vendor = $this->vendorService->find(id: $sellerId, relations: ['wallet']);

        if ($vendor === null) {
            return $this->authError(
                config: config('response.account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => (new ProfileResource($vendor))->resolve() + [
                'product_count' => $this->productService->count(filters: [
                    'added_by' => ProductService::ADDED_BY_SELLER,
                    'user_id' => $sellerId,
                ]),
                'orders_count' => $this->orderService->count(filters: [
                    'seller_is' => OrderService::SELLER_IS_SELLER,
                    'seller_id' => $sellerId,
                ]),
                'free_delivery_features_status' => $this->businessSettingService->getValueByType(type: BusinessSettingService::FREE_DELIVERY_STATUS),
                'free_delivery_responsibility' => $this->businessSettingService->getValueByType(type: BusinessSettingService::FREE_DELIVERY_RESPONSIBILITY),
                'minimum_order_amount_by_seller' => $this->businessSettingService->getValueByType(type: BusinessSettingService::MINIMUM_ORDER_AMOUNT_BY_SELLER),
                'can_set_wholesale_minimum_order_amount' => $this->vendorService->canSetWholesaleMinimumOrderAmount(),
            ] + $this->getAuctionProfileData(sellerId: $sellerId),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Update the signed-in vendor profile and bank details',
        description: 'Only the fields sent are written, so a partial update never blanks stored values. Changing the password revokes every existing session and returns a fresh token.',
        tag: 'Profile (Vendor)',
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function updateProfile(UpdateRequest $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');
        $data = $request->validated();
        $password = $request['password'];

        $token = DB::transaction(function () use ($seller, $request, $data, $password): ?string {
            $this->vendorService->update(id: $seller->id, data: $data, image: $request->file('image'));

            $bankData = array_intersect_key($data, array_flip(self::BANK_FIELDS));

            if ($bankData !== []) {
                $this->vendorService->updateBankInfo(id: $seller->id, data: $bankData);
            }

            if ($password === null) {
                return null;
            }

            $this->vendorService->updatePassword(id: $seller->id, password: $password);

            return $this->vendorAuthService->issueAccessToken(account: $seller->refresh());
        });

        return $this->responseFormatter(
            config: config('response.information_updated'),
            content: $token === null ? null : ['token' => $token],
        );
    }

    #[ApiDoc(
        summary: 'Change the app language for the signed-in vendor',
        description: 'Stores the vendor app language. The code must be one of the languages enabled in the admin panel.',
        tag: 'Account (Vendor)',
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function updateLanguage(LanguageRequest $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        $this->vendorService->update(
            id: $seller->id,
            data: ['app_language' => $request['current_language']],
        );

        return $this->responseFormatter(
            config: config('response.language_updated_successfully'),
            content: ['app_language' => $request['current_language']],
        );
    }

    #[ApiDoc(
        summary: 'Store the push notification device token for the signed-in vendor',
        description: 'Saves the Firebase cloud messaging token used to deliver push notifications to the vendor app.',
        tag: 'Account (Vendor)',
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function updateFcmToken(FcmTokenRequest $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        $this->vendorService->update(
            id: $seller->id,
            data: ['cm_firebase_token' => $request['cm_firebase_token']],
        );

        return $this->responseFormatter(config: config('response.device_token_updated'));
    }

    #[ApiDoc(
        summary: 'Delete the signed-in vendor account',
        description: 'Refuses while the vendor has ongoing orders, owes commission to the admin, or has delivery men holding unsettled balances. On success the vendor and their own coupons are removed permanently.',
        tag: 'Account (Vendor)',
        status: [401, 403],
        guard: 'seller',
    )]
    public function deleteAccount(Request $request): JsonResponse
    {
        $sellerId = (int) $request->attributes->get('seller')->id;

        $blocking = $this->deletionBlockingReason(sellerId: $sellerId);

        if ($blocking !== null) {
            return $this->authError(
                config: config($blocking['config_key']),
                errorCode: $blocking['error_code'],
            );
        }

        DB::transaction(function () use ($sellerId): void {
            $this->couponService->deleteBy(params: [
                'coupon_bearer' => CouponService::BEARER_SELLER,
                'seller_id' => $sellerId,
            ]);

            $this->vendorService->delete(id: $sellerId);
        });

        return $this->responseFormatter(config: config('response.account_deleted'));
    }

    private function getAuctionProfileData(int $sellerId): array
    {
        if (! getCheckAddonPublishedStatus(moduleName: 'Auction')) {
            return ['show_auction_menu_for_vendor' => false, 'auction_wallet_visible' => false]
                + array_fill_keys(self::AUCTION_WALLET_AMOUNT_KEYS, 0);
        }

        $isAuctionFeatureEnabled = (int) getWebConfig(name: 'auction_feature_status') === 1;

        $showAuctionMenu = $isAuctionFeatureEnabled || app(AuctionProductService::class)->count(filters: [
            'owner_type' => OwnerType::SELLER,
            'owner_id' => $sellerId,
            'approval_status' => ApprovalStatus::APPROVED,
        ]) > 0;

        $walletStats = app(VendorWalletStatsService::class)->getStats(vendorId: $sellerId);

        return [
            'show_auction_menu_for_vendor' => $showAuctionMenu,
            'auction_wallet_visible' => (bool) $walletStats['auction_wallet_visible'],
        ] + array_map(
            static fn ($amount): float => (float) $amount,
            array_intersect_key($walletStats, array_flip(self::AUCTION_WALLET_AMOUNT_KEYS)),
        );
    }

    /**
     * @return array{error_code: string, config_key: string}|null
     */
    private function deletionBlockingReason(int $sellerId): ?array
    {
        $ongoingOrders = $this->orderService->count(filters: [
            'order_status' => OrderService::ONGOING_STATUSES,
            'seller_is' => OrderService::SELLER_IS_SELLER,
            'seller_id' => $sellerId,
        ]);

        if ($ongoingOrders > 0) {
            return [
                'error_code' => AuthErrorCode::ACCOUNT_DELETE_ONGOING_ORDER,
                'config_key' => 'response.account_delete_ongoing_order',
            ];
        }

        if ($this->hasUnpaidAdminCommission(sellerId: $sellerId)) {
            return [
                'error_code' => AuthErrorCode::ACCOUNT_DELETE_COMMISSION_DUE,
                'config_key' => 'response.account_delete_admin_commission_due',
            ];
        }

        if ($this->deliveryManService->countWithUnsettledWallet(sellerId: $sellerId) > 0) {
            return [
                'error_code' => AuthErrorCode::ACCOUNT_DELETE_DELIVERY_MAN_DUE,
                'config_key' => 'response.account_delete_delivery_man_due',
            ];
        }

        return null;
    }

    private function hasUnpaidAdminCommission(int $sellerId): bool
    {
        $owed = $this->orderTransactionService->sumAdminCommissionForSeller(sellerId: $sellerId);
        $wallet = $this->vendorWalletService->findBy(params: ['seller_id' => $sellerId]);

        return $owed > (float) ($wallet->commission_given ?? 0);
    }
}
