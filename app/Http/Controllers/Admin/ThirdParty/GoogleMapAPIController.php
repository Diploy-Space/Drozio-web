<?php

namespace App\Http\Controllers\Admin\ThirdParty;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\ThirdParty\MapApiUpdateRequest;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleMapAPIController extends BaseController
{
    private const MAP_API_KEY = 'map_api_key';

    private const MAP_API_KEY_SERVER = 'map_api_key_server';

    private const MAP_API_STATUS = 'map_api_status';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $settings = $this->settingValues(settingKeys: [self::MAP_API_KEY, self::MAP_API_KEY_SERVER, self::MAP_API_STATUS]);

        return view('admin-views.third-party.map-api.index', [
            'mapAPIKey' => $settings[self::MAP_API_KEY] ?? null,
            'mapAPIKeyServer' => $settings[self::MAP_API_KEY_SERVER] ?? null,
            'mapAPIStatus' => ($settings[self::MAP_API_STATUS] ?? 0) == 1,
        ]);
    }

    public function update(MapApiUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('Update option is disabled for demo'));

            return redirect()->route('admin.settings.third-party.map-api');
        }

        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::MAP_API_KEY],
            data: ['value' => $request['map_api_key'] ?? ''],
        );
        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::MAP_API_KEY_SERVER],
            data: ['value' => $request['map_api_key_server'] ?? ''],
        );
        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::MAP_API_STATUS],
            data: ['value' => $request->get('status', 0)],
        );

        updateSetupGuideCacheKey(key: 'google_map_apis', panel: 'admin');
        ToastMagic::success(translate('Config data updated'));

        return redirect()->route('admin.settings.third-party.map-api');
    }

    /**
     * @param  array<int, string>  $settingKeys
     * @return array<string, mixed>
     */
    private function settingValues(array $settingKeys): array
    {
        return $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => $settingKeys])
            ->pluck('value', 'type')
            ->toArray();
    }
}
