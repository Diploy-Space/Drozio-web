<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SEOSettingsController extends BaseController
{
    /** @var array<int, string> */
    private const WEB_MASTER_TOOL_KEYS = [
        'google_search_console_code',
        'bing_webmaster_code',
        'baidu_webmaster_code',
        'yandex_webmaster_code',
    ];

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $webMasterToolData = [];
        foreach (self::WEB_MASTER_TOOL_KEYS as $key) {
            $webMasterToolData[$key] = $this->businessSettingService->findBy(params: ['type' => $key])?->value;
        }

        return view('admin-views.seo-settings.web-master-tool', compact('webMasterToolData'));
    }

    public function updateWebMasterTool(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('You cannot update this on demo mode'));

            return redirect()->back();
        }

        foreach ($this->getWebMasterToolData(data: $request->all()) as $key => $value) {
            $this->businessSettingService->updateOrInsert(params: ['type' => $key], data: ['value' => $value]);
        }

        ToastMagic::success(translate('Updated successfully'));

        return redirect()->back();
    }

    public function getRobotTxtView(): View
    {
        $path = $this->getRobotTextPath();
        $content = File::exists($path) ? File::get($path) : '';

        return view('admin-views.seo-settings.robot-txt', compact('content', 'path'));
    }

    public function updateRobotText(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('You cannot update this on demo mode'));

            return redirect()->back();
        }

        File::put($this->getRobotTextPath(), $request['robot_text'] ?? '');
        ToastMagic::success(translate('Updated successfully'));

        return redirect()->back();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function getWebMasterToolData(array $data): array
    {
        $webMasterToolData = [];
        foreach (self::WEB_MASTER_TOOL_KEYS as $key) {
            $webMasterToolData[$key] = $data[$key] ?? '';
        }

        return $webMasterToolData;
    }

    private function getRobotTextPath(): string
    {
        return DOMAIN_POINTED_DIRECTORY == 'public' ? public_path('robots.txt') : base_path('robots.txt');
    }
}
