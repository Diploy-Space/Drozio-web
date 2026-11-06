<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Website\FeatureSectionUpdateRequest;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeaturesSectionController extends BaseController
{
    private const TOP_SETTING_KEY = 'features_section_top';

    private const MIDDLE_SETTING_KEY = 'features_section_middle';

    private const BOTTOM_SETTING_KEY = 'features_section_bottom';

    private const COMPANY_RELIABILITY_SETTING_KEY = 'company_reliability';

    private const ICON_DIRECTORY = 'banner/';

    private const RELIABILITY_IMAGE_DIRECTORY = 'company-reliability/';

    private const IMAGE_FORMAT = 'webp';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.pages-and-media.features-section.view', [
            'featuresSectionTop' => $this->businessSettingService->findBy(params: ['type' => self::TOP_SETTING_KEY]),
            'featuresSectionMiddle' => $this->businessSettingService->findBy(params: ['type' => self::MIDDLE_SETTING_KEY]),
            'featuresSectionBottom' => $this->businessSettingService->findBy(params: ['type' => self::BOTTOM_SETTING_KEY]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $storedBottom = $this->businessSettingService->findBy(params: ['type' => self::BOTTOM_SETTING_KEY])?->value;

        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::TOP_SETTING_KEY],
            data: ['value' => json_encode($request[self::TOP_SETTING_KEY])],
        );

        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::MIDDLE_SETTING_KEY],
            data: ['value' => json_encode($this->middleSectionItems(section: $request[self::MIDDLE_SETTING_KEY]))],
        );

        if ($request[self::BOTTOM_SETTING_KEY]) {
            $this->businessSettingService->updateOrInsert(
                params: ['type' => self::BOTTOM_SETTING_KEY],
                data: ['value' => json_encode($this->bottomSectionItems(
                    section: $request[self::BOTTOM_SETTING_KEY],
                    icons: $request->file(self::BOTTOM_SETTING_KEY.'_icon') ?? [],
                    stored: $storedBottom,
                ))],
            );
        }

        clearWebConfigCacheKeys();

        return back();
    }

    public function delete(Request $request): JsonResponse
    {
        $stored = $this->businessSettingService->findBy(params: ['type' => self::BOTTOM_SETTING_KEY]);

        if ($stored !== null) {
            $this->businessSettingService->updateOrInsert(
                params: ['type' => self::BOTTOM_SETTING_KEY],
                data: ['value' => json_encode($this->bottomSectionItemsWithout(
                    stored: $stored->value,
                    title: $request['title'],
                    subtitle: $request['subtitle'],
                ))],
            );
        }

        clearWebConfigCacheKeys();

        return response()->json(['status' => 'success']);
    }

    public function getCompanyReliabilityView(): View
    {
        return view('admin-views.pages-and-media.company-reliability.index', [
            'companyReliabilityData' => $this->businessSettingService->findBy(params: ['type' => self::COMPANY_RELIABILITY_SETTING_KEY]),
        ]);
    }

    public function updateCompanyReliability(FeatureSectionUpdateRequest $request): RedirectResponse
    {
        $stored = $this->businessSettingService->findBy(params: ['type' => self::COMPANY_RELIABILITY_SETTING_KEY]);

        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::COMPANY_RELIABILITY_SETTING_KEY],
            data: ['value' => json_encode($this->companyReliabilityItems(request: $request, stored: $stored?->value))],
        );

        clearWebConfigCacheKeys();
        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    /** @return array<int, array{title: string, subtitle: string}> */
    private function middleSectionItems(mixed $section): array
    {
        if (empty($section['title'])) {
            return [];
        }

        $items = [];

        foreach (array_keys($section['title']) as $key) {
            $items[] = [
                'title' => $section['title'][$key] ?? '',
                'subtitle' => $section['subtitle'][$key] ?? '',
            ];
        }

        return $items;
    }

    /**
     * @param  array<int, mixed>  $icons
     * @return array<int, mixed>
     */
    private function bottomSectionItems(mixed $section, array $icons, mixed $stored): array
    {
        $items = json_decode((string) $stored) ?: [];

        foreach (array_keys($section['title']) as $key) {
            $items[] = [
                'title' => $section['title'][$key],
                'subtitle' => $section['subtitle'][$key],
                'icon' => isset($icons[$key])
                    ? $this->businessSettingService->replaceStoredImage(
                        oldImageName: null,
                        directory: self::ICON_DIRECTORY,
                        format: self::IMAGE_FORMAT,
                        image: $icons[$key],
                    )
                    : null,
            ];
        }

        return $items;
    }

    /** @return array<int, mixed> */
    private function bottomSectionItemsWithout(mixed $stored, mixed $title, mixed $subtitle): array
    {
        $items = [];

        foreach (json_decode((string) $stored) ?: [] as $item) {
            if ($title != $item->title && $subtitle != $item->subtitle) {
                $items[] = $item;

                continue;
            }

            $this->businessSettingService->deleteStoredImage(
                directory: self::ICON_DIRECTORY,
                imageName: $item?->icon?->image_name ?? $item?->icon,
            );
        }

        return $items;
    }

    /** @return array<int, array<string, mixed>> */
    private function companyReliabilityItems(FeatureSectionUpdateRequest $request, mixed $stored): array
    {
        $items = [];

        foreach (json_decode((string) $stored, true) ?: [] as $key => $item) {
            $index = $key + 1;

            if ($item['item'] != $request["item_{$index}"]) {
                $items[] = [
                    'item' => $item['item'],
                    'title' => $item['title'],
                    'image' => $item['image'],
                    'status' => $item['status'] ?? 0,
                ];

                continue;
            }

            $items[] = [
                'item' => $request["item_{$index}"],
                'title' => $request["title_{$index}"] ?? '',
                'image' => $this->reliabilityImage(image: $request->file("image_{$index}"), storedImage: $item['image']),
                'status' => $request["status_{$index}"] ?? 0,
            ];
        }

        return $items;
    }

    private function reliabilityImage(mixed $image, mixed $storedImage): mixed
    {
        if ($image === null) {
            return $storedImage;
        }

        return $this->businessSettingService->replaceStoredImage(
            oldImageName: is_array($storedImage) ? ($storedImage['image_name'] ?? null) : $storedImage,
            directory: self::RELIABILITY_IMAGE_DIRECTORY,
            format: self::IMAGE_FORMAT,
            image: $image,
        );
    }
}
