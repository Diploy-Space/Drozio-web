<?php

namespace App\Http\Controllers\Web\v6\Auth;

use App\Enums\SessionKey;
use App\Enums\UserType;
use App\Events\OtpSmsEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Auth\PasswordResetRequest;
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

class PasswordResetController extends Controller
{
    private const DEFAULT_MAX_OTP_HIT = 5;

    private const DEFAULT_RESEND_SECONDS = 60;

    private const DEFAULT_BLOCK_SECONDS = 600;

    private const RECAPTCHA_SESSION_KEY = 'default_recaptcha_id_customer_auth';

    private const RECAPTCHA_ACTION = 'customer_auth';

    private const RECAPTCHA_TOKEN_KEY = 'g-recaptcha-response';

    private const VERIFIED_CHANNEL_EMAIL = 'email';

    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly PasswordResetService $passwordResetService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly FirebaseOtpService $firebaseOtpService,
    ) {}

    public function showOtpForm(Request $request): View|RedirectResponse
    {
        $reset = $this->passwordResetService->findByIdentity(
            identity: (string) $request['identity'],
            userType: UserType::CUSTOMER,
        );

        if ($reset === null) {
            return redirect()->route('customer.auth.recover-password');
        }

        $resendSeconds = $this->resendSeconds();
        $elapsedSeconds = $reset->created_at->diffInSeconds(now(), false);

        return view(themeViewName(key: 'otp_verification'), [
            'time_count' => $elapsedSeconds < $resendSeconds ? (int) ($resendSeconds - $elapsedSeconds) : 0,
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $identity = $request['identity'] ?? session(SessionKey::FORGOT_PASSWORD_IDENTIFY);
        $failure = $this->otpFailureMessage(identity: (string) $identity, otp: (string) $request['otp']);

        if ($failure !== null) {
            ToastMagic::error($failure);

            return redirect()->back();
        }

        return redirect()->route('customer.auth.reset-password', ['token' => $request['otp']]);
    }

    public function showResetForm(Request $request): View|RedirectResponse
    {
        $reset = $this->passwordResetService->findByResetToken(
            token: (string) $request['token'],
            userType: UserType::CUSTOMER,
        );

        if ($reset === null || $this->passwordResetService->isLinkTokenExpired(reset: $reset)) {
            ToastMagic::error(translate('Invalid credentials'));

            return back();
        }

        return view(themeViewName(key: 'reset_password'), ['token' => $request['token']]);
    }

    public function reset(PasswordResetRequest $request): RedirectResponse
    {
        $identity = base64_decode($request['identity']);
        $reset = $this->passwordResetService->findByIdentity(identity: $identity, userType: UserType::CUSTOMER);
        $customer = $this->customerAuthService->findByIdentity(identity: $identity);

        if (! $this->isResetTokenAccepted(reset: $reset, customer: $customer, resetToken: (string) $request['reset_token'])) {
            ToastMagic::error(translate('Invalid data'));

            return back();
        }

        $this->customerAuthService->updatePassword(id: $customer['id'], password: $request['password']);
        $this->customerAuthService->markVerified(id: $customer['id'], channel: self::VERIFIED_CHANNEL_EMAIL);
        $this->passwordResetService->deleteByIdentity(identity: $identity, userType: UserType::CUSTOMER);
        ToastMagic::success(translate(':item reset successfully', ['item' => translate('Password')]));

        return redirect('/');
    }

    public function resendOtp(Request $request): JsonResponse|RedirectResponse
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

        $customer = $this->customerAuthService->findByIdentity(identity: base64_decode($request['identity']));

        if (! $customer) {
            ToastMagic::error(translate('Invalid user'));

            return redirect()->back();
        }

        $resendWait = $this->passwordResetService->secondsUntilResendAllowed(
            identity: $customer['phone'],
            userType: UserType::CUSTOMER,
            resendSeconds: $this->resendSeconds(),
        );

        if ($resendWait > 0) {
            ToastMagic::error(translate('Please try again after').CarbonInterval::seconds($resendWait)->cascade()->forHumans());

            return redirect()->back();
        }

        $firebaseSession = $this->sendFirebaseOtp(
            phone: $customer['phone'],
            recaptchaToken: $request[self::RECAPTCHA_TOKEN_KEY] ?? session(self::RECAPTCHA_TOKEN_KEY),
        );

        if ($firebaseSession === false) {
            ToastMagic::error(translate('Something went wrong').' '.translate('Please try again after sometime'));

            return redirect()->back();
        }

        $token = $this->passwordResetService->issueOtp(
            identity: $customer['phone'],
            userType: UserType::CUSTOMER,
            token: $firebaseSession,
        );

        if ($firebaseSession === null) {
            event(new OtpSmsEvent(phone: $customer['phone'], otp: $token));
        }

        ToastMagic::success(translate(':item sent successfully', ['item' => translate('OTP')]));

        return redirect()->back();
    }

    private function isResetTokenAccepted(mixed $reset, mixed $customer, string $resetToken): bool
    {
        return $reset
            && $customer
            && (string) $reset->token === $resetToken
            && ! $this->passwordResetService->isLinkTokenExpired(reset: $reset);
    }

    private function otpFailureMessage(string $identity, string $otp): ?string
    {
        $blockedSeconds = $this->passwordResetService->secondsUntilUnblocked(
            identity: $identity,
            userType: UserType::CUSTOMER,
            blockSeconds: $this->blockSeconds(),
        );

        if ($blockedSeconds > 0) {
            return translate('Please try again after').CarbonInterval::seconds($blockedSeconds)->cascade()->forHumans();
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

    private function sendFirebaseOtp(string $phone, ?string $recaptchaToken): string|false|null
    {
        $firebaseOtp = $this->businessSettingService->getValueByType(type: BusinessSettingService::FIREBASE_OTP_VERIFICATION, default: []);

        if (! is_array($firebaseOtp) || empty($firebaseOtp['status'])) {
            return null;
        }

        $fcmCredentials = $this->businessSettingService->getValueByType(type: BusinessSettingService::FCM_CREDENTIALS, default: []);

        $response = $this->firebaseOtpService->sendPhoneOtp(
            apiKey: (string) (is_array($fcmCredentials) ? ($fcmCredentials['apiKey'] ?? '') : ''),
            phoneNumber: $phone,
            recaptchaToken: $recaptchaToken,
        );

        return $response['sent'] ? $response['session_info'] : false;
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
