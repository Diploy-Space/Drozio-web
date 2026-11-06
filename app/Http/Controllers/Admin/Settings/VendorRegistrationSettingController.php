<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Vendor\Registration\BusinessProcessUpdateRequest;
use App\Http\Requests\Admin\Vendor\Registration\DownloadAppSectionUpdateRequest;
use App\Http\Requests\Admin\Vendor\Registration\HeaderUpdateRequest;
use App\Http\Requests\Admin\Vendor\Registration\SellWithUsSectionUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\HelpTopicService;
use App\Services\New\VendorRegistrationReasonService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class VendorRegistrationSettingController extends BaseController
{
    private const HEADER_SETTING_KEY = 'vendor_registration_header';

    private const SELL_WITH_US_SETTING_KEY = 'vendor_registration_sell_with_us';

    private const BUSINESS_PROCESS_SETTING_KEY = 'business_process_main_section';

    private const BUSINESS_PROCESS_STEP_SETTING_KEY = 'business_process_step';

    private const DOWNLOAD_APP_SETTING_KEY = 'download_vendor_app';

    private const IMAGE_DIRECTORY = 'vendor-registration-setting/';

    private const IMAGE_FORMAT = 'webp';

    private const BUSINESS_PROCESS_STEPS = 3;

    private const LIST_LIMIT = 10;

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
        private readonly VendorRegistrationReasonService $vendorRegistrationReasonService,
        private readonly HelpTopicService $helpTopicService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.business-settings.vendor-registration-setting.header', [
            'vendorRegistrationHeader' => $this->storedSetting(type: self::HEADER_SETTING_KEY),
        ]);
    }

    public function getSellWithUsView(Request $request): View
    {
        return view('admin-views.business-settings.vendor-registration-setting.with-us', [
            'sellWithUs' => $this->storedSetting(type: self::SELL_WITH_US_SETTING_KEY),
            'vendorRegistrationReasons' => $this->vendorRegistrationReasonService->getList(
                searchValue: $request['searchValue'],
                dataLimit: self::LIST_LIMIT,
            ),
        ]);
    }

    public function getBusinessProcessView(): View
    {
        return view('admin-views.business-settings.vendor-registration-setting.business-process', [
            'businessProcess' => $this->storedSetting(type: self::BUSINESS_PROCESS_SETTING_KEY),
            'businessProcessStep' => $this->storedSetting(type: self::BUSINESS_PROCESS_STEP_SETTING_KEY),
        ]);
    }

    public function getDownloadAppView(): View
    {
        return view('admin-views.business-settings.vendor-registration-setting.download-app', [
            'downloadVendorApp' => $this->storedSetting(type: self::DOWNLOAD_APP_SETTING_KEY),
        ]);
    }

    public function getFAQView(Request $request): View
    {
        return view('admin-views.business-settings.vendor-registration-setting.faq', [
            'helps' => $this->helpTopicService->getList(
                searchValue: $request['searchValue'],
                dataLimit: self::LIST_LIMIT,
                filters: ['type' => HelpTopicService::TYPE_VENDOR_REGISTRATION],
            ),
        ]);
    }

    public function updateHeaderSection(HeaderUpdateRequest $request): RedirectResponse
    {
        return $this->updateIntroSection(
            type: self::HEADER_SETTING_KEY,
            data: $request->all(),
            image: $request->file('image'),
        );
    }

    public function updateSellWithUsSection(SellWithUsSectionUpdateRequest $request): RedirectResponse
    {
        return $this->updateIntroSection(
            type: self::SELL_WITH_US_SETTING_KEY,
            data: $request->all(),
            image: $request->file('image'),
        );
    }

    public function updateBusinessProcess(BusinessProcessUpdateRequest $request): RedirectResponse
    {
        $storedSteps = $this->storedSetting(type: self::BUSINESS_PROCESS_STEP_SETTING_KEY);

        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::BUSINESS_PROCESS_SETTING_KEY],
            data: ['value' => json_encode([
                'title' => $request['title'],
                'sub_title' => $request['sub_title'],
                'status' => $request['status'] ?? 0,
            ])],
        );

        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::BUSINESS_PROCESS_STEP_SETTING_KEY],
            data: ['value' => json_encode($this->businessProcessSteps(request: $request, storedSteps: $storedSteps))],
        );

        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    public function updateDownloadAppSection(DownloadAppSectionUpdateRequest $request): RedirectResponse
    {
        $stored = json_decode(
            (string) $this->businessSettingService->findBy(params: ['type' => self::DOWNLOAD_APP_SETTING_KEY])?->value,
            true,
        ) ?? [];

        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::DOWNLOAD_APP_SETTING_KEY],
            data: ['value' => json_encode($this->downloadAppValue(
                data: $request->all(),
                stored: $stored,
                image: $request->file('image'),
            ))],
        );

        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    private function storedSetting(string $type): mixed
    {
        return json_decode((string) $this->businessSettingService->findBy(params: ['type' => $type])?->value);
    }

    /** @param  array<string, mixed>  $data */
    private function updateIntroSection(string $type, array $data, ?UploadedFile $image): RedirectResponse
    {
        $stored = $this->storedSetting(type: $type);

        $this->businessSettingService->updateOrInsert(
            params: ['type' => $type],
            data: ['value' => json_encode([
                'title' => $data['title'] ?? null,
                'sub_title' => $data['sub_title'] ?? null,
                'status' => $data['status'] ?? 0,
                'image' => $this->imageData(image: $image, storedImage: $stored->image ?? null),
            ])],
        );

        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    /** @return array<int, array<string, mixed>> */
    private function businessProcessSteps(Request $request, mixed $storedSteps): array
    {
        $steps = [];

        for ($index = 1; $index <= self::BUSINESS_PROCESS_STEPS; $index++) {
            $steps[] = [
                'title' => $request["section_{$index}_title"],
                'description' => $request["section_{$index}_description"],
                'image' => $this->imageData(
                    image: $request->file("section_{$index}_image"),
                    storedImage: $storedSteps[$index - 1]->image ?? null,
                ),
            ];
        }

        return $steps;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private function downloadAppValue(array $data, array $stored, ?UploadedFile $image): array
    {
        $value = [
            'title' => $stored['title'] ?? '',
            'sub_title' => $stored['sub_title'] ?? '',
            'status' => $stored['status'] ?? 0,
            'download_google_app' => $stored['download_google_app'] ?? '',
            'download_google_app_status' => $stored['download_google_app_status'] ?? 0,
            'download_apple_app' => $stored['download_apple_app'] ?? '',
            'download_apple_app_status' => $stored['download_apple_app_status'] ?? 0,
        ];

        if (isset($data['title']) || isset($data['sub_title'])) {
            $value['title'] = $data['title'] ?? '';
            $value['sub_title'] = $data['sub_title'] ?? '';
            $value['status'] = $data['status'] ?? 0;
        }

        if (isset($data['download_google_app']) || isset($data['download_apple_app'])) {
            $value['download_google_app'] = $data['download_google_app'] ?? '';
            $value['download_google_app_status'] = $data['download_google_app_status'] ?? 0;
            $value['download_apple_app'] = $data['download_apple_app'] ?? '';
            $value['download_apple_app_status'] = $data['download_apple_app_status'] ?? 0;
        }

        $value['image'] = $this->imageData(image: $image, storedImage: $stored['image'] ?? null);

        return $value;
    }

    /** @return array{image_name: ?string, storage: string} */
    private function imageData(?UploadedFile $image, mixed $storedImage): array
    {
        $current = $this->storedImageData(storedImage: $storedImage);

        if ($image === null) {
            return $current;
        }

        return $this->businessSettingService->replaceStoredImage(
            oldImageName: $current['image_name'],
            directory: self::IMAGE_DIRECTORY,
            format: self::IMAGE_FORMAT,
            image: $image,
        );
    }

    /** @return array{image_name: ?string, storage: string} */
    private function storedImageData(mixed $storedImage): array
    {
        if (is_string($storedImage)) {
            return ['image_name' => $storedImage, 'storage' => $this->defaultStorage()];
        }

        $image = (array) $storedImage;

        return [
            'image_name' => $image['image_name'] ?? null,
            'storage' => $image['storage'] ?? $this->defaultStorage(),
        ];
    }

    private function defaultStorage(): string
    {
        return config('filesystems.disks.default') ?? 'public';
    }
}
