<?php

namespace App\Http\Controllers\Admin\ThirdParty;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\ThirdParty\RecaptchaUpdateRequest;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RecaptchaController extends BaseController
{
    private const RECAPTCHA_SETTING_KEY = 'recaptcha';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $config = $this->businessSettingService->findBy(params: ['type' => self::RECAPTCHA_SETTING_KEY]);

        return view('admin-views.third-party.recaptcha-index', compact('config'));
    }

    public function update(RecaptchaUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        if ($request['status'] == 1 && (empty($request['site_key']) || empty($request['secret_key']))) {
            ToastMagic::error(translate('Please provide the credentials for active'));

            return back();
        }

        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::RECAPTCHA_SETTING_KEY],
            data: ['value' => $this->getRecaptchaValue(data: $request->all())],
        );
        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    /** @param  array<string, mixed>  $data */
    private function getRecaptchaValue(array $data): string
    {
        return json_encode([
            'status' => $data['status'] ?? 0,
            'site_key' => $data['site_key'] ?? null,
            'secret_key' => $data['secret_key'] ?? null,
        ]);
    }
}
