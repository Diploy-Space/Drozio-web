<?php

namespace App\Http\Controllers\Admin\BusinessSettings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Website\SetupUpdateRequest;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class WebsiteSetupController extends BaseController
{
    /** @var array<string, string> setting type => the format its image is stored in */
    private const SETUP_IMAGES = [
        'company_web_logo' => 'webp',
        'company_footer_logo' => 'webp',
        'company_fav_icon' => 'png',
        'loader_gif' => 'webp',
        'company_mobile_logo' => 'webp',
    ];

    private const INVOICE_LOGO_SOURCE = 'company_web_logo';

    private const INVOICE_LOGO = 'company_web_logo_png';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.business-settings.website-setup', [
            'businessSetting' => $this->getWebsiteSetup(),
        ]);
    }

    /** @throws Throwable */
    public function updateWebsiteSetup(SetupUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('Update option is disabled for demo'));

            return redirect()->route('admin.settings.business-settings.website-setup');
        }

        $this->storeWebsiteSetup(
            data: $request->all(),
            images: $request->websiteSetupImages(),
        );

        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Website Setup')]));

        return back();
    }

    /** @return array<string, mixed> */
    private function getWebsiteSetup(): array
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => ['colors', ...array_keys(self::SETUP_IMAGES)]])
            ->pluck('value', 'type');

        $colors = json_decode((string) $settings->get('colors'), true);

        return [
            'primary_color' => $colors['primary'] ?? '',
            'secondary_color' => $colors['secondary'] ?? '',
            'panel_sidebar' => $colors['panel-sidebar'] ?? '',
            'primary_color_light' => $colors['primary_light'] ?? '',
            'web_logo' => $this->imageLink($settings->get('company_web_logo')),
            'mob_logo' => $this->imageLink($settings->get('company_mobile_logo')),
            'fav_icon' => $this->imageLink($settings->get('company_fav_icon')),
            'footer_logo' => $this->imageLink($settings->get('company_footer_logo')),
            'loader_gif' => $this->imageLink($settings->get('loader_gif')),
        ];
    }

    /**
     * A stored image row is either a {image_name, storage} blob or a bare filename from an older
     * install; both resolve to the link the preview renders.
     *
     * @return array<string, mixed>|string
     */
    private function imageLink(?string $storedValue): array|string
    {
        if ($storedValue === null) {
            return '';
        }

        $decoded = json_decode((string) $storedValue, true);
        $image = isset($decoded['image_name']) ? $decoded : ['image_name' => $storedValue, 'storage' => 'public'];

        return storageLink(
            rtrim(BusinessSettingService::COMPANY_IMAGE_DIRECTORY, '/'),
            $image['image_name'],
            $image['storage'],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $images
     *
     * @throws Throwable
     */
    private function storeWebsiteSetup(array $data, array $images = []): void
    {
        DB::transaction(function () use ($data, $images): void {
            $this->businessSettingService->updateOrInsert(params: ['type' => 'colors'], data: ['value' => json_encode([
                'primary' => $data['primary'] ?? null,
                'secondary' => $data['secondary'] ?? null,
                'panel-sidebar' => $data['panel-sidebar'] ?? null,
                'primary_light' => $data['primary_light'] ?? null,
                'app-primary' => $data['app-primary'] ?? null,
                'app-secondary' => $data['app-secondary'] ?? null,
            ])]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'download_app_apple_store'], data: ['value' => json_encode([
                'status' => $data['app_store_download_status'] ?? 0,
                'link' => $data['app_store_download_url'] ?? null,
            ])]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'download_app_google_store'], data: ['value' => json_encode([
                'status' => $data['play_store_download_status'] ?? 0,
                'link' => $data['play_store_download_url'] ?? null,
            ])]);

            foreach (self::SETUP_IMAGES as $type => $format) {
                if (! isset($images[$type])) {
                    continue;
                }

                $this->businessSettingService->updateOrInsert(params: ['type' => $type], data: ['value' => json_encode(
                    $this->businessSettingService->storeImage(
                        type: $type,
                        directory: BusinessSettingService::COMPANY_IMAGE_DIRECTORY,
                        format: $format,
                        image: $images[$type],
                    )
                )]);
            }

            if (isset($images[self::INVOICE_LOGO_SOURCE])) {
                $this->businessSettingService->updateOrInsert(params: ['type' => self::INVOICE_LOGO], data: ['value' => json_encode(
                    $this->businessSettingService->storeImage(
                        type: self::INVOICE_LOGO,
                        directory: BusinessSettingService::COMPANY_IMAGE_DIRECTORY,
                        format: 'png',
                        image: $images[self::INVOICE_LOGO_SOURCE],
                    )
                )]);
            }
        });
    }
}
