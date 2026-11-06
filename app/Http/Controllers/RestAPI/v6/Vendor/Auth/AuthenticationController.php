<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor\Auth;

use App\Enums\AuthErrorCode;
use App\Events\VendorRegistrationEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\Auth\LoginRequest;
use App\Http\Requests\Vendor\Auth\RegistrationRequest;
use App\Http\Requests\Vendor\Auth\VendorExistInfoRequest;
use App\Services\New\Auth\AdminAuthService;
use App\Services\New\Auth\VendorAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\New\ShopService;
use App\Services\New\VendorWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class AuthenticationController extends BaseApiController
{
    private const DEFAULT_MAX_LOGIN_HIT = 5;

    private const DEFAULT_BLOCK_SECONDS = 600;

    public function __construct(
        private readonly VendorAuthService $vendorAuthService,
        private readonly ShopService $shopService,
        private readonly VendorWalletService $sellerWalletService,
        private readonly AdminAuthService $adminAuthService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Vendor login',
        description: 'Issues the bearer token the seller app sends on every later call. Only one token is live per vendor, so logging in on a second device signs the first one out.',
        tag: 'Auth (Seller)',
        status: [401, 403, 422, 429],
        auth: false,
        guard: 'seller',
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $identity = $request['email'];
        $maxAttempts = $this->maxLoginHit();
        $blockSeconds = $this->loginBlockSeconds();

        $seller = $this->vendorAuthService->findByIdentity(identity: $identity);

        $retryAfter = $this->vendorAuthService->secondsUntilLoginAllowed(
            identity: $identity,
            account: $seller,
            maxAttempts: $maxAttempts,
            blockSeconds: $blockSeconds,
        );

        if ($retryAfter > 0) {
            return $this->authThrottled(
                config: config('response.login_attempts_blocked'),
                errorCode: AuthErrorCode::LOGIN_BLOCKED,
                retryAfter: $retryAfter,
            );
        }

        if ($seller === null || ! $this->vendorAuthService->verifyPassword(account: $seller, password: $request['password'])) {
            $this->vendorAuthService->registerLoginFailure(
                identity: $identity,
                account: $seller,
                maxAttempts: $maxAttempts,
                blockSeconds: $blockSeconds,
            );

            return $this->authError(
                config: config('response.credentials_mismatch'),
                errorCode: AuthErrorCode::INVALID_CREDENTIALS,
            );
        }

        $reason = $this->vendorAuthService->accountStatusReason(account: $seller);

        if ($reason !== null) {
            return $this->authError(
                errorCode: $reason === VendorAuthService::REASON_PENDING_APPROVAL
                    ? AuthErrorCode::VENDOR_PENDING_APPROVAL
                    : AuthErrorCode::VENDOR_NOT_APPROVED,
                config: config($reason === VendorAuthService::REASON_PENDING_APPROVAL ? 'response.vendor_approval_in_review' : 'response.vendor_approval_revoked'),
            );
        }

        $this->vendorAuthService->clearLoginFailures(identity: $identity, account: $seller);
        $this->sellerWalletService->createForSeller(sellerId: $seller->id);

        return $this->responseFormatter(
            config: config('response.login_success'),
            content: ['token' => $this->vendorAuthService->issueAccessToken(account: $seller), 'token_type' => 'Bearer'],
        );
    }

    #[ApiDoc(
        summary: 'Vendor logout',
        tag: 'Auth (Seller)',
        status: [401],
        guard: 'seller',
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->vendorAuthService->revokeAccessToken(account: $request->attributes->get('seller'));

        return $this->responseFormatter(config: config('response.logged_out'));
    }

    #[ApiDoc(
        summary: 'Vendor registration',
        description: 'Creates the vendor and their shop in one transaction. The account opens in review and cannot log in until an admin approves it.',
        tag: 'Auth (Seller)',
        status: [201, 422],
        auth: false,
        guard: 'seller',
    )]
    public function register(RegistrationRequest $request): JsonResponse
    {
        if ($this->adminAuthService->identityExists(identity: $request['email'])) {
            return $this->authError(
                config: config('response.email_already_in_use'),
                errorCode: AuthErrorCode::ACCOUNT_EMAIL_RESERVED,
            );
        }

        $seller = DB::transaction(function () use ($request) {
            $seller = $this->vendorAuthService->create(
                data: $request->validated(),
                image: $request->file('image'),
            );

            $this->shopService->create(
                data: [
                    'seller_id' => $seller->id,
                    'name' => $request['shop_name'],
                    'contact' => $request['phone'],
                    'address' => $request['shop_address'],
                    'tax_identification_number' => $request['tax_identification_number'],
                    'tin_expire_date' => $request['tin_expire_date'],
                ],
                images: [
                    'image' => $request->file('logo'),
                    'banner' => $request->file('banner'),
                    'bottom_banner' => $request->file('bottom_banner'),
                    'tin_certificate' => $request->file('tin_certificate'),
                ],
            );

            $this->sellerWalletService->createForSeller(sellerId: $seller->id);

            return $seller;
        });

        event(new VendorRegistrationEvent(email: $seller->email, data: [
            'vendorName' => $seller->f_name,
            'status' => $seller->status,
            'subject' => translate('Vendor registration successfully completed'),
            'title' => translate('Vendor registration successfully completed'),
            'userType' => 'vendor',
            'templateName' => 'registration',
        ]));

        return $this->responseFormatter(
            config: config('response.your_shop_application_has_been_submitted_for_rev'),
            content: ['status' => $seller->status,
            ],
        );
    }

    #[ApiDoc(
        summary: 'Check whether a vendor phone number is already registered',
        tag: 'Auth (Seller)',
        status: [422],
        auth: false,
        guard: 'seller',
    )]
    public function checkExisting(VendorExistInfoRequest $request): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['exists' => $this->vendorAuthService->identityExists(identity: $request['phone']),
            ],
        );
    }

    private function maxLoginHit(): int
    {
        return (int) $this->businessSettingService->getValueByType(
            type: 'maximum_login_hit',
            default: self::DEFAULT_MAX_LOGIN_HIT,
        );
    }

    private function loginBlockSeconds(): int
    {
        return (int) $this->businessSettingService->getValueByType(
            type: 'temporary_login_block_time',
            default: self::DEFAULT_BLOCK_SECONDS,
        );
    }
}
