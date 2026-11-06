<?php

namespace App\Http\Controllers\Web\v6\Auth;

use App\Enums\SessionKey;
use App\Events\EmailVerificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Auth\SocialLoginConfirmRequest;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\Auth\VerificationService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CartService;
use App\Services\New\CustomerService;
use App\Services\New\FirebaseOtpService;
use App\Services\New\LoginSetupService;
use App\Services\New\ProductCompareService;
use App\Services\New\RestockProductService;
use App\Services\New\WishlistService;
use App\Services\RecaptchaService;
use App\Traits\CustomerAuthRedirectTrait;
use App\Utils\SMSModule;
use Carbon\CarbonInterval;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as ProviderRedirectResponse;

class SocialAuthController extends Controller
{
    use CustomerAuthRedirectTrait;

    private const PENDING_ACCOUNT_SESSION_KEY = 'social_login_new_customer';

    private const EMAIL_RELEASED_SESSION_KEY = 'socialLoginEmailRemovedForOldUser';

    private const GUEST_ID_KEY = 'guest_id';

    private const RECAPTCHA_SESSION_KEY = 'default_recaptcha_id_customer_auth';

    private const RECAPTCHA_ACTION = 'customer_auth';

    private const RECAPTCHA_TOKEN_KEY = 'g-recaptcha-response';

    private const CONFIRMATION_STATUS_APPROVE = 'approve';

    private const UPDATE_TYPE_SOCIAL = 'social';

    private const VERIFY_TYPE_PHONE = 'phone_verification';

    private const VERIFY_TYPE_EMAIL = 'email_verification';

    private const SOCIAL_LOGIN_VERIFY_ACTION = 'social-login-verify';

    private const EMAIL_TEMPLATE_REGISTRATION_VERIFICATION = 'registration-verification';

    private const EMAIL_USER_TYPE_CUSTOMER = 'customer';

    private const FIREBASE_VERIFY_ERROR_FALLBACK = 'No specific error message';

    private const OTP_ERROR_CODE = 'otp';

    private const LIVE_APP_MODE = 'live';

    private const OTP_DEV_TOKEN = '123456';

    private const OTP_MIN = 100000;

    private const OTP_MAX = 999999;

    private const DEFAULT_MAX_OTP_HIT = 5;

    private const DEFAULT_RESEND_SECONDS = 60;

