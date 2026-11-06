<?php

namespace App\Builder;

use App\Enums\UserType;
use App\Events\EmailVerificationEvent;
use App\Events\OtpSmsEvent;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\Auth\VerificationService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CustomerService;
use App\Services\New\GuestUserService;
use App\Services\New\LoginSetupService;
use App\Services\New\ProductCompareService;
use App\Services\New\RestockProductService;
use App\Services\New\WishlistService;
use App\Utils\CartManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Builder\app\Contracts\CustomerAuthProvider as CustomerAuthProviderContract;
use Modules\Builder\app\ValueObjects\StorefrontCustomer;

class CustomerAuthProvider implements CustomerAuthProviderContract
{
    private const PENDING_PROFILE_SESSION_KEY = 'pending_profile_user_id';

    private const SESSION_KEYS_CLEARED_ON_LOGIN = [
        'coupon_code', 'coupon_type', 'coupon_bearer', 'coupon_discount',
        'payment_method', 'shipping_method_id', 'billing_address_id',
        'order_id', 'cart_group_id', 'order_note',
        'wish_list', 'compare_list', 'customer_fcm_topic',
    ];

    private const DEFAULT_MAX_OTP_HIT = 5;

    public function __construct(
        private CustomerAuthService $customerAuthService,
        private CustomerService $customerService,
        private VerificationService $verificationService,
        private PasswordResetService $passwordResetService,
        private LoginSetupService $loginSetupService,
        private BusinessSettingService $businessSettingService,
        private GuestUserService $guestUserService,
        private WishlistService $wishlistService,
        private ProductCompareService $productCompareService,
        private RestockProductService $restockProductService,
    ) {}

    public function current(): ?StorefrontCustomer
    {
        $user = Auth::guard('customer')->user();

        return $user ? $this->toCustomer($user) : null;
    }

    public function loginWithPassword(string $emailOrPhone, string $password, string $fieldType): StorefrontCustomer
    {
        $credentials = $fieldType === 'email'
            ? ['email' => $emailOrPhone, 'password' => $password]
            : ['phone' => $this->normalizePhone($emailOrPhone), 'password' => $password];

        if (! Auth::guard('customer')->attempt($credentials)) {
            throw ValidationException::withMessages(['email_or_phone' => $fieldType === 'email'
                ? translate('Incorrect email or password. Please try again')
                : translate('Incorrect phone number or password. Please try again')]);
        }

        $user = Auth::guard('customer')->user();
        $this->customerAuthService->markLoginMedium(id: $user->id, medium: CustomerAuthService::MEDIUM_SYSTEM);
        $this->afterLogin($user);

        return $this->toCustomer($user);
    }

    public function sendLoginOtp(string $phone): void
    {
        $this->issuePhoneOtp($this->normalizePhone($phone));
    }

    public function loginWithOtp(string $phone, string $otp): StorefrontCustomer
    {
        $phone = $this->normalizePhone($phone);
        $this->assertOtp(identity: $phone, otp: $otp);

        $user = $this->customerAuthService->findByPhone(phone: $phone);

        if ($user === null) {
            $user = $this->customerAuthService->create(data: [
                'f_name' => '',
                'phone' => $phone,
                'login_medium' => CustomerAuthService::MEDIUM_OTP,
                'is_phone_verified' => 1,
            ]);
        } else {
            $this->customerAuthService->markVerified(id: $user->id, channel: LoginSetupService::CHANNEL_PHONE);
            $this->customerAuthService->markLoginMedium(id: $user->id, medium: CustomerAuthService::MEDIUM_OTP);
            $user->refresh();
        }

        $this->verificationService->deleteByIdentity(identity: $phone);

        if (! $this->isProfileComplete($user)) {
            session([self::PENDING_PROFILE_SESSION_KEY => $user->id]);

            return $this->toCustomer($user);
        }

        Auth::guard('customer')->loginUsingId($user->id);
        $this->afterLogin($user);

        return $this->toCustomer($user);
    }

