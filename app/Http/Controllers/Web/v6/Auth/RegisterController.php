<?php

namespace App\Http\Controllers\Web\v6\Auth;

use App\Enums\SessionKey;
use App\Enums\UserType;
use App\Events\CustomerRegisteredViaReferralEvent;
use App\Events\EmailVerificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Auth\RegistrationRequest;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\Auth\VerificationService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CustomerService;
use App\Services\New\FirebaseOtpService;
use App\Services\New\LoginSetupService;
use App\Services\New\ProductCompareService;
use App\Services\New\ReferralCustomerService;
use App\Services\New\RestockProductService;
use App\Services\New\WishlistService;
use App\Services\RecaptchaService;
use App\Traits\CustomerAuthRedirectTrait;
use App\Utils\SMSModule;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    use CustomerAuthRedirectTrait;

    private const RECAPTCHA_SESSION_KEY = 'default_recaptcha_id_customer_auth';

    private const RECAPTCHA_ACTION = 'customer_auth';

    private const VERIFICATION_TYPE_PHONE = 'phone_verification';

    private const VERIFICATION_TYPE_EMAIL = 'email_verification';

    private const REGISTRATION_EMAIL_TEMPLATE = 'registration-verification';

    private const MAIL_STATUS_ACTIVE = 1;

    private const DEFAULT_THEME = 'default';

    private const REGISTER_VIEW = 'web-views.customer-views.auth.register';

    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly CustomerService $customerService,
        private readonly VerificationService $verificationService,
        private readonly ReferralCustomerService $referralCustomerService,
        private readonly LoginSetupService $loginSetupService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly FirebaseOtpService $firebaseOtpService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly RestockProductService $restockProductService,
    ) {}

    public function index(): View|RedirectResponse
    {
        if (config('app.theme') !== self::DEFAULT_THEME) {
            return redirect($this->getCustomerAuthReturnUrl());
        }

        $this->keepCustomerAuthReturnUrl();

        return view(self::REGISTER_VIEW, [
            'recaptcha' => $this->businessSettingService->getValueByType(type: BusinessSettingService::RECAPTCHA),
            'keepCustomerLoginRedirectUrl' => session(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL),
        ]);
    }

    public function register(RegistrationRequest $request): JsonResponse|RedirectResponse
    {
        $recaptchaFailure = $this->recaptchaFailureResponse(request: $request);

        if ($recaptchaFailure !== null) {
            return $recaptchaFailure;
        }

        $this->keepCustomerAuthReturnUrl(requestedUrl: $request[SessionKey::CUSTOMER_LOGIN_REDIRECT_URL]);

        $referrer = $request['referral_code']
            ? $this->customerAuthService->findByReferralCode(code: (string) $request['referral_code'])
            : null;

        $customer = DB::transaction(function () use ($request, $referrer): Model {
            $customer = $this->customerAuthService->create(
                data: $request->validated() + ['referred_by' => $referrer?->id],
            );

            $this->rewardReferrer(customerId: $customer->id, referrer: $referrer);

            return $customer;
        });

        $pendingChannel = $this->pendingVerificationChannel(customer: $customer);

        if ($pendingChannel === null) {
            $this->signIn(customer: $customer);

            if ($request->ajax()) {
                return response()->json([
                    'status' => 1,
                    'message' => translate('Registration successful'),
                    'redirect_url' => $this->getCustomerAuthReturnUrl(),
                ]);
            }

            ToastMagic::success(translate('Registration successful'));

            return redirect($this->getCustomerAuthReturnUrl());
        }

        $identity = $pendingChannel === LoginSetupService::CHANNEL_PHONE ? $customer['phone'] : $customer['email'];

        $this->verificationService->deleteByIdentity(identity: $identity);

        $this->sendVerificationOtp(
            customer: $customer,
            channel: $pendingChannel,
            recaptchaToken: $request['g-recaptcha-response'] ?? session('g-recaptcha-response'),
        );

        $verificationUrl = route('customer.auth.check-verification', [
            'identity' => base64_encode($identity),
            'type' => base64_encode($pendingChannel === LoginSetupService::CHANNEL_PHONE ? self::VERIFICATION_TYPE_PHONE : self::VERIFICATION_TYPE_EMAIL),
        ]);

        if ($request->ajax()) {
            return response()->json(['redirect_url' => $verificationUrl]);
        }

        return redirect($verificationUrl);
    }

    public function checkVerification(Request $request): View
    {
        $customer = $this->customerAuthService->findByIdentity(identity: base64_decode((string) $request['identity']));

        abort_if($customer === null, 404);

        $pendingChannel = $this->pendingVerificationChannel(customer: $customer);

        $otpIdentity = base64_decode((string) $request['type']) === self::VERIFICATION_TYPE_PHONE ? $customer['phone'] : $customer['email'];

        return view(VIEW_FILE_NAMES['customer_auth_verify'], [
            'user' => $customer,
            'user_verify' => $pendingChannel === null ? 1 : 0,
            'verifyType' => $pendingChannel ?? '',
            'get_time' => $otpIdentity ? $this->verificationService->secondsUntilResendAllowed(
                identity: $otpIdentity,
                resendSeconds: max(0, (int) $this->businessSettingService->getValueByType(type: BusinessSettingService::OTP_RESEND_TIME, default: 0)),
            ) : 0,
        ]);
    }

    private function recaptchaFailureResponse(Request $request): RedirectResponse|JsonResponse|null
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

    private function rewardReferrer(int $customerId, ?Model $referrer): void
    {
        $referralConfig = $this->businessSettingService->getValueByType(type: BusinessSettingService::REFERRAL_EARNING_CUSTOMER, default: []);

        if ($referrer === null || (int) ($referralConfig['ref_earning_discount_status'] ?? 0) !== 1) {
            return;
        }

        $referral = $this->referralCustomerService->create(data: $referralConfig + [
            'user_id' => $customerId,
            'refer_by' => $referrer->id,
            'ref_by_earning_amount' => $this->businessSettingService->getValueByType(type: BusinessSettingService::REFERRAL_EARNING_EXCHANGE_RATE, default: 0),
        ]);

        event(new CustomerRegisteredViaReferralEvent($referral, $referrer));
    }

    private function pendingVerificationChannel(Model $customer): ?string
    {
        if ($this->loginSetupService->isVerificationRequired(channel: LoginSetupService::CHANNEL_PHONE) && ! $customer['is_phone_verified']) {
            return LoginSetupService::CHANNEL_PHONE;
        }

        if ($this->loginSetupService->isVerificationRequired(channel: LoginSetupService::CHANNEL_EMAIL) && ! $customer['is_email_verified']) {
            return LoginSetupService::CHANNEL_EMAIL;
        }

        return null;
    }

    private function sendVerificationOtp(Model $customer, string $channel, ?string $recaptchaToken): void
    {
        if ($channel === LoginSetupService::CHANNEL_EMAIL) {
            $this->sendEmailOtp(customer: $customer);

            return;
        }

        if ($this->isFirebaseOtpEnabled()) {
            $this->sendFirebaseOtp(phone: $customer['phone'], recaptchaToken: $recaptchaToken);

            return;
        }

        $token = $this->verificationService->issueOtp(identity: $customer['phone']);

        SMSModule::sendCentralizedSMS($customer['phone'], $token);

        ToastMagic::success(translate('Please check your SMS for OTP'));
    }

    private function sendFirebaseOtp(string $phone, ?string $recaptchaToken): void
    {
        $credentials = $this->businessSettingService->getValueByType(type: BusinessSettingService::FCM_CREDENTIALS, default: []);

        $response = $this->firebaseOtpService->sendPhoneOtp(
            apiKey: (string) (is_array($credentials) ? ($credentials['apiKey'] ?? '') : ''),
            phoneNumber: $phone,
            recaptchaToken: $recaptchaToken,
        );

        if (! $response['sent']) {
            ToastMagic::error(ucfirst(str_replace('_', ' ', strtolower((string) $response['error']))));

            return;
        }

        $this->verificationService->issueOtp(identity: $phone, token: (string) $response['session_info']);
    }

    private function sendEmailOtp(Model $customer): void
    {
        if (! $this->isMailConfigured() || ! $customer['email']) {
            ToastMagic::error(translate('Email failed'));

            return;
        }

        $token = $this->verificationService->issueOtp(identity: $customer['email']);

        try {
            event(new EmailVerificationEvent(email: $customer['email'], data: [
                'userName' => $customer['f_name'],
                'subject' => translate('Registration Verification Code'),
                'title' => translate('Registration Verification Code'),
                'verificationCode' => $token,
                'userType' => UserType::CUSTOMER,
                'templateName' => self::REGISTRATION_EMAIL_TEMPLATE,
            ]));
        } catch (Exception) {
            $this->verificationService->deleteByIdentity(identity: $customer['email']);

            ToastMagic::error(translate('Email is not configured').'. '.translate('Contact with the administrator'));
        }
    }

    private function isFirebaseOtpEnabled(): bool
    {
        $firebaseOtp = $this->businessSettingService->getValueByType(type: BusinessSettingService::FIREBASE_OTP_VERIFICATION, default: []);

        return is_array($firebaseOtp) && ! empty($firebaseOtp['status']);
    }

    private function isMailConfigured(): bool
    {
        $mailConfig = $this->businessSettingService->getValueByType(type: BusinessSettingService::MAIL_CONFIG, default: []);

        if ((int) ($mailConfig['status'] ?? 0) === 0) {
            $mailConfig = $this->businessSettingService->getValueByType(type: BusinessSettingService::MAIL_CONFIG_SENDGRID, default: []);
        }

        return (int) ($mailConfig['status'] ?? 0) === self::MAIL_STATUS_ACTIVE;
    }

    private function signIn(Model $customer): void
    {
        auth('customer')->login($customer);

        $customerId = (int) auth('customer')->id();

        $sessionState = $this->customerService->buildSignInSessionState(
            wishListProductIds: $this->wishlistService->getProductIds(customerId: $customerId),
            compareListProductIds: $this->productCompareService->getProductIds(customerId: $customerId),
            restockFcmTopics: $this->restockProductService->getCustomerFcmTopics(customerId: $customerId),
        );

        session()->forget($sessionState['forget']);
        session()->put($sessionState['put']);
    }
}
