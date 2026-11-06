<?php

namespace Modules\Courier\app\Http\Controllers\Vendor;

use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\Courier\app\Http\Requests\SaveCourierProviderRequest;
use Modules\Courier\app\Services\New\CourierConfigService;
use Modules\Courier\app\Services\New\ProviderRegistry;

class CourierConfigController extends Controller
{
    public function __construct(
        private readonly ProviderRegistry $providers,
        private readonly CourierConfigService $courierConfigService,
    ) {}

    public function index(): View
    {
        $businessModules = $this->providers->businessModules();

        return view('courier::vendor.config.index', [
            'providers' => $this->providers->catalog(),
            'businessModules' => $businessModules,
            'activeBusinessModule' => $this->providers->owner()->businessModule,
            'showBusinessModuleTabs' => count($businessModules) > 1,
        ]);
    }

    public function update(SaveCourierProviderRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('Update option is disabled for demo'));

            return redirect()->route('vendor.courier.config.index');
        }

        $data = $request->validated();
        $params = $this->settingParams(provider: $data['provider']);
        $setting = $this->courierConfigService->findBy(params: $params);
        $columns = $this->settingColumns(data: $data, setting: $setting);

        $setting === null
            ? $this->courierConfigService->create(data: $params + $columns)
            : $this->courierConfigService->update(id: $setting->id, data: $columns);

        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Delivery partner settings')]));

        return back();
    }

    /** @return array<string, mixed> */
    private function settingParams(string $provider): array
    {
        $owner = $this->providers->owner();

        return [
            'owner_type' => $owner->type,
            'owner_id' => $owner->id,
            'business_module' => $owner->businessModule,
            'provider' => $provider,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function settingColumns(array $data, ?Model $setting): array
    {
        $columns = ['is_active' => (bool) ($data['is_enabled'] ?? false)];

        if (array_key_exists('credentials', $data)) {
            $columns['credentials'] = (array) ($data['credentials'] ?? []);
        }

        $columns['settings'] = $this->settingsColumn(data: $data, setting: $setting);

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function settingsColumn(array $data, ?Model $setting): array
    {
        $settings = (array) ($setting?->settings ?? []);

        foreach (['environment', 'country'] as $key) {
            if (($data[$key] ?? null) !== null) {
                $settings[$key] = $data[$key];
            }
        }

        return $settings;
    }
}