    private const DEFAULT_BLOCK_SECONDS = 600;

    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly VerificationService $verificationService,
        private readonly LoginSetupService $loginSetupService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly FirebaseOtpService $firebaseOtpService,
        private readonly CustomerService $customerService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly RestockProductService $restockProductService,
        private readonly CartService $cartService,
    ) {}

    public function redirectToProvider(string $service): ProviderRedirectResponse
    {
        return Socialite::driver($service)->redirect();
    }

    public function handleCallback(Request $request, string $service): RedirectResponse|JsonResponse
    {
        $this->restoreAuthorizationCode(request: $request);

        try {
            $socialUser = Socialite::driver($service)->stateless()->user();
        } catch (Exception) {
            ToastMagic::error(translate('Login failed'));

            return redirect()->route('home');
        }

        $email = $socialUser->getEmail();
        $customer = $email === null ? null : $this->customerAuthService->findByEmail(email: $email);

        if ($customer === null || $customer['login_medium'] != $service) {
            $pendingAccount = $this->customerAuthService->buildPendingSocialAccount(
                medium: $service,
                socialId: (string) $socialUser->getId(),
                email: $email,
                name: $socialUser['name'],
            );

            session()->forget(self::EMAIL_RELEASED_SESSION_KEY);
            session()->put(self::PENDING_ACCOUNT_SESSION_KEY, $pendingAccount);

            return redirect()->route('customer.auth.social-login-confirmation', [
                'identity' => base64_encode((string) $email),
                'fullName' => base64_encode($pendingAccount['name']),
            ]);
        }

        $this->customerAuthService->attachSocialAccountByEmail(
            email: $email,
            medium: $service,
            socialId: (string) $socialUser->getId(),
        );

        return $this->completeSocialLogin(request: $request, customer: $customer);
    }

    public function confirmation(Request $request): View|RedirectResponse|JsonResponse
    {
        $customer = $this->customerAuthService->findByEmail(email: base64_decode((string) $request['identity']));
        $pendingAccount = session(self::PENDING_ACCOUNT_SESSION_KEY);

        if ($request['status'] == self::CONFIRMATION_STATUS_APPROVE && $customer) {
            $this->customerAuthService->attachSocialAccountByEmail(
                email: $customer['email'],
                medium: $pendingAccount['login_medium'] ?? null,
                socialId: isset($pendingAccount['social_id']) ? (string) $pendingAccount['social_id'] : null,
            );

            return $this->completeSocialLogin(request: $request, customer: $customer);
        }

        return view(themeViewName(key: 'customer_auth_verify_otp_update_info'), [
            'user' => $customer,
            'socialLoginNewCustomer' => $pendingAccount,
            'updateType' => self::UPDATE_TYPE_SOCIAL,
            'identity' => $request['identity'],
        ]);
    }

    public function updateConfirmation(SocialLoginConfirmRequest $request): RedirectResponse|JsonResponse
    {
        $identityEmail = base64_decode((string) $request['identity']);
        $pendingAccount = session(self::PENDING_ACCOUNT_SESSION_KEY);
        $phoneOwner = $this->customerAuthService->findByPhone(phone: (string) $request['phone']);

        if ($phoneOwner && $phoneOwner['email'] != $identityEmail) {
            ToastMagic::error(translate('Phone Number Already Exist'));

            return back();
        }

        if (! session()->has(self::EMAIL_RELEASED_SESSION_KEY)) {
            $this->customerAuthService->releaseEmail(email: $identityEmail);
            session()->put(self::EMAIL_RELEASED_SESSION_KEY, 1);
        }

        if (empty($pendingAccount) || ($pendingAccount['email'] ?? null) === null) {
            ToastMagic::error(translate('Invalid Request'));

            return back();
        }

        $account = $this->customerAuthService->buildPendingSocialAccountWithPhone(
            pendingAccount: $pendingAccount,
            name: $request['name'],
            phone: (string) $request['phone'],
        );

        session()->put(self::PENDING_ACCOUNT_SESSION_KEY, $account);
        $this->customerAuthService->updateOrCreateByEmail(email: $pendingAccount['email'], account: $account);

        if ($this->loginSetupService->isVerificationRequired(channel: LoginSetupService::CHANNEL_PHONE)) {
            return $this->sendLoginOtp(request: $request);
        }

        return $this->completeSocialLogin(
            request: $request,
            customer: $this->customerAuthService->findByEmail(email: $pendingAccount['email']),
        );
    }

    public function verify(Request $request): RedirectResponse|JsonResponse
    {
        $recaptcha = RecaptchaService::verificationStatus(
            request: $request,
            session: self::RECAPTCHA_SESSION_KEY,
            action: self::RECAPTCHA_ACTION,
            firebase: true,
        );

        if ($recaptcha && ! $recaptcha['status']) {
            if ($request->ajax()) {
                return response()->json([
                    'error' => $recaptcha['message'],
                    'default_captcha_required' => $recaptcha['fallback'],
                ]);
            }

            ToastMagic::error($recaptcha['message']);

            return back();
        }

        if (! $request->has('token') || empty($request['token'])) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => translate('The token field is required'),
                ]);
            }

            ToastMagic::error(translate('The token field is required'));

            return redirect()->back();
        }

        $identity = base64_decode((string) $request['identity']);
        $matchedVerification = $this->verificationService->findByIdentityAndToken(identity: $identity, token: (string) $request['token']);

        $attempt = $this->verificationService->registerLoginOtpAttempt(
            identity: $identity,
            maxAttempts: $this->maxOtpHit(),
            resendSeconds: $this->resendSeconds(),
            blockSeconds: $this->blockSeconds(),
        );

        if ($attempt['reason'] !== null) {
            return $this->otpFailureResponse(request: $request, message: $this->otpAttemptFailureMessage(attempt: $attempt));
        }

        if ($this->isFirebaseOtpEnabled()) {
            $verification = $this->verificationService->findByIdentity(identity: $identity);
            $firebaseResult = $this->firebaseOtpService->verifyPhoneOtp(
                apiKey: $this->firebaseApiKey(),
                sessionInfo: (string) $verification['token'],
                phoneNumber: (string) $verification['phone_or_email'],
                code: (string) $request['token'],
            );

            if (! $firebaseResult['verified']) {
                $this->verificationService->registerFirebaseOtpFailure(
                    verification: $this->verificationService->findByIdentity(identity: $identity),
                );
                ToastMagic::error($this->readableFirebaseError(error: $firebaseResult['error'] ?? self::FIREBASE_VERIFY_ERROR_FALLBACK));

                return back();
            }
        }

        $isTokenVerified = $this->isFirebaseOtpEnabled() || $matchedVerification !== null;

        $this->rememberCustomerAuthReturnUrl(request: $request);
        $returnUrl = $this->getCustomerAuthReturnUrl();

        $customer = $isTokenVerified ? $this->customerAuthService->findByIdentity(identity: $identity) : null;

        if ($customer === null) {
            return $this->otpFailureResponse(request: $request, message: translate('OTP is not matched'));
        }

        $this->customerAuthService->markChannelVerified(id: $customer['id'], channel: LoginSetupService::CHANNEL_PHONE);
        $this->verificationService->deleteByIdentity(identity: $identity);
        $this->signInCustomer(customer: $customer);

        return redirect($returnUrl);
    }

    private function restoreAuthorizationCode(Request $request): void
    {
        parse_str((string) parse_url($request->getRequestUri(), PHP_URL_QUERY), $query);
        $code = $query['code'] ?? null;

        if ($code && ! $request->has('code')) {
            $request->merge(['code' => $code]);
            $request->query->set('code', $code);
        }
    }

    private function completeSocialLogin(Request $request, Model $customer): RedirectResponse|JsonResponse
    {
        $isPhoneVerificationRequired = $this->loginSetupService->isVerificationRequired(channel: LoginSetupService::CHANNEL_PHONE);
        $isEmailVerificationRequired = $this->loginSetupService->isVerificationRequired(channel: LoginSetupService::CHANNEL_EMAIL);

        $this->rememberCustomerAuthReturnUrl(request: $request);
        $returnUrl = $this->getCustomerAuthReturnUrl();

        if ($customer['phone']) {
            $this->verificationService->deleteByIdentity(identity: $customer['phone']);
        }

        if ($customer['email']) {
            $this->verificationService->deleteByIdentity(identity: $customer['email']);
        }

        $isPhoneVerificationPending = $isPhoneVerificationRequired && ! $customer['is_phone_verified'];
        $isEmailVerificationPending = $isEmailVerificationRequired && ! $customer['is_email_verified'];

        if ($isPhoneVerificationPending || $isEmailVerificationPending) {
            $this->sendVerificationToken(request: $request, customer: $customer, isPhoneVerificationPending: $isPhoneVerificationPending);

            return $this->verificationPendingResponse(request: $request, customer: $customer, isPhoneVerificationPending: $isPhoneVerificationPending);
        }

        $message = translate('Credentials are not matched or your account is not active').'!';

        if ($customer->is_active) {
            $this->signInCustomer(customer: $customer);
            $this->moveGuestCart(request: $request, customerId: (int) $customer->id);
            $message = translate('Welcome to').' '.$this->businessSettingService->getValueByType(type: BusinessSettingService::COMPANY_NAME).'!';
        }

        ToastMagic::info($message);

        return redirect($returnUrl);
    }

    private function verificationPendingResponse(Request $request, Model $customer, bool $isPhoneVerificationPending): RedirectResponse|JsonResponse
    {
        $verificationUrl = route('customer.auth.check-verification', [
            'identity' => base64_encode((string) ($isPhoneVerificationPending ? $customer['phone'] : $customer['email'])),
            'type' => base64_encode($isPhoneVerificationPending ? self::VERIFY_TYPE_PHONE : self::VERIFY_TYPE_EMAIL),
        ]);
        $message = $isPhoneVerificationPending ? translate('Please verify your phone') : translate('Please verify your email');

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'redirect_url' => $verificationUrl,
            ]);
        }

        ToastMagic::success($message);

        return redirect($verificationUrl);
    }

    private function sendVerificationToken(Request $request, Model $customer, bool $isPhoneVerificationPending): void
    {
        $token = $this->generateOtpToken();

        if ($isPhoneVerificationPending && $this->isFirebaseOtpEnabled()) {
            $firebaseResult = $this->sendFirebaseOtp(request: $request, phone: (string) $customer['phone']);

            if (! $firebaseResult['sent']) {
                ToastMagic::error((string) $firebaseResult['error']);

                return;
            }

            $token = $firebaseResult['session_info'];
        } elseif ($isPhoneVerificationPending) {
            SMSModule::sendCentralizedSMS($customer['phone'], $token);
        } else {
            $emailFailureMessage = $this->sendEmailVerificationToken(customer: $customer, token: $token);

            if ($emailFailureMessage !== null) {
                ToastMagic::error($emailFailureMessage);

                return;
            }
        }

        $this->verificationService->updateOrCreateToken(
            identity: (string) ($isPhoneVerificationPending ? $customer['phone'] : $customer['email']),
            token: $token,
        );
    }

    private function sendEmailVerificationToken(Model $customer, string $token): ?string
    {
        $mailConfig = $this->businessSettingService->getValueByType(type: BusinessSettingService::MAIL_CONFIG, default: []);

        if (($mailConfig['status'] ?? 0) == 0) {
            $mailConfig = $this->businessSettingService->getValueByType(type: BusinessSettingService::MAIL_CONFIG_SENDGRID, default: []);
        }

        if (($mailConfig['status'] ?? 0) != 1 || ! $customer['email']) {
            return translate('Email failed');
        }

        try {
            event(new EmailVerificationEvent(email: $customer['email'], data: [
                'userName' => $customer['f_name'],
                'subject' => translate('Registration Verification Code'),
                'title' => translate('Registration Verification Code'),
                'verificationCode' => $token,
                'userType' => self::EMAIL_USER_TYPE_CUSTOMER,
                'templateName' => self::EMAIL_TEMPLATE_REGISTRATION_VERIFICATION,
            ]));
        } catch (Exception) {
            return translate('Email is not configured').'. '.translate('Contact with the administrator');
        }

        return null;
    }

    private function sendLoginOtp(Request $request): RedirectResponse|JsonResponse
    {
        $phone = (string) $request['phone'];
        $resendWaitSeconds = $this->verificationService->secondsUntilResendAllowed(identity: $phone, resendSeconds: $this->resendSeconds());

        if ($resendWaitSeconds > 0) {
            return response()->json([
                'errors' => [[
                    'code' => self::OTP_ERROR_CODE,
                    'message' => translate('Please try again after').$resendWaitSeconds.' '.translate('Seconds'),
                ]],
            ], 403);
        }

        $token = $this->generateOtpToken();

        if ($this->isFirebaseOtpEnabled()) {
            $firebaseResult = $this->sendFirebaseOtp(request: $request, phone: $phone);

            if (! $firebaseResult['sent']) {
                ToastMagic::error($this->readableFirebaseError(error: (string) $firebaseResult['error']));

                return redirect()->back();
            }

            $token = $firebaseResult['session_info'];
        } else {
            SMSModule::sendCentralizedSMS($phone, $token);
        }

        $this->verificationService->updateOrCreateToken(identity: $phone, token: $token);

        return redirect()->route('customer.auth.login.verify-account', [
            'identity' => base64_encode($phone),
            'action' => base64_encode(self::SOCIAL_LOGIN_VERIFY_ACTION),
        ]);
    }

    private function signInCustomer(Model $customer): void
    {
        auth('customer')->login($customer);

        $customerId = (int) $customer->id;
        $sessionState = $this->customerService->buildSignInSessionState(
            wishListProductIds: $this->wishlistService->getProductIds(customerId: $customerId),
            compareListProductIds: $this->productCompareService->getProductIds(customerId: $customerId),
            restockFcmTopics: $this->restockProductService->getCustomerFcmTopics(customerId: $customerId),
        );

        session()->forget($sessionState['forget']);
        session()->put($sessionState['put']);
    }

    private function moveGuestCart(Request $request, int $customerId): void
    {
        if (session()->has(self::GUEST_ID_KEY) || $request[self::GUEST_ID_KEY] !== null) {
            $this->cartService->moveGuestCartToCustomer(
                guestId: session(self::GUEST_ID_KEY) ?? $request[self::GUEST_ID_KEY],
                customerId: $customerId,
            );
        }
    }

    private function rememberCustomerAuthReturnUrl(Request $request): void
    {
        $this->keepCustomerAuthReturnUrl(requestedUrl: $request[SessionKey::CUSTOMER_LOGIN_REDIRECT_URL]);
    }

    private function otpFailureResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->ajax()) {
            return response()->json([
                'status' => 0,
                'message' => $message,
            ]);
        }

        ToastMagic::error($message);

        return redirect()->back();
    }

    private function otpAttemptFailureMessage(array $attempt): string
    {
        $retryAfter = CarbonInterval::seconds($attempt['retry_after'])->cascade()->forHumans();

        return match ($attempt['reason']) {
            VerificationService::REASON_OTP_BLOCKED => translate('Please try again after').$retryAfter,
            VerificationService::REASON_OTP_TOO_MANY_ATTEMPTS => translate('Too many attempts.').' '.translate('Please try again after').$retryAfter,
            default => translate('OTP is not matched'),
        };
    }

    private function sendFirebaseOtp(Request $request, string $phone): array
    {
        return $this->firebaseOtpService->sendPhoneOtp(
            apiKey: $this->firebaseApiKey(),
            phoneNumber: $phone,
            recaptchaToken: $request[self::RECAPTCHA_TOKEN_KEY] ?? session(self::RECAPTCHA_TOKEN_KEY),
        );
    }

    private function readableFirebaseError(string $error): string
    {
        return ucfirst(str_replace('_', ' ', strtolower($error)));
    }

    private function generateOtpToken(): string
    {
        return config('app.mode') === self::LIVE_APP_MODE ? (string) rand(self::OTP_MIN, self::OTP_MAX) : self::OTP_DEV_TOKEN;
    }

    private function isFirebaseOtpEnabled(): bool
    {
        $firebaseOtp = $this->businessSettingService->getValueByType(type: BusinessSettingService::FIREBASE_OTP_VERIFICATION, default: []);

        return is_array($firebaseOtp) && ! empty($firebaseOtp['status']);
    }

    private function firebaseApiKey(): string
    {
        $fcmCredentials = $this->businessSettingService->getValueByType(type: BusinessSettingService::FCM_CREDENTIALS, default: []);

        return (string) (is_array($fcmCredentials) ? ($fcmCredentials['apiKey'] ?? '') : '');
    }

    private function maxOtpHit(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: BusinessSettingService::MAXIMUM_OTP_HIT, default: self::DEFAULT_MAX_OTP_HIT);
    }

    private function resendSeconds(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: BusinessSettingService::OTP_RESEND_TIME, default: self::DEFAULT_RESEND_SECONDS);
    }

    private function blockSeconds(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: BusinessSettingService::TEMPORARY_BLOCK_TIME, default: self::DEFAULT_BLOCK_SECONDS);
    }
}
