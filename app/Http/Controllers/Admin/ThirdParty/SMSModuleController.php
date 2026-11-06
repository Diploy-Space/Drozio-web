<?php

namespace App\Http\Controllers\Admin\ThirdParty;

use App\Enums\GlobalConstant;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\ThirdParty\SmsModuleUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\FirebaseOtpService;
use App\Services\New\RecaptchaService;
use App\Services\New\SettingService;
use App\Utils\SMSModule;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SMSModuleController extends BaseController
{
    private const SMS_CONFIG_TYPE = 'sms_config';

    /** @var array<int, string> */
    private const SMS_GATEWAYS = [
        'releans', 'twilio', 'nexmo', '2factor', 'msg91', 'hubtel', 'paradox', 'signal_wire',
        '019_sms', 'viatech', 'global_sms', 'akandit_sms', 'sms_to', 'alphanet_sms',
    ];

    private const SMS_SETUP_ROUTE = 'sms_setup';

    private const RECAPTCHA_SESSION_KEY = 'adminSMSRecaptchaSessionKey';

    private const RECAPTCHA_ACTION = 'login';

    private const OTP_RANGE_START = 1111;

    private const OTP_RANGE_END = 9999;

    public function __construct(
        private readonly SettingService $settingService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly FirebaseOtpService $firebaseOtpService,
        private readonly RecaptchaService $recaptchaService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $paymentPublishedStatus = config('get_payment_publish_status') ?? 0;

        return view('admin-views.third-party.sms-index', [
            'recaptcha' => $this->businessSettingService->getValueByType(type: 'recaptcha'),
            'firebaseOtpVerification' => $this->businessSettingService->getValueByType(type: 'firebase_otp_verification'),
            'companyPhone' => $this->businessSettingService->getValueByType(type: 'company_phone'),
            'paymentGatewayPublishedStatus' => $paymentPublishedStatus[0]['is_published'] ?? 0,
            'smsGateways' => $this->sortGatewaysByCredentialCount(gateways: $this->settingService->getList(
                dataLimit: 'all',
                filters: ['settings_type' => self::SMS_CONFIG_TYPE, 'key_name' => GlobalConstant::DEFAULT_SMS_GATEWAYS],
            )),
            'paymentUrl' => $this->addonRouteUrl(name: self::SMS_SETUP_ROUTE),
        ]);
    }

    public function update(SmsModuleUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        $this->storeGatewayCredential(gateway: $request['gateway'], mode: $request['mode'], status: (int) $request['status'], credential: $request->validated());

        if ($request['status'] == 1) {
            $this->deactivateOtherGateways(activeGateway: $request['gateway']);
        }

        ToastMagic::success(GATEWAYS_DEFAULT_UPDATE_200['message']);

        return back();
    }

    public function sendSMS(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        $recaptchaFailure = $this->recaptchaFailureMessage(request: $request);
        if ($recaptchaFailure !== null) {
            ToastMagic::error($recaptchaFailure);

            return back();
        }

        $delivery = $this->deliverSMS(phoneNumber: $request['phone'], recaptchaToken: $request['g-recaptcha-response']);

        $delivery['sent']
            ? ToastMagic::success(translate('SMS sent successfully'))
            : ToastMagic::error($delivery['error']);

        return redirect()->back();
    }

    /** @return array{sent: bool, error: ?string} */
    private function deliverSMS(mixed $phoneNumber, mixed $recaptchaToken): array
    {
        $firebaseOtpVerification = $this->businessSettingService->getValueByType(type: 'firebase_otp_verification') ?? [];

        if (! empty($firebaseOtpVerification['status'])) {
            $response = $this->firebaseOtpService->sendPhoneOtp(
                apiKey: (string) ($this->businessSettingService->getValueByType(type: 'fcm_credentials')['apiKey'] ?? ''),
                phoneNumber: (string) $phoneNumber,
                recaptchaToken: $recaptchaToken ?? Session::get('g-recaptcha-response'),
            );

            return [
                'sent' => $response['sent'],
                'error' => $response['sent'] ? null : ucfirst(strtolower((string) $response['error'])),
            ];
        }

        $response = SMSModule::sendCentralizedSMS($phoneNumber, rand(self::OTP_RANGE_START, self::OTP_RANGE_END));

        return [
            'sent' => $response == 'success',
            'error' => $response == 'success' ? null : translate('SMS send failed'),
        ];
    }

    private function recaptchaFailureMessage(Request $request): ?string
    {
        $firebaseOtpVerification = $this->businessSettingService->getValueByType(type: 'firebase_otp_verification') ?? [];

        if (! empty($firebaseOtpVerification['status'])) {
            return empty($request['g-recaptcha-response']) ? translate('ReCAPTCHA failed') : null;
        }

        $recaptcha = $this->businessSettingService->getValueByType(type: 'recaptcha');

        if (isset($recaptcha) && $recaptcha['status'] == 1 && ! $request['default_captcha_value']) {
            $verification = $this->recaptchaService->verifyToken(
                secretKey: (string) ($recaptcha['secret_key'] ?? ''),
                token: $request['g-recaptcha-response'],
                action: self::RECAPTCHA_ACTION,
                remoteIp: $request->ip(),
            );

            if (! $verification['verified']) {
                return $this->recaptchaReasonMessage(reason: $verification['reason']);
            }
        } elseif (strtolower((string) Session::get(self::RECAPTCHA_SESSION_KEY)) != strtolower((string) $request['default_captcha_value'])) {
            return translate('ReCAPTCHA failed');
        }

        Session::forget(self::RECAPTCHA_SESSION_KEY);

        return null;
    }

    private function recaptchaReasonMessage(?string $reason): string
    {
        return match ($reason) {
            RecaptchaService::REASON_VERIFICATION_FAILED => translate('ReCAPTCHA failed'),
            RecaptchaService::REASON_SCORE_TOO_LOW => translate('ReCAPTCHA score too low. Please try again'),
            RecaptchaService::REASON_ACTION_MISMATCH => translate('ReCAPTCHA Action Invalid'),
            default => translate('ReCAPTCHA verification failed'),
        };
    }

    /** @param  array<string, mixed>  $credential */
    private function storeGatewayCredential(string $gateway, mixed $mode, int $status, array $credential): void
    {
        $stored = $this->settingService->findBy(params: ['key_name' => $gateway, 'settings_type' => self::SMS_CONFIG_TYPE]);
        $data = [
            'key_name' => $gateway,
            'live_values' => $credential,
            'test_values' => $credential,
            'settings_type' => self::SMS_CONFIG_TYPE,
            'mode' => $mode,
            'is_active' => $status,
        ];

        $stored === null
            ? $this->settingService->create(data: $data)
            : $this->settingService->update(id: $stored->id, data: $data);
    }

    private function deactivateOtherGateways(string $activeGateway): void
    {
        foreach (self::SMS_GATEWAYS as $gateway) {
            if ($gateway === $activeGateway) {
                continue;
            }

            $stored = $this->settingService->findBy(params: ['key_name' => $gateway, 'settings_type' => self::SMS_CONFIG_TYPE]);

            if ($stored === null) {
                continue;
            }

            $credential = array_merge($stored['live_values'] ?? [], ['status' => 0]);

            $this->settingService->update(id: $stored->id, data: [
                'live_values' => $credential,
                'test_values' => $credential,
                'is_active' => 0,
            ]);
        }
    }

    private function sortGatewaysByCredentialCount(Collection $gateways): array
    {
        return $gateways
            ->sortBy(fn ($gateway) => count($gateway['live_values'] ?? []))
            ->values()
            ->all();
    }

    private function addonRouteUrl(string $name): string
    {
        foreach (config('addon_admin_routes') ?? [] as $routes) {
            foreach ($routes as $route) {
                if ($route['name'] === $name) {
                    return $route['url'];
                }
            }
        }

        return '';
    }
}
