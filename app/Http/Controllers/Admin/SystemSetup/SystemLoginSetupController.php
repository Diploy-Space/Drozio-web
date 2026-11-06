<?php

namespace App\Http\Controllers\Admin\SystemSetup;

use App\Enums\GlobalConstant;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\System\CustomerLoginSetupUpdateRequest;
use App\Http\Requests\Admin\Setting\System\LoginSetupUpdateRequest;
use App\Http\Requests\Admin\Setting\System\OtpSetupUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\LoginSetupService;
use App\Services\New\SettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SystemLoginSetupController extends BaseController
{
    private const KEY_LOGIN_OPTIONS = 'login_options';

    private const KEY_SOCIAL_MEDIA_FOR_LOGIN = 'social_media_for_login';

    private const KEY_EMAIL_VERIFICATION = 'email_verification';

    private const KEY_PHONE_VERIFICATION = 'phone_verification';

    private const SMS_CONFIG_TYPE = 'sms_config';

    private const ADMIN_LOGIN_URL = 'admin_login_url';

    private const EMPLOYEE_LOGIN_URL = 'employee_login_url';

    /** @var array<int, string> */
    private const SOCIAL_LOGIN_MEDIUMS = ['google', 'facebook', 'apple'];

    /** @var array<int, string> */
    private const OTP_SETUP_KEYS = ['maximum_otp_hit', 'otp_resend_time', 'temporary_block_time', BusinessSettingService::OTP_EXPIRY_TIME, 'maximum_login_hit', 'temporary_login_block_time'];

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
        private readonly SettingService $settingService,
        private readonly LoginSetupService $loginSetupService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return $this->getCustomerLoginSetupView();
    }

    public function getCustomerLoginSetupView(): View
    {
        return view('admin-views.system-setup.login-settings.customer-login-setup', [
            'socialMediaLoginOptions' => $this->getLoginSetupValue(key: self::KEY_SOCIAL_MEDIA_FOR_LOGIN, default: ['google' => 0, 'facebook' => 0, 'apple' => 0]),
            'loginOptions' => $this->getLoginSetupValue(key: self::KEY_LOGIN_OPTIONS, default: ['manual_login' => 0, 'otp_login' => 0, 'social_login' => 0]),
            'emailVerification' => $this->loginSetupService->findBy(params: ['key' => self::KEY_EMAIL_VERIFICATION])?->value ?? 0,
            'phoneVerification' => $this->loginSetupService->findBy(params: ['key' => self::KEY_PHONE_VERIFICATION])?->value ?? 0,
            'configStatus' => $this->getSocialMediaLoginConfigStatus(),
        ]);
    }

    public function updateCustomerLoginSetup(CustomerLoginSetupUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('You cannot update this on demo mode'));

            return back();
        }

        $this->loginSetupService->updateOrInsert(params: ['key' => self::KEY_LOGIN_OPTIONS], data: ['value' => json_encode([
            'manual_login' => $request->get('manual_login', 0),
            'otp_login' => $request->get('otp_login', 0),
            'social_login' => $request->get('social_login', 0),
        ])]);
        $this->loginSetupService->updateOrInsert(params: ['key' => self::KEY_SOCIAL_MEDIA_FOR_LOGIN], data: ['value' => json_encode([
            'google' => $request->get('google_login', 0),
            'facebook' => $request->get('facebook_login', 0),
            'apple' => $request->get('apple_login', 0),
        ])]);
        $this->loginSetupService->updateOrInsert(params: ['key' => self::KEY_EMAIL_VERIFICATION], data: ['value' => $request->get('email_verification', 0)]);
        $this->loginSetupService->updateOrInsert(params: ['key' => self::KEY_PHONE_VERIFICATION], data: ['value' => $request->get('phone_verification', 0)]);

        updateSetupGuideCacheKey(key: 'customer_login', panel: 'admin');
        ToastMagic::success(translate('Login settings updated'));

        return redirect()->route('admin.settings.system-setup.login-settings.customer-login-setup');
    }

    public function getConfigValidation(Request $request): JsonResponse
    {
        $status = match (true) {
            in_array($request['key'], self::SOCIAL_LOGIN_MEDIUMS, true) => $this->getSocialMediaLoginConfigStatus()[$request['key']],
            $request['key'] === 'email' => $this->getEmailConfigStatus(),
            in_array($request['key'], ['otp', 'otp-login'], true) => $this->getOtpConfigStatus(),
            default => 1,
        };

        return response()->json([
            'status' => $status,
            'htmlView' => view('admin-views.system-setup.login-settings.partials._config-validation', ['data' => ['type' => $request['key']]])->render(),
        ]);
    }

    public function getOtpSetupView(): View
    {
        return view('admin-views.system-setup.login-settings.otp-setup', [
            'maximumOtpHit' => $this->businessSettingService->findBy(params: ['type' => 'maximum_otp_hit'])?->value ?? 0,
            'otpResendTime' => $this->businessSettingService->findBy(params: ['type' => 'otp_resend_time'])?->value ?? 0,
            'temporaryBlockTime' => $this->businessSettingService->findBy(params: ['type' => 'temporary_block_time'])?->value ?? 0,
            'otpExpiryTime' => $this->businessSettingService->findBy(params: ['type' => 'otp_expiry_time'])?->value ?? BusinessSettingService::DEFAULT_OTP_EXPIRY_SECONDS,
            'maximumLoginHit' => $this->businessSettingService->findBy(params: ['type' => 'maximum_login_hit'])?->value ?? 0,
            'temporaryLoginBlockTime' => $this->businessSettingService->findBy(params: ['type' => 'temporary_login_block_time'])?->value ?? 0,
            'otpFields' => $this->otpSetupFields(),
        ]);
    }

    private function otpSetupFields(): array
    {
        $fields = [];

        foreach (OtpSetupUpdateRequest::fields() as $name => $field) {
            $fields[$name] = $field + ['range_message' => OtpSetupUpdateRequest::rangeMessage($field)];
        }

        return $fields;
    }

    public function updateOtpSetup(OtpSetupUpdateRequest $request): RedirectResponse
    {
        foreach (self::OTP_SETUP_KEYS as $key) {
            $this->businessSettingService->updateOrInsert(params: ['type' => $key], data: ['value' => $request[$key]]);
        }

        ToastMagic::success(translate('Settings updated'));

        return back();
    }

    public function getLoginSetupView(): View
    {
        return view('admin-views.system-setup.login-settings.login-url-setup');
    }

    public function updateLoginSetupView(LoginSetupUpdateRequest $request): RedirectResponse
    {
        if (! in_array($request['type'], [self::ADMIN_LOGIN_URL, self::EMPLOYEE_LOGIN_URL], true)) {
            return back();
        }

        $currentUrl = strtolower($request['url']);
        $counterpartType = $request['type'] === self::ADMIN_LOGIN_URL ? self::EMPLOYEE_LOGIN_URL : self::ADMIN_LOGIN_URL;
        $counterpartUrl = $this->businessSettingService->findBy(params: ['type' => $counterpartType])?->value ?? '';

        if ($counterpartUrl == $currentUrl) {
            ToastMagic::error(translate('Admin and employee URL cannot be same'));

            return back();
        }

        $this->businessSettingService->updateOrInsert(params: ['type' => $request['type']], data: ['value' => $currentUrl]);
        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    /**
     * @param  array<string, int>  $default
     * @return array<string, mixed>
     */
    private function getLoginSetupValue(string $key, array $default): array
    {
        $storedValue = $this->loginSetupService->findBy(params: ['key' => $key])?->value;

        if ($storedValue === null) {
            return $default;
        }

        return json_decode($storedValue, true) ?? $default;
    }

    /** @return array<string, int> */
    private function getSocialMediaLoginConfigStatus(): array
    {
        $configStatus = ['google' => 0, 'facebook' => 0, 'apple' => 0];

        foreach ($this->getBusinessSettingValue(type: 'social_login') as $socialLogin) {
            if (! empty($socialLogin['client_id']) && ! empty($socialLogin['client_secret'])) {
                $configStatus[$socialLogin['login_medium']] = 1;
            }
        }

        foreach ($this->getBusinessSettingValue(type: 'apple_login') as $appleLogin) {
            if (! empty($appleLogin['client_id']) && ! empty($appleLogin['team_id']) && ! empty($appleLogin['key_id']) && ! empty($appleLogin['service_file'])) {
                $configStatus[$appleLogin['login_medium']] = 1;
            }
        }

        return $configStatus;
    }

    private function getEmailConfigStatus(): int
    {
        $mailConfig = $this->getBusinessSettingValue(type: 'mail_config');

        if (empty($mailConfig['status'])) {
            $mailConfig = $this->getBusinessSettingValue(type: 'mail_config_sendgrid');
        }

        return ($mailConfig['status'] ?? 0) == 1 ? 1 : 0;
    }

    private function getOtpConfigStatus(): int
    {
        $smsGateways = $this->settingService->getList(
            dataLimit: 'all',
            filters: config('get_payment_publish_status')
                ? ['settings_type' => self::SMS_CONFIG_TYPE]
                : ['settings_type' => self::SMS_CONFIG_TYPE, 'key_name' => GlobalConstant::DEFAULT_SMS_GATEWAYS],
        );

        $status = 0;
        foreach ($smsGateways as $smsGateway) {
            $status = $smsGateway['is_active'] == 1 ? 1 : $status;
        }

        $firebaseOtpVerification = $this->getBusinessSettingValue(type: 'firebase_otp_verification');
        $firebaseOtpStatus = (int) (! empty($firebaseOtpVerification['status']) && ! empty($firebaseOtpVerification['web_api_key']));

        return (int) ($firebaseOtpStatus || $status);
    }

    /** @return array<string, mixed> */
    private function getBusinessSettingValue(string $type): array
    {
        $storedValue = $this->businessSettingService->findBy(params: ['type' => $type])?->value;

        if ($storedValue === null) {
            return [];
        }

        $decoded = json_decode($storedValue, true);

        return is_array($decoded) ? $decoded : [];
    }
}
