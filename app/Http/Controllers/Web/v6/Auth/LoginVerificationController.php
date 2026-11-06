<?php

namespace App\Http\Controllers\Web\v6\Auth;

use App\Enums\SessionKey;
use App\Enums\UserType;
use App\Events\EmailVerificationEvent;
use App\Http\Controllers\Controller;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\Auth\VerificationService;
use App\Services\New\BusinessSettingService;
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

class LoginVerificationController extends Controller
{
    use CustomerAuthRedirectTrait;

    private const RECAPTCHA_SESSION_KEY = 'default_recaptcha_id_customer_auth';

    private const RECAPTCHA_ACTION = 'customer_auth';

    private const PENDING_ACCOUNT_SESSION_KEY = 'tempCustomerInfo';

    private const VERIFICATION_TYPE_PASSWORD_RESET = 'password-reset';

    private const OTP_FLOW_PASSWORD_RESET = 'password-reset';

    private const OTP_FLOW_SOCIAL_LOGIN = 'social-login-verify';

    private const UPDATE_TYPE_OTP = 'otp';

    private const FIREBASE_UNKNOWN_ERROR = 'No specific error message';

    private const DEFAULT_MAX_OTP_HIT = 5;

    private const DEFAULT_RESEND_SECONDS = 60;

    private const DEFAULT_BLOCK_SECONDS = 600;

    private const MAIL_STATUS_ACTIVE = 1;

    private const RESEND_STATUS_SENT = 1;

    private const RESEND_STATUS_REFUSED = 0;

