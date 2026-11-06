<?php

namespace App\Http\Controllers\Web\v6\Auth;

use App\Enums\SessionKey;
use App\Enums\UserType;
use App\Events\EmailVerificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Auth\LoginByEmailOrPhoneRequest;
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

class LoginController extends Controller
{
    use CustomerAuthRedirectTrait;

    private const RECAPTCHA_SESSION_KEY = 'default_recaptcha_id_customer_auth';

    private const RECAPTCHA_ACTION = 'customer_auth';

    private const PENDING_PHONE_ACCOUNT_SESSION_KEY = 'tempCustomerInfo';

    private const GUEST_ID_SESSION_KEY = 'guest_id';

    private const CUSTOMER_FCM_TOPIC_SESSION_KEY = 'customer_fcm_topic';

    private const DEFAULT_THEME = 'default';

    private const LOGIN_VIEW = 'web-views.customer-views.auth.login';

    private const VERIFICATION_TYPE_PHONE = 'phone_verification';

    private const VERIFICATION_TYPE_EMAIL = 'email_verification';

    private const EMAIL_VERIFICATION_TEMPLATE = 'registration-verification';

    private const SEND_SUCCESS = 'success';

    private const SEND_ERROR = 'error';

    private const LIVE_APP_MODE = 'live';

    private const DEV_APP_MODE = 'dev';

    private const DEV_OTP_TOKEN = '123456';

    private const OTP_TOKEN_MIN = 100000;

    private const OTP_TOKEN_MAX = 999999;

    private const DEFAULT_MAX_LOGIN_HIT = 5;

    private const DEFAULT_LOGIN_BLOCK_SECONDS = 5;

    private const DEFAULT_OTP_RESEND_SECONDS = 60;

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

    public function authenticationFailed(): JsonResponse
    {
        return response()->json([
            'errors' => [
                ['code' => 'auth-001', 'message' => 'Unauthorized.'],
            ],
        ], 401);
    }

    public function index(): View|RedirectResponse
    {
        if (theme_root_path() !== self::DEFAULT_THEME) {
            return redirect($this->getCustomerAuthReturnUrl());
        }

        $this->keepCustomerAuthReturnUrl();

        return view(self::LOGIN_VIEW, [
            'keepCustomerLoginRedirectUrl' => session(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL),
        ]);
    }

    public function login(LoginByEmailOrPhoneRequest $request): JsonResponse|RedirectResponse
    {
        $recaptchaFailure = $this->recaptchaFailureResponse(request: $request);

        if ($recaptchaFailure !== null) {
            return $recaptchaFailure;
        }

        $this->storeLoginReturnUrl(request: $request);
        $returnUrl = $this->getCustomerAuthReturnUrl();

        session()->forget(self::PENDING_PHONE_ACCOUNT_SESSION_KEY);

        if ($request->isOtpLogin()) {
            return $this->loginByOtp(request: $request);
        }

        if ($request->isManualLogin()) {
            return $this->loginByEmailOrPhone(request: $request, returnUrl: $returnUrl);
        }

        return redirect($returnUrl);
    }

    public function logout(Request $request): RedirectResponse
    {
        $returnUrl = config('app.theme') === self::DEFAULT_THEME
            ? $this->getCustomerAuthReturnUrl()
            : $this->getCustomerLogoutReturnUrl(request: $request);

        $customerId = auth('customer')->id();

        if ($customerId !== null) {
            $this->customerAuthService->clearWebFirebaseToken(id: $customerId);
        }

        auth('customer')->logout();

        session()->forget(SessionKey::WISH_LIST);
        session()->forget(self::CUSTOMER_FCM_TOPIC_SESSION_KEY);

        ToastMagic::success(translate('Come back soon').'!');

        return redirect($returnUrl);
    }

    public function getModalData(): JsonResponse
    {
        return response()->json([
            'login_modal' => view(themeViewName(key: 'get_login_modal_data'))->render(),
            'register_modal' => view(themeViewName(key: 'get_register_modal_data'))->render(),
        ]);
    }

