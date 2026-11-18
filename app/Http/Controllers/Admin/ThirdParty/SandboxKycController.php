<?php

namespace App\Http\Controllers\Admin\ThirdParty;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\ThirdParty\SandboxKycUpdateRequest;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SandboxKycController extends BaseController
{
    public const SETTING_KEY = 'sandbox_kyc_api';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $config = $this->getConfig();

        return view('admin-views.third-party.sandbox-kyc.index', compact('config'));
    }

    public function update(SandboxKycUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('Update option is disabled for demo'));
            return redirect()->route('admin.settings.third-party.sandbox-kyc');
        }

        $existing = $this->getConfig();
        $status = (int) $request->input('status', 0);
        $apiKey = $request->input('api_key');
        $apiSecret = $request->input('api_secret');

        // Keep existing secrets if fields left blank (common masked-edit pattern)
        if (empty($apiKey)) {
            $apiKey = $existing['api_key'] ?? '';
        }
        if (empty($apiSecret)) {
            $apiSecret = $existing['api_secret'] ?? '';
        }

        if ($status === 1 && (empty($apiKey) || empty($apiSecret))) {
            ToastMagic::error(translate('Please provide Sandbox API key and secret to activate'));
            return back();
        }

        $value = [
            'status' => $status,
            'mode' => $request->input('mode', 'test'),
            'base_url_test' => rtrim((string) $request->input('base_url_test', 'https://test-api.sandbox.co.in'), '/'),
            'base_url_live' => rtrim((string) $request->input('base_url_live', 'https://api.sandbox.co.in'), '/'),
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'api_version' => $request->input('api_version', '1.0'),
            'verify_pan' => (int) $request->input('verify_pan', 1),
            'verify_gstin' => (int) $request->input('verify_gstin', 1),
            'verify_bank' => (int) $request->input('verify_bank', 1),
            'manual_fallback' => (int) $request->input('manual_fallback', 1),
        ];

        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::SETTING_KEY],
            data: ['value' => json_encode($value)],
        );

        ToastMagic::success(translate('Sandbox KYC API settings updated successfully'));
        return redirect()->route('admin.settings.third-party.sandbox-kyc');
    }

    public function getConfig(): array
    {
        $setting = $this->businessSettingService->findBy(params: ['type' => self::SETTING_KEY]);
        $config = $setting ? (json_decode((string) $setting->value, true) ?: []) : [];
        return array_merge([
            'status' => 0,
            'mode' => 'test',
            'base_url_test' => 'https://test-api.sandbox.co.in',
            'base_url_live' => 'https://api.sandbox.co.in',
            'api_key' => '',
            'api_secret' => '',
            'api_version' => '1.0',
            'verify_pan' => 1,
            'verify_gstin' => 1,
            'verify_bank' => 1,
            'manual_fallback' => 1,
        ], $config);
    }
}