    private const RESEND_STATUS_MAIL_FAILED = '0';

    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly VerificationService $verificationService,
        private readonly PasswordResetService $passwordResetService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly LoginSetupService $loginSetupService,
        private readonly FirebaseOtpService $firebaseOtpService,
        private readonly CustomerService $customerService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly RestockProductService $restockProductService,
    ) {}

    public function index(Request $request): View
    {
        $otpResendTime = $this->viewResendSeconds();
        $otpFlow = base64_decode((string) $request['action']);

        return view(VIEW_FILE_NAMES['customer_auth_verify_otp_login'], [
            'userVerify' => 0,
            'identity' => $request['identity'],
            'otpResendTime' => $otpResendTime,
            'getTimeInSecond' => $this->secondsUntilResendAllowed(
                otpFlow: $otpFlow,
                identity: base64_decode((string) $request['identity']),
                resendSeconds: (int) $otpResendTime,
            ),
            'otpFromType' => $request['action'] ?? '',
            'otpVerifyUrl' => $this->otpVerifyUrl(otpFlow: $otpFlow),
            'otpResendUrl' => $this->otpResendUrl(otpFlow: $otpFlow),
        ]);
    }

    public function verify(Request $request): RedirectResponse|JsonResponse
    {
        $recaptchaFailure = $this->recaptchaFailureResponse(request: $request, answersJson: true);

        if ($recaptchaFailure !== null) {
            return $recaptchaFailure;
        }

        if (empty($request['token'])) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => translate('The token field is required'),
                ]);
            }

            ToastMagic::error(translate('The token field is required'));

            return redirect()->back();
        }

        $returnUrl = $this->resolveReturnUrl(request: $request);
        $identity = base64_decode((string) $request['identity']);
        $matchedVerification = $this->verificationService->findByIdentityAndToken(identity: $identity, token: (string) $request['token']);

        $attempt = $this->verificationService->registerLoginOtpAttempt(
            identity: $identity,
            maxAttempts: $this->maxOtpHit(),
            resendSeconds: $this->resendSeconds(),
            blockSeconds: $this->blockSeconds(),
        );

        if ($this->isAttemptRefused(attempt: $attempt)) {
            return $this->otpFailureResponse(request: $request, message: $this->attemptFailureMessage(attempt: $attempt));
        }

        $firebaseApiKey = $this->firebaseApiKey();

        if ($firebaseApiKey !== null) {
            $verification = $this->verificationService->findByIdentity(identity: $identity);
            $firebaseResult = $this->firebaseOtpService->verifyPhoneOtp(
                apiKey: $firebaseApiKey,
                sessionInfo: (string) $verification['token'],
                phoneNumber: (string) $verification['phone_or_email'],
                code: (string) $request['token'],
            );

            if (! $firebaseResult['verified']) {
                $this->verificationService->registerFirebaseOtpFailure(verification: $this->verificationService->findByIdentity(identity: $identity));
                ToastMagic::error($this->firebaseErrorMessage(error: $firebaseResult['error']));

                return back();
            }
        } elseif ($matchedVerification === null) {
            return $this->otpFailureResponse(request: $request, message: translate('OTP is not matched'));
        }

        $customer = $this->customerAuthService->findByIdentity(identity: $identity);

        if ($customer !== null) {
            $this->customerAuthService->markChannelVerified(id: $customer['id'], channel: LoginSetupService::CHANNEL_PHONE);
            $customer = $this->customerAuthService->findByIdentity(identity: $identity);
        }

        $this->verificationService->deleteByIdentity(identity: $identity);

        $updateInfoUrl = route('customer.auth.login.update-info', ['identity' => base64_encode($identity)]);

        if ($customer !== null && $request['type'] === self::VERIFICATION_TYPE_PASSWORD_RESET) {
            $this->signIn(customer: $customer);

            return redirect($returnUrl);
        }

        if ($customer !== null && $customer['name'] == null) {
            return redirect($updateInfoUrl);
        }

        if ($customer !== null && $customer['name']) {
            $this->signIn(customer: $customer);

            return redirect($returnUrl);
        }

        session()->put(self::PENDING_ACCOUNT_SESSION_KEY, $this->customerAuthService->buildPendingPhoneAccount(phone: $identity));

        return redirect($updateInfoUrl);
    }

    public function editInfo(Request $request): View
    {
        return view(VIEW_FILE_NAMES['customer_auth_verify_otp_update_info'], [
            'user' => $this->customerAuthService->findByPhone(phone: base64_decode((string) $request['identity'])),
            'tempUser' => session(self::PENDING_ACCOUNT_SESSION_KEY),
            'updateType' => self::UPDATE_TYPE_OTP,
            'identity' => $request['identity'],
        ]);
    }

    public function updateInfo(Request $request): RedirectResponse
    {
        $recaptchaFailure = $this->recaptchaFailureResponse(request: $request, answersJson: false);

        if ($recaptchaFailure !== null) {
            return $recaptchaFailure;
        }

        if (! empty($request['email']) && $this->customerAuthService->findByEmail(email: (string) $request['email']) !== null) {
            ToastMagic::error(translate('Email has already been taken'));

            return back();
        }

        $returnUrl = $this->resolveReturnUrl(request: $request);
        $phone = base64_decode((string) $request['identity']);

        if ($this->customerAuthService->findByPhone(phone: $phone) === null && ! session()->has(self::PENDING_ACCOUNT_SESSION_KEY)) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $customer = $this->customerAuthService->updateOrCreateProfileByPhone(
            phone: $phone,
            name: $request['name'],
            email: $request['email'],
            pendingAccount: session(self::PENDING_ACCOUNT_SESSION_KEY) ?? [],
        );

        $this->signIn(customer: $customer);
        ToastMagic::success(translate('Updated successfully'));

        return redirect($returnUrl);
    }

    public function resendOtp(Request $request): RedirectResponse|JsonResponse
    {
        $customer = $this->customerAuthService->findByPhone(phone: base64_decode((string) $request['identity']));

        if ($customer === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $otpResendTime = $this->viewResendSeconds();
        $resendWait = $this->verificationService->secondsUntilResendAllowed(identity: $customer['phone'], resendSeconds: (int) $otpResendTime);

        if ($resendWait > 0) {
            if ($request->ajax()) {
                return response()->json(['status' => self::RESEND_STATUS_REFUSED]);
            }

            ToastMagic::error(translate('Resend OTP failed'));

            return back();
        }

        $token = $this->verificationService->issueOtp(identity: $customer['phone']);

        if ($this->loginSetupService->isVerificationRequired(channel: LoginSetupService::CHANNEL_PHONE) && ! $customer['is_phone_verified']) {
            SMSModule::sendCentralizedSMS($customer['phone'], $token);
        }

        if ($this->loginSetupService->isVerificationRequired(channel: LoginSetupService::CHANNEL_EMAIL) && ! $customer['is_email_verified'] && ! $this->sendVerificationEmail(customer: $customer, token: $token)) {
            return response()->json(['status' => self::RESEND_STATUS_MAIL_FAILED]);
        }

        if ($request->ajax()) {
            return response()->json(['status' => self::RESEND_STATUS_SENT, 'new_time' => $otpResendTime]);
        }

        ToastMagic::success(translate('OTP resent successfully'));

        return back();
    }

    private function secondsUntilResendAllowed(string $otpFlow, string $identity, int $resendSeconds): int
    {
        if ($otpFlow === self::OTP_FLOW_PASSWORD_RESET) {
            return $this->passwordResetService->secondsUntilResendAllowed(
                identity: $identity,
                userType: UserType::CUSTOMER,
                resendSeconds: $resendSeconds,
            );
        }

        return $this->verificationService->secondsUntilResendAllowed(identity: $identity, resendSeconds: $resendSeconds);
    }

    private function otpVerifyUrl(string $otpFlow): string
    {
        return match ($otpFlow) {
            self::OTP_FLOW_SOCIAL_LOGIN => route('customer.auth.login.social.verify-account'),
            self::OTP_FLOW_PASSWORD_RESET => route('customer.auth.verify-recover-password'),
            default => route('customer.auth.login.verify-account.submit'),
        };
    }

    private function otpResendUrl(string $otpFlow): string
    {
        return $otpFlow === self::OTP_FLOW_PASSWORD_RESET
            ? route('customer.auth.resend-otp-reset-password')
            : route('customer.auth.resend-otp');
    }

    /** @param array{reason: ?string, retry_after: int} $attempt */
    private function isAttemptRefused(array $attempt): bool
    {
        return $attempt['reason'] !== null && $attempt['reason'] !== VerificationService::REASON_OTP_BLOCK_LIFTED;
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

        foreach ($sessionState['put'] as $key => $value) {
            session()->put($key, $value);
        }
    }

    private function resolveReturnUrl(Request $request): string
    {
        $this->keepCustomerAuthReturnUrl(requestedUrl: $request[SessionKey::CUSTOMER_LOGIN_REDIRECT_URL]);

        return $this->getCustomerAuthReturnUrl();
    }

    private function recaptchaFailureResponse(Request $request, bool $answersJson): RedirectResponse|JsonResponse|null
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

        if ($answersJson && $request->ajax()) {
            return response()->json([
                'error' => $result['message'],
                'default_captcha_required' => $result['fallback'],
            ]);
        }

        ToastMagic::error($result['message']);

        return back();
    }

    private function otpFailureResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->ajax()) {
            return response()->json(['status' => 0, 'message' => $message]);
        }

        ToastMagic::error($message);

        return redirect()->back();
    }

    /** @param array{reason: ?string, retry_after: int} $attempt */
    private function attemptFailureMessage(array $attempt): string
    {
        $retryAfter = CarbonInterval::seconds($attempt['retry_after'])->cascade()->forHumans();

        return match ($attempt['reason']) {
            VerificationService::REASON_OTP_BLOCKED => translate('Please try again after').$retryAfter,
            VerificationService::REASON_OTP_TOO_MANY_ATTEMPTS => translate('Too many attempts.').' '.translate('Please try again after').$retryAfter,
            default => translate('OTP is not matched'),
        };
    }

    private function firebaseErrorMessage(?string $error): string
    {
        return ucfirst(str_replace('_', ' ', strtolower($error ?? self::FIREBASE_UNKNOWN_ERROR)));
    }

    private function sendVerificationEmail(Model $customer, string $token): bool
    {
        $mailConfig = $this->businessSettingService->getValueByType(type: BusinessSettingService::MAIL_CONFIG, default: []);

        if (($mailConfig['status'] ?? 0) == 0) {
            $mailConfig = $this->businessSettingService->getValueByType(type: BusinessSettingService::MAIL_CONFIG_SENDGRID, default: []);
        }

        if (($mailConfig['status'] ?? 0) != self::MAIL_STATUS_ACTIVE) {
            return true;
        }

        try {
            event(new EmailVerificationEvent(email: (string) $customer['email'], data: [
                'userName' => $customer['f_name'],
                'subject' => translate('Registration Verification Code'),
                'title' => translate('Registration Verification Code'),
                'verificationCode' => $token,
                'userType' => 'customer',
                'templateName' => 'registration-verification',
            ]));
        } catch (Exception) {
            return false;
        }

        return true;
    }

    private function firebaseApiKey(): ?string
    {
        $firebaseOtp = $this->businessSettingService->getValueByType(type: BusinessSettingService::FIREBASE_OTP_VERIFICATION, default: []);

        if (! is_array($firebaseOtp) || empty($firebaseOtp['status'])) {
            return null;
        }

        $credentials = $this->businessSettingService->getValueByType(type: BusinessSettingService::FCM_CREDENTIALS, default: []);

        return (string) (is_array($credentials) ? ($credentials['apiKey'] ?? '') : '');
    }

    private function viewResendSeconds(): string|int
    {
        $resendSeconds = $this->businessSettingService->getValueByType(type: BusinessSettingService::OTP_RESEND_TIME);

        return $resendSeconds > 0 ? (string) $resendSeconds : 0;
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