    private function loginByOtp(LoginByEmailOrPhoneRequest $request): JsonResponse|RedirectResponse
    {
        $phone = (string) $request['phone'];

        $resendWait = $this->verificationService->secondsUntilResendAllowed(
            identity: $phone,
            resendSeconds: (int) $this->businessSettingService->getValueByType(type: BusinessSettingService::OTP_RESEND_TIME, default: self::DEFAULT_OTP_RESEND_SECONDS),
        );

        if ($resendWait > 0) {
            $message = translate('Please try again after').$resendWait.' '.translate('Seconds');

            if ($request->ajax()) {
                return response()->json(['status' => 0, 'message' => $message]);
            }

            ToastMagic::error($message);

            return redirect()->back();
        }

        $delivery = $this->sendLoginOtp(phone: $phone, recaptchaToken: $request['g-recaptcha-response'] ?? session('g-recaptcha-response'));

        if ($delivery['status'] === self::SEND_SUCCESS) {
            $this->verificationService->updateOrCreateToken(identity: $phone, token: $delivery['token']);
        }

        $verifyUrl = route('customer.auth.login.verify-account', [
            'identity' => base64_encode($phone),
            'type' => base64_encode(self::VERIFICATION_TYPE_PHONE),
        ]);

        if ($request->ajax()) {
            return response()->json($delivery['status'] === self::SEND_SUCCESS
                ? ['status' => self::SEND_SUCCESS, 'redirect_url' => $verifyUrl, 'message' => translate('Please Verify that its you')]
                : ['status' => self::SEND_ERROR, 'message' => $delivery['message']]);
        }

        if ($delivery['status'] === self::SEND_SUCCESS) {
            return redirect($verifyUrl);
        }

        ToastMagic::error($delivery['message']);

        return redirect()->back();
    }

    private function loginByEmailOrPhone(LoginByEmailOrPhoneRequest $request, string $returnUrl): JsonResponse|RedirectResponse
    {
        $identity = (string) $request['user_identity'];
        $maxLoginHit = (int) $this->businessSettingService->getValueByType(type: BusinessSettingService::MAXIMUM_LOGIN_HIT, default: self::DEFAULT_MAX_LOGIN_HIT);
        $blockSeconds = (int) $this->businessSettingService->getValueByType(type: BusinessSettingService::TEMPORARY_LOGIN_BLOCK_TIME, default: self::DEFAULT_LOGIN_BLOCK_SECONDS);
        $customer = $this->customerAuthService->findByIdentity(identity: $identity);

        if ($customer === null || ! $this->customerAuthService->verifyPassword(account: $customer, password: (string) $request['password'])) {
            if ($customer !== null) {
                $this->customerAuthService->registerLoginFailure(
                    identity: $identity,
                    account: $customer,
                    maxAttempts: $maxLoginHit,
                    blockSeconds: $blockSeconds,
                );
            }

            return $this->loginFailure(request: $request, message: $this->incorrectCredentialsMessage(identity: $identity));
        }

        $retryAfter = $this->customerAuthService->secondsUntilLoginAllowed(
            identity: $identity,
            account: $customer,
            maxAttempts: $maxLoginHit,
            blockSeconds: $blockSeconds,
        );

        if ($retryAfter > 0) {
            return $this->loginFailure(
                request: $request,
                message: translate('Too many attempts. Please try again after').CarbonInterval::seconds($retryAfter)->cascade()->forHumans(),
            );
        }

        $pendingChannel = $this->customerAuthService->pendingVerificationChannel(
            account: $customer,
            requiredChannel: $this->loginSetupService->requiredVerificationChannel(),
        );

        if ($pendingChannel !== null) {
            return $this->pendingVerificationResponse(request: $request, customer: $customer, channel: $pendingChannel);
        }

        if ($this->customerAuthService->accountStatusReason(account: $customer) !== null) {
            return $this->loginFailure(request: $request, message: translate('Your account is suspended'));
        }

        auth('customer')->login($customer, (bool) $request['remember']);
        $this->customerAuthService->clearLoginFailures(identity: $identity, account: $customer);

        $this->hydrateSignInSession(customerId: (int) auth('customer')->id());

        if (session()->has(self::GUEST_ID_SESSION_KEY)) {
            $this->cartService->moveGuestCartToCustomer(guestId: session(self::GUEST_ID_SESSION_KEY), customerId: (int) auth('customer')->id());
        }

        if ($request->ajax()) {
            return response()->json([
                'status' => self::SEND_SUCCESS,
                'message' => translate('Login successful'),
                'redirect_url' => $returnUrl,
            ]);
        }

        ToastMagic::success(translate('Welcome to').' '.$this->businessSettingService->getValueByType(type: BusinessSettingService::COMPANY_NAME).'!');

        return redirect($returnUrl);
    }

