<?php

namespace App\Http\Controllers\Web\v6\Auth;

use App\Events\EmailVerificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Auth\VerificationTokenRequest;
use App\Services\New\Auth\CustomerAuthService;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RegisterOtpController extends Controller
{
    use CustomerAuthRedirectTrait;

    private const RECAPTCHA_SESSION_KEY = 'default_recaptcha_id_customer_auth';

    private const RECAPTCHA_ACTION = 'customer_auth';

    private const RECAPTCHA_TOKEN_KEY = 'g-recaptcha-response';

    private const TYPE_PHONE_VERIFICATION = 'phone_verification';

    private const TYPE_EMAIL_VERIFICATION = 'email_verification';

    private const DEFAULT_MAX_OTP_HIT = 5;

    private const DEFAULT_RESEND_SECONDS = 60;

    private const DEFAULT_BLOCK_SECONDS = 5;

    private const AJAX_STATUS_SUCCESS = 'success';

    private const AJAX_STATUS_ERROR = 'error';

    private const FORM_JSON_STATUS_FAILED = 0;

    private const FIREBASE_UNKNOWN_VERIFY_ERROR = 'No specific error message';

    private const MAIL_STATUS_ACTIVE = 1;

    private const EMAIL_TEMPLATE_REGISTRATION = 'registration-verification';

    private const EMAIL_USER_TYPE_CUSTOMER = 'customer';

    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly CustomerService $customerService,
        private readonly VerificationService $verificationService,
        private readonly LoginSetupService $loginSetupService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly FirebaseOtpService $firebaseOtpService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly RestockProductService $restockProductService,
    ) {}

    public function verify(VerificationTokenRequest $request): RedirectResponse|JsonResponse
    {
        $recaptchaFailure = $this->recaptchaFailureResponse(request: $request);

        if ($recaptchaFailure !== null) {
            return $recaptchaFailure;
        }

        $verificationType = base64_decode((string) $request['type']);
        $customer = $this->customerAuthService->findByIdentity(identity: base64_decode((string) $request['identity']));
        $identity = (string) ($verificationType === self::TYPE_EMAIL_VERIFICATION ? $customer?->email : $customer?->phone);
        $verification = $identity === '' ? null : $this->verificationService->findByIdentity(identity: $identity);

        if ($verification === null) {
            return $this->otpNotMatchedResponse(request: $request);
        }

        $block = $this->verificationService->checkRegistrationOtpBlock(
            verification: $verification,
            maxAttempts: $this->maxOtpHit(),
            resendSeconds: $this->resendSeconds(),
            blockSeconds: $this->blockSeconds(),
        );

        if ($block['reason'] === VerificationService::REASON_OTP_BLOCKED) {
            ToastMagic::error(translate('Please try again after').$this->humanDuration(seconds: $block['retry_after']));

            return redirect()->back();
        }

        if ($block['reason'] === VerificationService::REASON_OTP_TOO_MANY_ATTEMPTS) {
            $message = translate('Too many attempts.').' '.translate('Please try again after').$this->humanDuration(seconds: $block['retry_after']);

            if ($request->ajax()) {
                return response()->json(['status' => self::FORM_JSON_STATUS_FAILED, 'message' => $message]);
            }

            ToastMagic::error($message);

            return redirect()->back();
        }

        $isPhoneIdentity = $verificationType !== self::TYPE_EMAIL_VERIFICATION;

        if ($isPhoneIdentity && $this->isFirebaseOtpEnabled()) {
            $firebaseResult = $this->firebaseOtpService->verifyPhoneOtp(
                apiKey: $this->firebaseApiKey(),
                sessionInfo: (string) $verification->token,
                phoneNumber: (string) $verification->phone_or_email,
                code: (string) $request['token'],
            );

            if (! $firebaseResult['verified']) {
                $this->verificationService->registerFirebaseOtpFailure(verification: $verification);
                ToastMagic::error($this->firebaseErrorMessage(error: $firebaseResult['error'] ?? self::FIREBASE_UNKNOWN_VERIFY_ERROR));

                return back();
            }
        } elseif ($this->verificationService->findByIdentityAndToken(identity: $identity, token: (string) $request['token']) === null) {
            $this->toastRegistrationOtpMismatch(verification: $verification);
            ToastMagic::error(translate('Invalid OTP'));

            return back();
        }

        $this->customerAuthService->markChannelVerified(
            id: $customer->id,
            channel: $verificationType === self::TYPE_PHONE_VERIFICATION ? LoginSetupService::CHANNEL_PHONE : LoginSetupService::CHANNEL_EMAIL,
        );
        $this->verificationService->deleteByIdentity(identity: $identity);

        $this->signIn(customer: $this->customerService->find(id: $customer->id));
        ToastMagic::success(translate('Verification completed successfully'));

        return redirect($this->getCustomerAuthReturnUrl());
    }

    public function verifyAjax(VerificationTokenRequest $request): JsonResponse
    {
        $customerId = filter_var($request->input('id'), FILTER_VALIDATE_INT);
        $customer = $customerId === false ? null : $this->customerService->find(id: $customerId);
        $email = (string) $customer?->email;

        if ($email === '') {
            return $this->ajaxVerifyResponse(status: self::AJAX_STATUS_ERROR, message: translate('Verification code/ OTP mismatched'));
        }

        $verification = $this->verificationService->findByIdentityAndToken(identity: $email, token: (string) $request['token']);

        if ($verification !== null) {
            $blockedSeconds = $this->verificationService->secondsRemainingInBlock(verification: $verification, blockSeconds: $this->blockSeconds());

            if ($blockedSeconds > 0) {
                return $this->ajaxVerifyResponse(
                    status: self::AJAX_STATUS_ERROR,
                    message: translate('Please try again after').$this->humanDuration(seconds: $blockedSeconds),
                );
            }

            $this->customerAuthService->markChannelVerified(id: $customer->id, channel: $this->ajaxVerifiedChannel());
            $this->verificationService->deleteByIdentity(identity: $email);

            return $this->ajaxVerifyResponse(status: self::AJAX_STATUS_SUCCESS, message: translate('Verification completed successfully'));
        }

        $pendingVerification = $this->verificationService->findByIdentity(identity: $email);

        if ($pendingVerification === null) {
            return $this->ajaxVerifyResponse(status: self::AJAX_STATUS_ERROR, message: translate('Verification code/ OTP mismatched'));
        }

        $mismatch = $this->verificationService->registerOtpMismatch(
            verification: $pendingVerification,
            maxAttempts: $this->maxOtpHit(),
            blockSeconds: $this->blockSeconds(),
        );

        return $this->ajaxVerifyResponse(status: self::AJAX_STATUS_ERROR, message: $this->ajaxMismatchMessage(mismatch: $mismatch));
    }

    public function resendOtp(Request $request): JsonResponse|RedirectResponse
    {
        $recaptchaFailure = $this->recaptchaFailureResponse(request: $request);

        if ($recaptchaFailure !== null) {
            return $recaptchaFailure;
        }

        $verificationType = base64_decode((string) $request['type']);
        $requestedIdentity = base64_decode((string) $request['identity']);
        $customer = $this->customerAuthService->findByIdentity(identity: $requestedIdentity);
        $recipient = $this->otpRecipient(customer: $customer, requestedIdentity: $requestedIdentity);
        $isEmailIdentity = $customer !== null && $verificationType === self::TYPE_EMAIL_VERIFICATION;
        $identity = (string) ($isEmailIdentity ? $recipient['email'] : $recipient['phone']);

        $resendWait = $identity === '' ? 0 : $this->verificationService->secondsUntilResendAllowed(
            identity: $identity,
            resendSeconds: $this->resendSeconds(),
        );

        if ($resendWait > 0) {
            ToastMagic::error(translate('Please try again after').$this->humanDuration(seconds: $resendWait));

            return redirect()->back();
        }

        $this->verificationService->deleteByIdentity(identity: $identity);
        $this->sendRegistrationOtp(
            recipient: $recipient,
            identity: $identity,
            isPhoneVerificationRequired: $customer === null || $this->loginSetupService->isVerificationRequired(channel: LoginSetupService::CHANNEL_PHONE),
            recaptchaToken: $request[self::RECAPTCHA_TOKEN_KEY] ?? session(self::RECAPTCHA_TOKEN_KEY),
        );
        ToastMagic::success(translate(':item sent successfully', ['item' => translate('OTP')]));

        return redirect()->back();
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

    private function otpNotMatchedResponse(Request $request): RedirectResponse|JsonResponse
    {
        $message = translate('OTP is not matched');

        if ($request->ajax()) {
            return response()->json(['status' => self::FORM_JSON_STATUS_FAILED, 'message' => $message]);
        }

        ToastMagic::error($message);

        return redirect()->back();
    }

    private function toastRegistrationOtpMismatch(Model $verification): void
    {
        $mismatch = $this->verificationService->registerOtpMismatch(
            verification: $verification,
            maxAttempts: $this->maxOtpHit(),
            blockSeconds: $this->blockSeconds(),
        );

        if ($mismatch['reason'] === VerificationService::REASON_OTP_BLOCKED) {
            ToastMagic::error(translate('Please try again after').$this->humanDuration(seconds: $mismatch['retry_after']));
        } elseif ($mismatch['reason'] === VerificationService::REASON_OTP_TOO_MANY_ATTEMPTS) {
            ToastMagic::error(translate('Too many attempts. Please try again after').$this->humanDuration(seconds: $mismatch['retry_after']));
        }
    }

    /** @param array{reason: ?string, retry_after: int} $mismatch */
    private function ajaxMismatchMessage(array $mismatch): string
    {
        return match ($mismatch['reason']) {
            VerificationService::REASON_OTP_BLOCKED => translate('Please try again after').$this->humanDuration(seconds: $mismatch['retry_after']),
            VerificationService::REASON_OTP_BLOCK_LIFTED => translate('Verification OTP mismatched'),
            VerificationService::REASON_OTP_TOO_MANY_ATTEMPTS => translate('Too many attempts. Please try again after').$this->humanDuration(seconds: $mismatch['retry_after']),
            default => translate('Verification code/ OTP mismatched'),
        };
    }

    private function ajaxVerifyResponse(string $status, string $message): JsonResponse
    {
        return response()->json(['status' => $status, 'message' => $message]);
    }

    private function ajaxVerifiedChannel(): string
    {
        $isEmailVerificationRequired = $this->loginSetupService->isVerificationRequired(channel: LoginSetupService::CHANNEL_EMAIL);
        $isPhoneVerificationRequired = $this->loginSetupService->isVerificationRequired(channel: LoginSetupService::CHANNEL_PHONE);

        return $isEmailVerificationRequired || ! $isPhoneVerificationRequired ? LoginSetupService::CHANNEL_EMAIL : LoginSetupService::CHANNEL_PHONE;
    }

    private function signIn(Model $customer): void
    {
        auth('customer')->login($customer);

        $sessionState = $this->customerService->buildSignInSessionState(
            wishListProductIds: $this->wishlistService->getProductIds(customerId: $customer->id),
            compareListProductIds: $this->productCompareService->getProductIds(customerId: $customer->id),
            restockFcmTopics: $this->restockProductService->getCustomerFcmTopics(customerId: $customer->id),
        );

        session()->forget($sessionState['forget']);
        session()->put($sessionState['put']);
    }

    /** @return array{phone: ?string, email: ?string, f_name: ?string, is_phone_verified: int, is_email_verified: int} */
    private function otpRecipient(?Model $customer, string $requestedIdentity): array
    {
        if ($customer === null) {
            return [
                'phone' => $requestedIdentity,
                'email' => $requestedIdentity,
                'f_name' => null,
                'is_phone_verified' => 0,
                'is_email_verified' => 0,
            ];
        }

        return [
            'phone' => $customer->phone,
            'email' => $customer->email,
            'f_name' => $customer->f_name,
            'is_phone_verified' => (int) $customer->is_phone_verified,
            'is_email_verified' => (int) $customer->is_email_verified,
        ];
    }

    /** @param array{phone: ?string, email: ?string, f_name: ?string, is_phone_verified: int, is_email_verified: int} $recipient */
    private function sendRegistrationOtp(array $recipient, string $identity, bool $isPhoneVerificationRequired, ?string $recaptchaToken): void
    {
        $isPhonePending = $isPhoneVerificationRequired && ! $recipient['is_phone_verified'];

        if ($isPhonePending && $this->isFirebaseOtpEnabled()) {
            $firebaseResult = $this->firebaseOtpService->sendPhoneOtp(
                apiKey: $this->firebaseApiKey(),
                phoneNumber: (string) $recipient['phone'],
                recaptchaToken: $recaptchaToken,
            );

            if (! $firebaseResult['sent']) {
                ToastMagic::error($this->firebaseErrorMessage(error: (string) $firebaseResult['error']));

                return;
            }

            $this->verificationService->issueOtp(identity: $identity, token: (string) $firebaseResult['session_info']);

            return;
        }

        if ($isPhonePending) {
            $token = $this->verificationService->issueOtp(identity: $identity);
            SMSModule::sendCentralizedSMS($recipient['phone'], $token);
            ToastMagic::success(translate('Please check your SMS for OTP'));

            return;
        }

        $isEmailPending = $this->loginSetupService->isVerificationRequired(channel: LoginSetupService::CHANNEL_EMAIL) && ! $recipient['is_email_verified'];

        if (! $isEmailPending) {
            $this->verificationService->issueOtp(identity: $identity);

            return;
        }

        $token = $this->verificationService->issueOtp(identity: $identity);
        $emailFailure = $this->sendEmailOtp(recipient: $recipient, token: $token);

        if ($emailFailure !== null) {
            $this->verificationService->deleteByIdentity(identity: $identity);
            ToastMagic::error($emailFailure);
        }
    }

    /** @param array{phone: ?string, email: ?string, f_name: ?string, is_phone_verified: int, is_email_verified: int} $recipient */
    private function sendEmailOtp(array $recipient, string $token): ?string
    {
        $mailConfig = $this->businessSettingService->getValueByType(type: BusinessSettingService::MAIL_CONFIG, default: []);

        if ((int) ($mailConfig['status'] ?? 0) === 0) {
            $mailConfig = $this->businessSettingService->getValueByType(type: BusinessSettingService::MAIL_CONFIG_SENDGRID, default: []);
        }

        if ((int) ($mailConfig['status'] ?? 0) !== self::MAIL_STATUS_ACTIVE || empty($recipient['email'])) {
            return translate('Email failed');
        }

        try {
            event(new EmailVerificationEvent(email: $recipient['email'], data: [
                'userName' => $recipient['f_name'],
                'subject' => translate('Registration Verification Code'),
                'title' => translate('Registration Verification Code'),
                'verificationCode' => $token,
                'userType' => self::EMAIL_USER_TYPE_CUSTOMER,
                'templateName' => self::EMAIL_TEMPLATE_REGISTRATION,
            ]));
        } catch (Exception) {
            return translate('Email is not configured').'. '.translate('Contact with the administrator');
        }

        return null;
    }

    private function firebaseErrorMessage(string $error): string
    {
        return ucfirst(normalizeTranslationKey(strtolower($error)));
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

    private function humanDuration(int $seconds): string
    {
        return CarbonInterval::seconds($seconds)->cascade()->forHumans();
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