    public function register(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = $data['email'] ?? null;
        $phone = $this->normalizePhone($data['phone'] ?? null);
        $password = $data['password'] ?? null;
        $refCode = config('builder.wallet_features_enabled', true) ? ($data['ref_code'] ?? null) : null;

        [$firstName, $lastName] = $this->splitName($name);

        $errors = [];
        if ($name === '') {
            $errors['name'] = [translate('Name is required')];
        }
        if (! $email) {
            $errors['email'] = [translate('Email is required')];
        } elseif ($this->customerAuthService->identityExists(identity: $email)) {
            $errors['email'] = [translate('This email is already registered')];
        }
        if (! $phone) {
            $errors['phone'] = [translate('Phone number is required')];
        } elseif ($this->customerAuthService->identityExists(identity: $phone)) {
            $errors['phone'] = [translate('This phone number is already registered')];
        }
        if (! $password || strlen($password) < 8) {
            $errors['password'] = [translate('Password must be at least 8 characters')];
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $referrer = $refCode === null ? null : $this->customerAuthService->findByReferralCode(code: $refCode);

        $user = $this->customerAuthService->create(data: [
            'f_name' => $firstName,
            'l_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'referred_by' => $referrer?->id,
        ]);

        $requiredChannel = $this->loginSetupService->requiredVerificationChannel();
        $needsPhone = $requiredChannel === LoginSetupService::CHANNEL_PHONE && ! $user->is_phone_verified;
        $needsEmail = ! $needsPhone
            && $requiredChannel === LoginSetupService::CHANNEL_EMAIL
            && ! $user->is_email_verified;

        if ($needsPhone) {
            $this->issuePhoneOtp($phone);
        } elseif ($needsEmail) {
            $this->issueEmailOtp($user);
        } else {
            Auth::guard('customer')->loginUsingId($user->id);
            $this->afterLogin($user);
        }

        return [
            'customer' => $this->toCustomer($user),
            'needsPhoneVerification' => $needsPhone,
            'needsEmailVerification' => $needsEmail,
        ];
    }

    public function verifyContact(string $type, string $value, string $otp): StorefrontCustomer
    {
        $value = $type === LoginSetupService::CHANNEL_PHONE ? $this->normalizePhone($value) : $value;

        $user = $this->customerAuthService->findByIdentity(identity: $value);
        if ($user === null) {
            throw ValidationException::withMessages(['_form' => translate('No account matches this phone number or email')]);
        }

        $this->assertOtp(identity: $value, otp: $otp);
        $this->verificationService->deleteByIdentity(identity: $value);
        $this->customerAuthService->markVerified(id: $user->id, channel: $type);

        Auth::guard('customer')->loginUsingId($user->id);
        $this->afterLogin($user);

        return $this->toCustomer($user->refresh());
    }

    public function resendVerificationOtp(string $type, string $value): void
    {
        if ($type === LoginSetupService::CHANNEL_PHONE) {
            $this->issuePhoneOtp($this->normalizePhone($value));

            return;
        }

        $user = $this->customerAuthService->findByIdentity(identity: $value);

        if ($user !== null) {
            $this->issueEmailOtp($user);
        }
    }

    public function sendPasswordResetOtp(string $type, string $value): void
    {
        $value = $type === LoginSetupService::CHANNEL_PHONE ? $this->normalizePhone($value) : $value;

        $user = $this->customerAuthService->findByIdentity(identity: $value);
        if ($user === null) {
            throw ValidationException::withMessages([$type => translate('No account matches this phone number or email')]);
        }

        $token = $this->passwordResetService->issueOtp(identity: $value, userType: UserType::CUSTOMER);

        if ($type === LoginSetupService::CHANNEL_PHONE) {
            event(new OtpSmsEvent(phone: $value, otp: $token));

            return;
        }

        $this->dispatchEmailOtp(user: $user, token: $token);
    }

    public function verifyPasswordResetOtp(string $type, string $value, string $otp): bool
    {
        $value = $type === LoginSetupService::CHANNEL_PHONE ? $this->normalizePhone($value) : $value;

        return $this->passwordResetService->verifyOtp(
            identity: $value,
            userType: UserType::CUSTOMER,
            token: $otp,
            maxAttempts: $this->maxOtpHit(),
            expirySeconds: $this->businessSettingService->getOtpExpirySeconds(),
        ) === null;
    }

    public function resetPassword(string $type, string $value, string $otp, string $newPassword): void
    {
        $value = $type === LoginSetupService::CHANNEL_PHONE ? $this->normalizePhone($value) : $value;

        if (! $this->verifyPasswordResetOtp($type, $value, $otp)) {
            throw ValidationException::withMessages(['otp' => translate('The OTP you entered is not correct')]);
        }
        if (strlen($newPassword) < 8) {
            throw ValidationException::withMessages(['password' => translate('Password must be at least 8 characters')]);
        }

        $user = $this->customerAuthService->findByIdentity(identity: $value);
        if ($user === null) {
            throw ValidationException::withMessages(['_form' => translate('No account matches this phone number or email')]);
        }

        $this->customerAuthService->updatePassword(id: $user->id, password: $newPassword);
        $this->passwordResetService->deleteByIdentity(identity: $value, userType: UserType::CUSTOMER);
    }

    public function loginWithSocial(string $provider, array $payload): array
    {
        throw ValidationException::withMessages(['_form' => translate('Social login is currently unavailable')]);
    }

    public function logout(): void
    {
        Auth::guard('customer')->logout();
        session()->forget(self::PENDING_PROFILE_SESSION_KEY);
    }

    public function pendingProfileUser(): ?StorefrontCustomer
    {
        $id = session(self::PENDING_PROFILE_SESSION_KEY);
        if (! $id) {
            return null;
        }

        $user = $this->customerService->find(id: $id);
        if ($user === null) {
            session()->forget(self::PENDING_PROFILE_SESSION_KEY);

            return null;
        }

        return $this->toCustomer($user);
    }

    public function completeProfile(array $data): StorefrontCustomer
    {
        $id = session(self::PENDING_PROFILE_SESSION_KEY);
        $user = $id ? $this->customerService->find(id: $id) : null;
        if ($user === null) {
            throw ValidationException::withMessages(['_form' => translate('No pending profile')]);
        }

        $changes = [];

        if (! empty($data['name'])) {
            [$changes['f_name'], $changes['l_name']] = $this->splitName((string) $data['name']);
        }
        if (! empty($data['phone'])) {
            $changes['phone'] = $this->normalizePhone($data['phone']);
        }
        if (! empty($data['email'])) {
            $changes['email'] = $data['email'];
        }
        if (! empty($data['ref_code']) && ! $user->referred_by && config('builder.wallet_features_enabled', true)) {
            $changes['referred_by'] = $this->customerAuthService->findByReferralCode(code: $data['ref_code'])?->id;
        }

        $user = $this->customerService->update(id: $user->id, data: $changes) ?? $user;

        Auth::guard('customer')->loginUsingId($user->id);
        $this->afterLogin($user);
        session()->forget(self::PENDING_PROFILE_SESSION_KEY);

        return $this->toCustomer($user);
    }

    public function updateProfile(array $data, ?UploadedFile $image = null): StorefrontCustomer
    {
        $user = Auth::guard('customer')->user();
        if (! $user) {
            throw ValidationException::withMessages(['_form' => translate('Sign in required')]);
        }

        $changes = [];

        if (array_key_exists('f_name', $data) && $data['f_name'] !== null) {
            $changes['f_name'] = $data['f_name'];
        }
        if (array_key_exists('l_name', $data)) {
            $changes['l_name'] = $data['l_name'] ?? '';
        }
        if (empty($user->email) && ! empty($data['email'])) {
            $changes['email'] = $data['email'];
        }
        if (! empty($data['phone'])) {
            $changes['phone'] = $this->normalizePhone($data['phone']);
        }
        if (! empty($data['password'])) {
            $changes['password'] = $data['password'];
        }

        $updated = $this->customerService->update(id: $user->id, data: $changes, image: $image);

        return $this->toCustomer($updated ?? $user);
    }

    public function ensureGuestId(): ?int
    {
        if (Auth::guard('customer')->check()) {
            return null;
        }

        $guestId = session('guest_id');
        if ($guestId && $this->guestUserService->find(id: $guestId) !== null) {
            return (int) $guestId;
        }

        $guest = $this->guestUserService->create(data: ['ip_address' => request()->ip()]);
        session(['guest_id' => $guest->id]);

        return (int) $guest->id;
    }

    public function loginMethods(): array
    {
        $firebase = $this->businessSettingService->getValueByType(type: 'firebase_otp_verification', default: []);

        return [
            'manual' => true,
            'otp' => (bool) ($firebase['status'] ?? 0),
        ];
    }

    public function socialConfig(): array
    {
        $enabled = (bool) config('builder.social_login_enabled', false);

        return [
            'google' => ['enabled' => $enabled, 'clientId' => null],
            'facebook' => ['enabled' => $enabled, 'appId' => null],
            'apple' => ['enabled' => $enabled, 'clientId' => null, 'redirectUri' => null],
        ];
    }

    private function afterLogin(AuthenticatableUser|Model $user): void
    {
        $this->refreshCustomerSession(customerId: (int) $user->id);

        try {
            CartManager::cartListSessionToDatabase();
        } catch (\Throwable) {
        }
    }

    private function refreshCustomerSession(int $customerId): void
    {
        foreach (self::SESSION_KEYS_CLEARED_ON_LOGIN as $key) {
            session()->forget($key);
        }

        session()->put('wish_list', $this->wishlistService->getProductIds(customerId: $customerId));
        session()->put('compare_list', $this->productCompareService->getProductIds(customerId: $customerId));
        session()->put('customer_fcm_topic', $this->restockProductService->getCustomerFcmTopics(customerId: $customerId));
    }

    private function issuePhoneOtp(string $phone): void
    {
        event(new OtpSmsEvent(
            phone: $phone,
            otp: $this->verificationService->issueOtp(identity: $phone),
        ));
    }

    private function issueEmailOtp(AuthenticatableUser|Model $user): void
    {
        $this->dispatchEmailOtp(
            user: $user,
            token: $this->verificationService->issueOtp(identity: (string) $user->email),
        );
    }

    private function dispatchEmailOtp(AuthenticatableUser|Model $user, string $token): void
    {
        if (empty($user->email) || ! $this->isMailConfigured()) {
            return;
        }

        event(new EmailVerificationEvent(email: $user->email, data: [
            'userType' => 'customer',
            'templateName' => 'registration-verification',
            'userName' => $user->f_name,
            'subject' => translate('Email verification'),
            'title' => translate('Email verification'),
            'verificationCode' => $token,
        ]));
    }

    private function assertOtp(string $identity, string $otp): void
    {
        $reason = $this->verificationService->verifyOtp(
            identity: $identity,
            token: $otp,
            maxAttempts: $this->maxOtpHit(),
            expirySeconds: $this->businessSettingService->getOtpExpirySeconds(),
        );

        if ($reason === null) {
            return;
        }

        throw ValidationException::withMessages([
            'otp' => $reason === VerificationService::REASON_OTP_EXPIRED
                ? translate('This OTP has expired. Please ask for a new one')
                : translate('The OTP you entered is not correct'),
        ]);
    }

    private function isMailConfigured(): bool
    {
        if (($this->businessSettingService->getValueByType(type: 'mail_config')['status'] ?? 0) == 1) {
            return true;
        }

        return ($this->businessSettingService->getValueByType(type: 'mail_config_sendgrid')['status'] ?? 0) == 1;
    }

    private function maxOtpHit(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'maximum_otp_hit', default: self::DEFAULT_MAX_OTP_HIT);
    }