    private function pendingVerificationResponse(LoginByEmailOrPhoneRequest $request, Model $customer, string $channel): JsonResponse|RedirectResponse
    {
        $isPhone = $channel === LoginSetupService::CHANNEL_PHONE;

        $this->sendPendingVerificationCode(customer: $customer, isPhone: $isPhone, recaptchaToken: $request['g-recaptcha-response'] ?? session('g-recaptcha-response'));

        $redirectUrl = route('customer.auth.check-verification', [
            'identity' => base64_encode((string) ($isPhone ? $customer['phone'] : $customer['email'])),
            'type' => base64_encode($isPhone ? self::VERIFICATION_TYPE_PHONE : self::VERIFICATION_TYPE_EMAIL),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'status' => self::SEND_SUCCESS,
                'message' => $isPhone ? translate('Please verify your phone') : translate('Please verify your email'),
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl);
    }

    private function sendPendingVerificationCode(Model $customer, bool $isPhone, ?string $recaptchaToken): void
    {
        $delivery = null;
        $token = $this->generateOtpToken();
        $isPhonePending = $isPhone && $customer['phone'] && ! $customer['is_phone_verified'];

        if ($isPhonePending && $this->isFirebaseOtpEnabled()) {
            $firebase = $this->sendFirebaseOtp(phone: (string) $customer['phone'], recaptchaToken: $recaptchaToken);
            $delivery = $firebase['sent']
                ? ['status' => self::SEND_SUCCESS, 'token' => (string) $firebase['session_info']]
                : ['status' => self::SEND_ERROR, 'message' => translate('Something went wrong')];
        } elseif ($isPhonePending) {
            SMSModule::sendCentralizedSMS($customer['phone'], $token);
            $delivery = ['status' => self::SEND_SUCCESS, 'token' => $token];
        } elseif (! $isPhone && $customer['email'] && ! $customer['is_email_verified']) {
            $delivery = $this->sendEmailVerificationCode(customer: $customer, token: $token);
        }

        if ($delivery === null) {
            return;
        }

        if ($delivery['status'] === self::SEND_ERROR) {
            ToastMagic::error($delivery['message']);

            return;
        }

        $this->verificationService->updateOrCreateToken(
            identity: (string) ($isPhonePending ? $customer['phone'] : $customer['email']),
            token: $delivery['token'],
        );
    }

    private function sendEmailVerificationCode(Model $customer, string $token): array
    {
        $mailConfig = $this->businessSettingService->getValueByType(type: BusinessSettingService::MAIL_CONFIG, default: []);

        if (($mailConfig['status'] ?? 0) == 0) {
            $mailConfig = $this->businessSettingService->getValueByType(type: BusinessSettingService::MAIL_CONFIG_SENDGRID, default: []);
        }

        if (($mailConfig['status'] ?? 0) != 1) {
            return ['status' => self::SEND_ERROR, 'message' => translate('Email failed')];
        }

        try {
            event(new EmailVerificationEvent(email: $customer['email'], data: [
                'userName' => $customer['f_name'],
                'subject' => translate('Registration Verification Code'),
                'title' => translate('Registration Verification Code'),
                'verificationCode' => $token,
                'userType' => UserType::CUSTOMER,
                'templateName' => self::EMAIL_VERIFICATION_TEMPLATE,
            ]));
        } catch (Exception) {
            return ['status' => self::SEND_ERROR, 'message' => translate('Email is not configured').'. '.translate('Contact with the administrator')];
        }

        return ['status' => self::SEND_SUCCESS, 'token' => $token];
    }

    private function sendLoginOtp(string $phone, ?string $recaptchaToken): array
    {
        $token = $this->generateOtpToken();

        if ($this->isFirebaseOtpEnabled()) {
            $firebase = $this->sendFirebaseOtp(phone: $phone, recaptchaToken: $recaptchaToken);

            return $firebase['sent']
                ? ['status' => self::SEND_SUCCESS, 'token' => (string) $firebase['session_info']]
                : ['status' => self::SEND_ERROR, 'message' => ucfirst(str_replace('_', ' ', strtolower((string) $firebase['error'])))];
        }

        $smsResponse = SMSModule::sendCentralizedSMS($phone, $token);

        $isSent = $smsResponse === self::SEND_SUCCESS || config('app.mode') == self::DEV_APP_MODE;

        return $isSent
            ? ['status' => self::SEND_SUCCESS, 'token' => $token]
            : ['status' => self::SEND_ERROR, 'message' => translate('OTP sending failed')];
    }

    private function sendFirebaseOtp(string $phone, ?string $recaptchaToken): array
    {
        $credentials = $this->businessSettingService->getValueByType(type: BusinessSettingService::FCM_CREDENTIALS, default: []);

        return $this->firebaseOtpService->sendPhoneOtp(
            apiKey: (string) (is_array($credentials) ? ($credentials['apiKey'] ?? '') : ''),
            phoneNumber: $phone,
            recaptchaToken: $recaptchaToken,
        );
    }

    private function isFirebaseOtpEnabled(): bool
    {
        $firebaseOtp = $this->businessSettingService->getValueByType(type: BusinessSettingService::FIREBASE_OTP_VERIFICATION, default: []);

        return is_array($firebaseOtp) && ! empty($firebaseOtp['status']);
    }

    private function generateOtpToken(): string
    {
        return config('app.mode') == self::LIVE_APP_MODE
            ? (string) rand(self::OTP_TOKEN_MIN, self::OTP_TOKEN_MAX)
            : self::DEV_OTP_TOKEN;
    }

    private function hydrateSignInSession(int $customerId): void
    {
        $sessionState = $this->customerService->buildSignInSessionState(
            wishListProductIds: $this->wishlistService->getProductIds(customerId: $customerId),
            compareListProductIds: $this->productCompareService->getProductIds(customerId: $customerId),
            restockFcmTopics: $this->restockProductService->getCustomerFcmTopics(customerId: $customerId),
        );

        session()->forget($sessionState['forget']);

        foreach ($sessionState['put'] as $key => $value) {
            session()->put($key, $value);
        }
    }

    private function storeLoginReturnUrl(Request $request): void
    {
        $this->keepCustomerAuthReturnUrl(requestedUrl: $request[SessionKey::CUSTOMER_LOGIN_REDIRECT_URL]);
    }

    private function incorrectCredentialsMessage(string $identity): string
    {
        return filter_var($identity, FILTER_VALIDATE_EMAIL) !== false
            ? translate('Incorrect email or password. Please try again')
            : translate('Incorrect phone number or password. Please try again');
    }

    private function loginFailure(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->ajax()) {
            return response()->json([
                'status' => self::SEND_ERROR,
                'message' => $message,
                'redirect_url' => '',
            ]);
        }

        ToastMagic::error($message);

        return back()->withInput();
    }

    private function recaptchaFailureResponse(Request $request): JsonResponse|RedirectResponse|null
    {
        $result = RecaptchaService::verificationStatus(
            request: $request,
            session: self::RECAPTCHA_SESSION_KEY,
            action: self::RECAPTCHA_ACTION,
            firebase: true,
        );

        if (! $result || $result['status']) {
            return null;
        }

        if ($request->ajax()) {
            return response()->json([
                'error' => $result['message'],
                'default_captcha_required' => $result['fallback'],
            ]);
        }

        ToastMagic::error($result['message']);

        return back();
    }
}
