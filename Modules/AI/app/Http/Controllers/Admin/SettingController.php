<?php

namespace Modules\AI\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\AI\app\Services\New\AISettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\AI\app\Http\Requests\AICustomerUsagesLimitRequest;
use Modules\AI\app\Http\Requests\AISettingRequest;
use Modules\AI\app\Http\Requests\AIVendorUsagesLimitRequest;

class SettingController extends Controller
{
    public function __construct(private readonly AISettingService $aiSettingService) {}

    public function index(): View
    {
        return view('ai::admin-views.ai-setting.index', ['AiSetting' => $this->aiSettingService->findBy(params: [])]);
    }

    public function getVendorUsagesLimitView(): View
    {
        return view('ai::admin-views.ai-setting.vendors-usage-limits', ['AiSetting' => $this->aiSettingService->findBy(params: [])]);
    }

    public function getCustomerUsagesLimitView(): View
    {
        return view('ai::admin-views.ai-setting.customers-usage-limits', ['AiSetting' => $this->aiSettingService->findBy(params: [])]);
    }

    public function store(AISettingRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return redirect()->back();
        }

        $apiKey = $request['api_key'];
        $organizationId = $request['organization_id'];
        $hasCredentials = ! empty($apiKey) && ! empty($organizationId);

        return $this->savedResponse($this->saveConfiguration([
            'api_key' => $apiKey,
            'organization_id' => $organizationId,
            'status' => $hasCredentials && (int) $request['status'] === 1 ? 1 : 0,
        ]));
    }

    public function updateVendorUsagesLimit(AIVendorUsagesLimitRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return redirect()->back();
        }

        return $this->savedResponse($this->saveConfiguration([
            'image_upload_limit' => (int) $request['image_upload_limit'],
            'generate_limit' => (int) $request['generate_limit'],
        ]));
    }

    public function updateCustomerUsagesLimit(AICustomerUsagesLimitRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return redirect()->back();
        }

        return $this->savedResponse($this->saveConfiguration([
            'customer_generate_limit' => (int) $request['customer_generate_limit'],
            'customer_image_upload_limit' => (int) $request['customer_image_upload_limit'],
        ]));
    }

    private function saveConfiguration(array $attributes): bool
    {
        $configuration = $this->aiSettingService->findBy(params: []) ?? $this->aiSettingService->create(data: []);

        return $configuration !== null
            && $this->aiSettingService->update(id: $configuration->id, data: $attributes) !== null;
    }

    private function savedResponse(bool $saved): RedirectResponse
    {
        $saved
            ? ToastMagic::success(translate(':item saved successfully', ['item' => translate('AI Configuration')]))
            : ToastMagic::error(translate('Failed to save AI configuration'));

        return redirect()->back();
    }
}
