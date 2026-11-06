<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\System\AddonPurchaseCodeRequest;
use App\Services\New\AddonService;
use App\Services\New\BusinessSettingService;
use App\Traits\AddonListResponseTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class AddonActivationController extends BaseController
{
    use AddonListResponseTrait;

    private const ACTIVATION_PREFIX = 'addon_activation_';

    private const DELIVERY_MAN_APP_ADDON = 'delivery_man_app';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
        private readonly AddonService $addonService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.system-setup.addons.addon-activation', [
            'addonData' => [
                'deliveryman_app' => $this->getAddonActivation(addon: self::DELIVERY_MAN_APP_ADDON),
            ],
        ]);
    }

    public function activation(AddonPurchaseCodeRequest $request): Redirector|RedirectResponse|Application
    {
        $activation = $this->addonService->activateByPurchaseKey(data: $request->all());

        if (! $activation['status']) {
            ToastMagic::error($activation['message'] ?: translate('Activation failed'));

            return back();
        }

        $this->updateAddonActivation(
            addon: self::DELIVERY_MAN_APP_ADDON,
            data: $request->validated() + ['activation_status' => $request['status'] ?? 0],
        );
        ToastMagic::success(translate('Activated successfully'));

        return back();
    }

    public function activateFromList(AddonPurchaseCodeRequest $request): Redirector|RedirectResponse|Application|JsonResponse
    {
        $addon = $this->addonService->find(id: $request['path']);

        if ($addon === null) {
            return $this->respond(request: $request, succeeded: false, message: translate('Invalid addon'));
        }

        $activation = $this->addonService->activateByPurchaseKey(data: $request->all());

        if (! $activation['status']) {
            return $this->respond(
                request: $request,
                succeeded: false,
                message: $activation['message'] ?: translate('Activation failed'),
            );
        }

        $this->addonService->update(id: $request['path'], data: [
            'username' => $request['username'],
            'purchase_code' => $request['purchase_key'],
        ]);

        $this->updateAddonActivation(
            addon: $addon['module_name'] ?? $request['addon_name'],
            data: $request->validated() + ['activation_status' => 1],
        );

        $published = $this->addonService->updateStatus(id: $request['path'], status: true);

        if (! $published['status']) {
            return $this->respond(
                request: $request,
                succeeded: false,
                message: translate('License activated but the addon could not be turned on').' — '.$this->failureMessage($published),
            );
        }

        return $this->respond(
            request: $request,
            succeeded: true,
            message: translate(':item activated successfully', ['item' => $addon['name'] ?? $addon['module_name'] ?? translate('Addon')]),
        );
    }

    /** @return array{activation_status: int, name: string, email: string, username: string, purchase_key: string} */
    private function getAddonActivation(string $addon): array
    {
        $row = $this->businessSettingService->findBy(params: ['type' => self::ACTIVATION_PREFIX.strtolower($addon)]);
        $stored = json_decode((string) $row?->value, true);
        $stored = is_array($stored) ? $stored : [];

        return [
            'activation_status' => (int) ($stored['activation_status'] ?? 0),
            'name' => (string) ($stored['name'] ?? ''),
            'email' => (string) ($stored['email'] ?? ''),
            'username' => (string) ($stored['username'] ?? ''),
            'purchase_key' => (string) ($stored['purchase_key'] ?? ''),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function updateAddonActivation(string $addon, array $data): void
    {
        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::ACTIVATION_PREFIX.strtolower($addon)],
            data: ['value' => json_encode([
                'activation_status' => $data['activation_status'] ?? 0,
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
                'username' => $data['username'] ?? '',
                'purchase_key' => $data['purchase_key'] ?? '',
            ])],
        );
    }
}
