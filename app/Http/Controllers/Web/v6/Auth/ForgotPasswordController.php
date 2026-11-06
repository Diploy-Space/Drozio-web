<?php

namespace App\Http\Controllers\Web\v6\Auth;

use App\Enums\UserType;
use App\Events\OtpSmsEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Auth\ForgotPasswordRequest;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\BusinessSettingService;
use App\Services\New\FirebaseOtpService;
use App\Services\RecaptchaService;
use Carbon\CarbonInterval;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    private const RECAPTCHA_SESSION_KEY = 'default_recaptcha_id_customer_auth';

    private const RECAPTCHA_ACTION = 'customer_auth';

    private const IDENTITY_SESSION_KEY = 'forgot_password_identity';

    private const VERIFICATION_TYPE_PHONE = 'phone_verification';

    private const VERIFICATION_ACTION_PASSWORD_RESET = 'password-reset';

    private const DEFAULT_MAX_OTP_HIT = 5;

    private const DEFAULT_RESEND_SECONDS = 60;

    private const DEFAULT_BLOCK_SECONDS = 600;

    private const FIREBASE_UNKNOWN_ERROR = 'No specific error message';

    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly PasswordResetService $passwordResetService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly FirebaseOtpService $firebaseOtpService,
    ) {}

    public function index(): View
    {
        return view(VIEW_FILE_NAMES['recover_password'], [
            'verification_by' => $this->businessSettingService->getValueByType(type: BusinessSettingService::FORGOT_PASSWORD_VERIFICATION),
        ]);
    }

    public function sendResetCode(ForgotPasswordRequest $request): RedirectResponse|JsonResponse
    {
        $recaptchaFailure = $this->recaptchaFailureResponse(request: $request);

        if ($recaptchaFailure !== null) {
            return $recaptchaFailure;
        }

        $customer = $this->customerAuthService->findByPhone(phone: (string) $request['identity']);

        if (! $customer) {
            ToastMagic::error(translate('No such user found'));

            return back();
        }

        if ($this->customerAuthService->accountStatusReason(account: $customer) !== null) {
            ToastMagic::error(translate('Your account is deactivated'));

            return back();
        }

        session()->put(self::IDENTITY_SESSION_KEY, $request['identity']);

        $resendWait = $this->passwordResetService->secondsUntilResendAllowed(
            identity: (string) $request['identity'],
            userType: UserType::CUSTOMER,
            resendSeconds: $this->resendSeconds(),
        );

        if ($resendWait > 0) {
            ToastMagic::error(translate('Please try again after').CarbonInterval::seconds($resendWait)->cascade()->forHumans());

            return back();
        }

        $firebaseSession = $this->sendFirebaseOtp(
            phone: $customer['phone'],
            recaptchaToken: $request['g-recaptcha-response'] ?? session('g-recaptcha-response'),
        );

        if ($firebaseSession === false) {
            ToastMagic::error(translate('Something went wrong').' '.translate('Please try again after sometime'));

            return back();
        }

        $token = $this->passwordResetService->issueOtp(
            identity: $customer['phone'],
            userType: UserType::CUSTOMER,
            token: $firebaseSession,
        );

        if ($firebaseSession === null) {
            event(new OtpSmsEvent(phone: $customer['phone'], otp: $token));
        }

        ToastMagic::success(translate('Check your phone').' '.translate('Password reset OTP sent'));

        return redirect()->route('customer.auth.login.verify-account', [
            'identity' => base64_encode($customer['phone']),
            'type' => base64_encode(self::VERIFICATION_TYPE_PHONE),
            'action' => base64_encode(self::VERIFICATION_ACTION_PASSWORD_RESET),
        ]);
    }

    public function verifyResetCode(Request $request): View|RedirectResponse|JsonResponse
    {
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

        $recaptchaFailure = $this->recaptchaFailureResponse(request: $request);

        if ($recaptchaFailure !== null) {
            return $recaptchaFailure;
        }

        $identity = base64_decode((string) $request['identity']);
        $firebaseApiKey = $this->firebaseApiKey();

        $failure = $firebaseApiKey === null
            ? $this->otpFailureMessage(identity: $identity, otp: (string) $request['token'])
            : $this->firebaseOtpFailureMessage(identity: $identity, otp: (string) $request['token'], apiKey: $firebaseApiKey);

        if ($failure !== null) {
            if ($request->ajax()) {
                return response()->json(['status' => 0, 'message' => $failure]);
            }

            ToastMagic::error($failure);

            return redirect()->back();
        }

        return view(VIEW_FILE_NAMES['reset_password'], [
            'token' => $firebaseApiKey === null
                ? $request['token']
                : $this->passwordResetService->issueResetLinkToken(identity: $identity, userType: UserType::CUSTOMER),
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

    private function otpFailureMessage(string $identity, string $otp): ?string
    {
        $blockedMessage = $this->otpBlockedMessage(identity: $identity);

        if ($blockedMessage !== null) {
            return $blockedMessage;
        }

        $reason = $this->passwordResetService->verifyOtp(
            identity: $identity,
            userType: UserType::CUSTOMER,
            token: $otp,
            maxAttempts: $this->maxOtpHit(),
            expirySeconds: $this->businessSettingService->getOtpExpirySeconds(),
        );

        return match ($reason) {
            null => null,
            PasswordResetService::REASON_OTP_EXPIRED => translate('This OTP has expired. Please ask for a new one'),
            default => translate('OTP is not matched'),
        };
    }

    private function firebaseOtpFailureMessage(string $identity, string $otp, string $apiKey): ?string
    {
        $blockedMessage = $this->otpBlockedMessage(identity: $identity);

        if ($blockedMessage !== null) {
            return $blockedMessage;
        }

        $reset = $this->passwordResetService->findByIdentity(identity: $identity, userType: UserType::CUSTOMER);

        if ($reset === null) {
            return translate('OTP is not matched');
        }

        $firebaseResult = $this->firebaseOtpService->verifyPhoneOtp(
            apiKey: $apiKey,
            sessionInfo: (string) $reset->token,
            phoneNumber: $identity,
            code: $otp,
        );

        if ($firebaseResult['verified']) {
            return null;
        }

        $this->passwordResetService->registerOtpFailure(identity: $identity, userType: UserType::CUSTOMER, maxAttempts: $this->maxOtpHit());

        return ucfirst(str_replace('_', ' ', strtolower($firebaseResult['error'] ?? self::FIREBASE_UNKNOWN_ERROR)));
    }

    private function otpBlockedMessage(string $identity): ?string
    {
        $blockedSeconds = $this->passwordResetService->secondsUntilUnblocked(
            identity: $identity,
            userType: UserType::CUSTOMER,
            blockSeconds: $this->blockSeconds(),
        );

        return $blockedSeconds > 0
            ? translate('Please try again after').CarbonInterval::seconds($blockedSeconds)->cascade()->forHumans()
            : null;
    }

    private function sendFirebaseOtp(string $phone, ?string $recaptchaToken): string|false|null
    {
        $apiKey = $this->firebaseApiKey();

        if ($apiKey === null) {
            return null;
        }

        $response = $this->firebaseOtpService->sendPhoneOtp(apiKey: $apiKey, phoneNumber: $phone, recaptchaToken: $recaptchaToken);

        return $response['sent'] ? (string) $response['session_info'] : false;
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
