<?php

namespace App\Http\Controllers\Admin\ThirdParty;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\ThirdParty\AppleLoginUpdateRequest;
use App\Http\Requests\Admin\Setting\ThirdParty\SocialLoginUpdateRequest;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SocialLoginSettingsController extends BaseController
{
    private const SOCIAL_LOGIN_SETTING_KEY = 'social_login';

    private const APPLE_LOGIN_SETTING_KEY = 'apple_login';

    private const WHATSAPP_SETTING_KEY = 'whatsapp';

    private const SERVICE_FILE_DIRECTORY = 'apple-login/';

    private const SERVICE_FILE_FORMAT = 'p8';

    private const LOGIN_SETUP_CACHE_TYPE = 'login_setups';

    /** @var array<string, string> */
    private const LOGIN_MEDIUM_LOGOS = [
        'google' => 'public/assets/back-end/img/google-logo.png',
        'facebook' => 'public/assets/back-end/img/facebook.png',
        'apple' => 'public/assets/back-end/img/apple.png',
    ];

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => [
                self::SOCIAL_LOGIN_SETTING_KEY,
                self::APPLE_LOGIN_SETTING_KEY,
                self::WHATSAPP_SETTING_KEY,
            ]])
            ->pluck('value', 'type');

        return view('admin-views.third-party.social-login.view', [
            'socialLoginServices' => $this->decodedSetting($settings, self::SOCIAL_LOGIN_SETTING_KEY),
            'appleLoginServices' => $this->decodedSetting($settings, self::APPLE_LOGIN_SETTING_KEY),
            'whatsapp' => $this->decodedSetting($settings, self::WHATSAPP_SETTING_KEY),
            'socialLoginLogos' => self::LOGIN_MEDIUM_LOGOS,
        ]);
    }

    public function update(SocialLoginUpdateRequest $request, string $service): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        $this->businessSettingService->updateWhere(
            params: ['type' => self::SOCIAL_LOGIN_SETTING_KEY],
            data: ['value' => $this->getSocialLoginValue(
                credentials: $this->storedCredentials(type: self::SOCIAL_LOGIN_SETTING_KEY),
                data: $request->all(),
                service: $service,
            )],
        );

        cacheRemoveByType(type: self::LOGIN_SETUP_CACHE_TYPE);
        ToastMagic::success(translate('Credential updated').' '.$service);

        return back();
    }

    public function updateAppleLogin(AppleLoginUpdateRequest $request, string $service): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        $serviceFile = $request->file('service_file');

        $this->businessSettingService->updateWhere(
            params: ['type' => self::APPLE_LOGIN_SETTING_KEY],
            data: ['value' => $this->getAppleLoginValue(
                credentials: $this->storedCredentials(type: self::APPLE_LOGIN_SETTING_KEY),
                data: $request->all(),
                service: $service,
                serviceFile: $serviceFile === null ? null : $this->businessSettingService->storeFile(
                    directory: self::SERVICE_FILE_DIRECTORY,
                    format: self::SERVICE_FILE_FORMAT,
                    file: $serviceFile,
                ),
            )],
        );

        cacheRemoveByType(type: self::LOGIN_SETUP_CACHE_TYPE);
        ToastMagic::success(translate('Credential updated').' '.$service);

        return back();
    }

    /** @return array<int|string, mixed> */
    private function decodedSetting(Collection $settings, string $type): array
    {
        return json_decode((string) $settings->get($type), true) ?? [];
    }

    /** @return array<int, array<string, mixed>> */
    private function storedCredentials(string $type): array
    {
        $storedValue = $this->businessSettingService->findBy(params: ['type' => $type])?->value;

        return json_decode((string) $storedValue, true) ?? [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $credentials
     * @param  array<string, mixed>  $data
     */
    private function getSocialLoginValue(array $credentials, array $data, string $service): string
    {
        foreach ($credentials as $index => $credential) {
            if (($credential['login_medium'] ?? null) !== $service) {
                continue;
            }

            $credentials[$index] = [
                'login_medium' => $service,
                'client_id' => $data['client_id'] ?? null,
                'client_secret' => $data['client_secret'] ?? null,
                'status' => 1,
            ];
        }

        return json_encode($credentials);
    }

    /**
     * @param  array<int, array<string, mixed>>  $credentials
     * @param  array<string, mixed>  $data
     */
    private function getAppleLoginValue(array $credentials, array $data, string $service, ?string $serviceFile): string
    {
        foreach ($credentials as $index => $credential) {
            if (($credential['login_medium'] ?? null) !== $service) {
                continue;
            }

            $credentials[$index] = [
                'login_medium' => $service,
                'client_id' => $data['client_id'] ?? null,
                'client_secret' => $data['client_secret'] ?? null,
                'status' => 1,
                'team_id' => $data['team_id'] ?? null,
                'key_id' => $data['key_id'] ?? null,
                'service_file' => $serviceFile ?? ($credential['service_file'] ?? null),
                'redirect_url' => $data['redirect_url'] ?? null,
            ];
        }

        return json_encode($credentials);
    }
}