    private function isProfileComplete(AuthenticatableUser|Model $user): bool
    {
        return (string) ($user->f_name ?? '') !== ''
            && (string) ($user->phone ?? '') !== ''
            && (string) ($user->email ?? '') !== '';
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = trim((string) $phone);

        return $phone !== '' && ! str_starts_with($phone, '+') && ctype_digit(ltrim($phone, '+'))
            ? '+'.ltrim($phone, '+')
            : $phone;
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $firstName = array_shift($parts) ?? '';
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName];
    }

    private function toCustomer(AuthenticatableUser|Model $user): StorefrontCustomer
    {
        $imageUrl = ! empty($user->image)
            ? getStorageImages(path: $user->image_full_url ?? null, type: 'backend-profile')
            : null;

        return new StorefrontCustomer(
            id: (int) $user->id,
            firstName: $user->f_name,
            lastName: $user->l_name,
            email: $user->email,
            phone: $user->phone,
            imageUrl: $imageUrl,
            isPhoneVerified: (bool) $user->is_phone_verified,
            isEmailVerified: (bool) $user->is_email_verified,
            walletBalance: (float) webCurrencyConverterOnlyDigit(amount: (float) ($user->wallet_balance ?? 0)),
            loyaltyPoint: (float) ($user->loyalty_point ?? 0),
            refCode: $user->referral_code,
            createdAt: optional($user->created_at)->toIso8601String(),
        );
    }
}
