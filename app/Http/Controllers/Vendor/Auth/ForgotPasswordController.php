<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Enums\SessionKey;
use App\Enums\UserType;
use App\Events\PasswordResetEvent;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\Auth\PasswordResetRequest;
use App\Http\Requests\Vendor\Profile\PasswordUpdateRequest;
use App\Services\FirebaseService;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\Auth\VendorAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\RecaptchaService;
use App\Traits\EmailTemplateTrait;
use App\Traits\SmsGateway;
use App\Utils\SMSModule;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ForgotPasswordController extends BaseController
{
    use EmailTemplateTrait, SmsGateway;

    private const DEFAULT_MAX_OTP_HIT = 5;

    public function __construct(
        private readonly VendorAuthService $vendorAuthService,
        private readonly PasswordResetService $passwordResetService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly FirebaseService $firebaseService,
    ) {
        $this->middleware('guest:seller', ['except' => ['logout']]);
    }

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return $this->getForgotPasswordView();
    }

    public function getForgotPasswordView(): View
    {
        return view('vendor-views.auth.forgot-password.index');
    }

    /**
     * @throws Exception
     */
    public function getPasswordResetRequest(PasswordResetRequest $request): JsonResponse|RedirectResponse
    {
        session()->put(SessionKey::FORGOT_PASSWORD_IDENTIFY, $request['identity']);
        $verificationBy = getWebConfig('vendor_forgot_password_method') ?? 'phone';

        $result = RecaptchaService::verificationStatus(request: $request, session: 'default_recaptcha_id_vendor_forgot_password', action: 'vendor_forgot_password', firebase: true);
        if ($result && ! $result['status']) {
            if ($request->ajax()) {
                return response()->json([
                    'error' => $result['message'],
                ]);
            }
            ToastMagic::error($result['message']);

            return back();
        }

        if ($verificationBy == 'email') {
            $vendor = $this->vendorAuthService->findByIdentity(identity: $request['identity']);
            if (isset($vendor)) {
                $emailServicesSmtp = getWebConfig(name: 'mail_config');
                if ($emailServicesSmtp['status'] == 0) {
                    $emailServicesSmtp = getWebConfig(name: 'mail_config_sendgrid');
                }
                if ($emailServicesSmtp['status'] == 1) {
                    $token = Str::random(120);
                    $this->passwordResetService->issueOtp(identity: $request['identity'], userType: UserType::SELLER, token: $token);
                    $resetUrl = route('vendor.auth.forgot-password.reset-password', ['token' => $token]);
                    try {
                        $data = [
                            'userType' => 'vendor',
                            'templateName' => 'forgot-password',
                            'vendorName' => $vendor['f_name'],
                            'subject' => translate('Password reset'),
                            'title' => translate('Password reset'),
                            'passwordResetURL' => $resetUrl,
                        ];
                        event(new PasswordResetEvent(email: $vendor['email'], data: $data));
                    } catch (Exception $exception) {
                        if ($request->ajax()) {
                            return response()->json(['error' => translate('Email send failed').'!!']);
                        }
                        ToastMagic::error(translate('Email send failed'));

                        return back();
                    }
                    if ($request->ajax()) {
                        return response()->json([
                            'verificationBy' => 'mail',
                            'success' => translate('OTP has been sent to your email address'),
                        ]);
                    }
                    ToastMagic::success(translate('OTP has been sent to your email address'));

                    return back();
                }
                $smsErrorMsg = translate('Something went wrong').' '.translate('Please try again after sometime');
                if ($request->ajax()) {
                    return response()->json(['error' => $smsErrorMsg]);
                }
                ToastMagic::error($smsErrorMsg);

                return back();
            }
        } elseif ($verificationBy == 'phone') {
            $vendor = $this->vendorAuthService->findByIdentity(identity: $request['identity']);
            if (isset($vendor)) {
                $response = 'not_found';
                $smsErrorMsg = translate('Something went wrong').' '.translate('Please try again after sometime');
                $token = (config('app.mode') == 'live') ? rand(111111, 999999) : 123456;

                $firebaseOTPVerification = getWebConfig(name: 'firebase_otp_verification') ?? [];
                if ($firebaseOTPVerification && $firebaseOTPVerification['status']) {
                    try {
                        $firebaseResponse = $this->firebaseService->sendOtp($request['identity']);
                        if ($firebaseResponse['status'] == 'success') {
                            $token = $firebaseResponse['sessionInfo'];
                            $response = $firebaseResponse['status'];
                        } else {
                            $smsErrorMsg = translate(strtolower($firebaseResponse['errors']));
                        }
                    } catch (Exception $e) {
                        $response = 'not_found';
                        $smsErrorMsg = translate('Something went wrong').' '.translate('Please try again after sometime');
                    }
                } else {
                    $response = SMSModule::sendCentralizedSMS($request['identity'], $token);
                    if (config('app.mode') == 'dev') {
                        $response = 'success';
                    }
                }

                $this->passwordResetService->issueOtp(identity: $request['identity'], userType: UserType::SELLER, token: (string) $token);

                if (config('app.mode') == 'dev') {
                    if ($request->ajax()) {
                        return response()->json([
                            'verificationBy' => 'phone',
                            'redirectRoute' => route('vendor.auth.forgot-password.otp-verification'),
                            'success' => translate('Check your phone').', '.translate('Password reset otp sent'),
                        ]);
                    }
                    ToastMagic::success(translate('Check your phone').', '.translate('Password reset otp sent'));

                    return redirect()->route('vendor.auth.forgot-password.otp-verification');
                }

                if ($response === 'not_found') {
                    if ($request->ajax()) {
                        return response()->json([
                            'error' => $smsErrorMsg,
                        ]);
                    }
                    ToastMagic::error($smsErrorMsg);

                    return back();
                }

                if ($request->ajax()) {
                    return response()->json([
                        'verificationBy' => 'phone',
                        'redirectRoute' => route('vendor.auth.forgot-password.otp-verification'),
                        'success' => translate('Check your phone').', '.translate('Password reset otp sent'),
                    ]);
                }
                ToastMagic::success(translate('Check your phone').', '.translate('Password reset otp sent'));

                return redirect()->route('vendor.auth.forgot-password.otp-verification');
            }
        }
        if ($request->ajax()) {
            return response()->json([
                'error' => translate('No such user found').'!!',
            ]);
        }
        ToastMagic::error(translate('No such user found'));

        return back();
    }

    public function getOTPVerificationView(): View
    {
        return view('vendor-views.auth.forgot-password.verify-otp-view');
    }

    /**
     * @throws Exception
     */
    public function submitOTPVerificationCode(Request $request): RedirectResponse|JsonResponse
    {
        $identity = session(SessionKey::FORGOT_PASSWORD_IDENTIFY);
        $otpCode = is_array($request['token']) ? implode('', $request['token']) : $request['token'];
        $verificationData = $this->passwordResetService->findByIdentity(identity: $identity, userType: UserType::SELLER);
        $OTPVerificationData = $verificationData !== null && (string) $verificationData['token'] === $otpCode;

        $tokenVerifyStatus = false;
        $firebaseOTPVerification = getWebConfig(name: 'firebase_otp_verification') ?? [];
        if ($firebaseOTPVerification && $firebaseOTPVerification['status']) {
            $firebaseVerify = $this->firebaseService->verifyOtp($verificationData['token'], $verificationData['identity'], $otpCode);
            $tokenVerifyStatus = (bool) ($firebaseVerify['status'] == 'success');
            if (! $tokenVerifyStatus) {
                $this->passwordResetService->registerOtpFailure(
                    identity: $identity,
                    userType: UserType::SELLER,
                    maxAttempts: $this->maxOtpHit(),
                );
                ToastMagic::error(translate(strtolower($firebaseVerify['errors'])));

                return redirect()->back();
            }
        } else {
            $tokenVerifyStatus = (bool) $OTPVerificationData;
        }

        if ($tokenVerifyStatus) {
            $redirectUrl = route('vendor.auth.forgot-password.reset-password', [
                'token' => $verificationData['token'],
            ]);

            return $request->ajax() ? response()->json(['redirectRoute' => $redirectUrl]) : redirect($redirectUrl);
        }

        return $this->otpVerificationFailed(request: $request, message: translate('Invalid otp'));
    }

    private function otpVerificationFailed(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->ajax()) {
            return response()->json(['error' => $message]);
        }

        ToastMagic::error($message);

        return redirect()->back();
    }

    /**
     * @throws Exception
     */
    public function getPasswordResetView(Request $request): View|RedirectResponse
    {
        $passwordResetData = $this->passwordResetService->findByResetToken(token: $request['token'], userType: UserType::SELLER);
        if (isset($passwordResetData)) {
            $token = $request['token'];

            return view('vendor-views.auth.forgot-password.reset-password-view', compact('token'));
        }
        ToastMagic::error(translate('Invalid URL'));

        return redirect()->route('vendor.auth.login');
    }

    /**
     * @throws Exception
     */
    public function resetPassword(PasswordUpdateRequest $request): JsonResponse|RedirectResponse
    {
        $passwordResetData = $this->passwordResetService->findByResetToken(token: $request['reset_token'], userType: UserType::SELLER);
        if ($passwordResetData) {
            $vendor = $this->vendorAuthService->findByIdentity(identity: $passwordResetData['identity']);
            $this->vendorAuthService->updatePassword(id: $vendor['id'], password: $request['password']);
            $this->vendorAuthService->revokeAccessToken(account: $vendor);
            $this->passwordResetService->delete(id: $passwordResetData['id']);
            if ($request->ajax()) {
                return response()->json([
                    'passwordUpdate' => 1,
                    'success' => translate(':item reset successfully', ['item' => translate('Password')]),
                    'redirectRoute' => route('vendor.auth.login'),
                ]);
            }
            ToastMagic::success(translate(':item reset successfully', ['item' => translate('Password')]));

            return redirect()->route('vendor.auth.login');
        }

        if ($request->ajax()) {
            return response()->json(['error' => translate('Invalid URL')]);
        }
        ToastMagic::error(translate('Invalid URL'));

        return back();
    }

    private function maxOtpHit(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'maximum_otp_hit', default: self::DEFAULT_MAX_OTP_HIT);
    }
}
