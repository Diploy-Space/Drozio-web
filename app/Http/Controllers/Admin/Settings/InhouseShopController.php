<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\BusinessModule;
use App\Enums\WebConfigKey;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Business\InHouseShopAvailabilityRequest;
use App\Http\Requests\Admin\Setting\Business\InHouseShopSetupUpdateRequest;
use App\Http\Requests\Admin\Setting\Business\InHouseShopUpdateRequest;
use App\Http\Requests\Admin\Setting\Business\InHouseShopVacationRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewService;
use App\Services\New\ShopService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class InhouseShopController extends BaseController
{
    private const SETUP_GUIDE_KEY = 'inhouse_shop_setup';

    private const SHOP_CACHE_TYPE = 'in_house_shop';

    private const SHOP_IMAGE_DIRECTORY = 'shop/';

    /** @var array<int, string> */
    private const BANNER_SETTINGS = ['shop_banner', 'bottom_banner', 'offer_banner'];

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
        private readonly ShopService $shopService,
        private readonly ProductRetailService $productService,
        private readonly OrderService $orderService,
        private readonly ReviewService $reviewService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $inHouseProductIds = $this->productService->getInHouseIds();
        $vacation = json_decode((string) $this->businessSettingService->findBy(params: ['type' => BusinessSettingService::IN_HOUSE_VACATION])?->value, true);
        $availabilityModules = $this->getAvailabilityModules();
        $vacationModules = $this->getVacationModules();
        $shop = getInHouseShopConfig();
        $moduleAvailability = array_column($availabilityModules, 'is_open', 'key');

        $data = [
            'shop' => $shop,
            'vacation' => $vacation,
            'vacationModules' => $vacationModules,
            'vacationSchedule' => $this->shopService->getVacationSchedule(
                isOnVacation: (int) ($vacation['status'] ?? 0) === 1,
                durationType: $vacation['vacation_duration_type'] ?? null,
                startDate: $vacation['vacation_start_date'] ?? null,
                endDate: $vacation['vacation_end_date'] ?? null,
                vacationModules: $vacationModules,
            ),
            'availabilityModules' => $availabilityModules,
            'closedBusinesses' => $this->shopService->getClosedBusinessesLabel(availabilityModules: $availabilityModules),
            'moduleAvailability' => $moduleAvailability,
            'retailOverview' => $this->retailOverview(isActive: (bool) ($moduleAvailability[BusinessModule::RETAIL] ?? false), productIds: $inHouseProductIds),
            'storefrontLinks' => $this->shopService->getStorefrontLinks(slug: $shop?->slug),
        ];

        if ($request['action'] === 'edit') {
            return view('admin-views.inhouse-shop.edit', $data);
        }

        return view('admin-views.inhouse-shop.index', $data);
    }

    public function update(InHouseShopUpdateRequest $request): RedirectResponse
    {
        $shop = $this->inHouseShop();

        $this->shopService->update(
            id: $shop?->id,
            data: [
                'name' => $request['name'],
                'address' => $request['address'],
                'company_phone' => $request['contact'],
            ],
            images: [
                'image' => $request->file('image'),
                'banner' => $request->file('shop_banner'),
                'bottom_banner' => $request->file('bottom_banner'),
                'offer_banner' => $request->file('offer_banner'),
            ],
        );

        $this->updateInHouseShopBanners(banners: array_filter([
            'shop_banner' => $request->file('shop_banner'),
            'bottom_banner' => $request->file('bottom_banner'),
            'offer_banner' => $request->file('offer_banner'),
        ]));

        cacheRemoveByType(type: self::SHOP_CACHE_TYPE);
        updateSetupGuideCacheKey(key: self::SETUP_GUIDE_KEY, panel: 'admin');
        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    public function updateAvailability(InHouseShopAvailabilityRequest $request): RedirectResponse
    {
        $availability = $this->updateInHouseShopAvailability(
            moduleKeys: $request->availableModuleKeys(),
            openModuleKeys: $request['status'] ?? [],
        );

        if (array_key_exists('retail', $availability)) {
            $this->shopService->updateBy(
                params: ['author_type' => ShopService::AUTHOR_TYPE_ADMIN],
                data: ['temporary_close' => $availability['retail'] ? 0 : 1],
            );
        }

        cacheRemoveByType(type: self::SHOP_CACHE_TYPE);
        updateSetupGuideCacheKey(key: self::SETUP_GUIDE_KEY, panel: 'admin');
        ToastMagic::success(translate('Availability updated successfully'));

        return back();
    }

    public function updateVacation(InHouseShopVacationRequest $request): RedirectResponse
    {
        $shop = $this->inHouseShop();
        $vacation = [
            'status' => $request['status'],
            'vacation_duration_type' => $request['vacation_duration_type'],
            ...$request->vacationPeriod(),
            'vacation_note' => $request['vacation_note'],
        ];

        $this->shopService->updateBy(
            params: ['id' => $shop?->id],
            data: $this->vacationColumns([...$vacation, 'vacation_status' => $vacation['status']]),
        );

        $this->updateInHouseVacation(
            data: $vacation,
            moduleKeys: $request['modules'] ?? [],
        );

        cacheRemoveByType(type: self::SHOP_CACHE_TYPE);
        ToastMagic::success(translate('Vacation mode updated successfully'));

        return back();
    }

    public function getSetupView(): View
    {
        return view('admin-views.inhouse-shop.setup', $this->getInHouseOrderSetup(
            moduleLabels: $this->getModuleLabels(),
        ));
    }

    public function updateSetup(InHouseShopSetupUpdateRequest $request): RedirectResponse
    {
        foreach ($request->minimumOrderAmountInput() as $amountField => $amount) {
            $this->businessSettingService->updateOrInsert(
                params: ['type' => $amountField],
                data: ['value' => currencyConverter(amount: $amount)],
            );
        }

        if ($request->has(WebConfigKey::IN_HOUSE_SHOP_VERIFIED_BADGE_STATUS)) {
            $this->businessSettingService->updateOrInsert(
                params: ['type' => WebConfigKey::IN_HOUSE_SHOP_VERIFIED_BADGE_STATUS],
                data: ['value' => $request->boolean(WebConfigKey::IN_HOUSE_SHOP_VERIFIED_BADGE_STATUS) ? 1 : 0],
            );
        }

        $this->updateInHouseFreeDeliverySetup(freeDelivery: $request->freeDeliveryInput());
        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    /** @return array<string, mixed> */
    private function retailOverview(bool $isActive, array $productIds): array
    {
        $rating = $this->reviewService->getRatingSummary(filters: ['product_id' => $productIds], precision: 1);

        return [
            'statLabel' => translate('Retail'),
            'statIsActive' => $isActive,
            'statMetrics' => [
                translate('Products') => count($productIds),
                translate('Orders') => $this->orderService->getInHouseCount(),
            ],
            'statRating' => [
                'average' => $rating['avg_rating'],
                'ratings' => $rating['total_review'],
                'reviews' => $rating['total_comment'],
            ],
        ];
    }

    /** @return array<int, array{key: string, label: string, description_on: string, description_off: string, is_open: bool}> */
    private function getAvailabilityModules(): array
    {
        $moduleLabels = $this->getModuleLabels();

        return $this->shopService->getAvailabilityModules(
            moduleLabels: $moduleLabels,
            availability: $this->getInHouseShopAvailability(moduleKeys: array_keys($moduleLabels)),
        );
    }

    /** @return array<int, array{key: string, label: string, is_selected: bool}> */
    private function getVacationModules(): array
    {
        $selectedModuleKeys = json_decode((string) $this->businessSettingService->findBy(params: ['type' => BusinessSettingService::IN_HOUSE_VACATION_MODULES])?->value, true);

        return $this->shopService->getVacationModules(
            moduleLabels: $this->getModuleLabels(),
            selectedModuleKeys: is_array($selectedModuleKeys) ? $selectedModuleKeys : null,
        );
    }

    private function inHouseShop(array $relations = []): mixed
    {
        return $this->shopService->findBy(
            params: ['author_type' => ShopService::AUTHOR_TYPE_ADMIN],
            relations: $relations,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function vacationColumns(array $data): array
    {
        return [
            'vacation_status' => ($data['vacation_status'] ?? 0) == 1 ? 1 : 0,
            'vacation_duration_type' => $data['vacation_duration_type'] ?? null,
            'vacation_start_date' => $data['vacation_start_date'] ?? null,
            'vacation_end_date' => $data['vacation_end_date'] ?? null,
            'vacation_note' => $data['vacation_note'] ?? null,
        ];
    }

    /** @return array<string, string> */
    private function getModuleLabels(): array
    {
        return array_map(fn ($module) => $module->label, businessModuleManager()->availableInPanel());
    }

    /**
     * @param  string[]  $moduleKeys
     * @return array<string, bool>
     */
    private function getInHouseShopAvailability(array $moduleKeys): array
    {
        $types = array_intersect_key(BusinessSettingService::IN_HOUSE_AVAILABILITY_SETTINGS, array_flip($moduleKeys));
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => array_values($types)])
            ->pluck('value', 'type');
        $availability = [];

        foreach ($types as $moduleKey => $type) {
            $status = json_decode((string) $settings->get($type), true)['status'] ?? 0;
            $availability[$moduleKey] = (int) $status === 0;
        }

        return $availability;
    }

    /**
     * @param  string[]  $moduleKeys
     * @param  string[]  $openModuleKeys
     * @return array<string, bool>
     */
    private function updateInHouseShopAvailability(array $moduleKeys, array $openModuleKeys): array
    {
        $availability = [];

        foreach ($moduleKeys as $moduleKey) {
            if (! isset(BusinessSettingService::IN_HOUSE_AVAILABILITY_SETTINGS[$moduleKey])) {
                continue;
            }

            $isOpen = in_array($moduleKey, $openModuleKeys, true);
            $this->businessSettingService->updateOrInsert(
                params: ['type' => BusinessSettingService::IN_HOUSE_AVAILABILITY_SETTINGS[$moduleKey]],
                data: ['value' => json_encode(['status' => $isOpen ? 0 : 1])],
            );
            $availability[$moduleKey] = $isOpen;
        }

        return $availability;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $moduleKeys
     *
     * @throws Throwable
     */
    private function updateInHouseVacation(array $data, array $moduleKeys): void
    {
        DB::transaction(function () use ($data, $moduleKeys): void {
            $this->businessSettingService->updateOrInsert(params: ['type' => BusinessSettingService::IN_HOUSE_VACATION], data: ['value' => json_encode([
                'status' => ($data['status'] ?? 0) == 1 ? 1 : 0,
                'vacation_duration_type' => $data['vacation_duration_type'] ?? null,
                'vacation_start_date' => $data['vacation_start_date'] ?? null,
                'vacation_end_date' => $data['vacation_end_date'] ?? null,
                'vacation_note' => $data['vacation_note'] ?? null,
            ])]);

            $this->businessSettingService->updateOrInsert(
                params: ['type' => BusinessSettingService::IN_HOUSE_VACATION_MODULES],
                data: ['value' => json_encode($moduleKeys)],
            );
        });
    }

    /** @param  array<string, UploadedFile>  $banners */
    private function updateInHouseShopBanners(array $banners): void
    {
        foreach (self::BANNER_SETTINGS as $type) {
            if (! isset($banners[$type])) {
                continue;
            }

            $this->businessSettingService->updateOrInsert(params: ['type' => $type], data: ['value' => json_encode(
                $this->businessSettingService->storeImage(
                    type: $type,
                    directory: self::SHOP_IMAGE_DIRECTORY,
                    format: 'webp',
                    image: $banners[$type],
                )
            )]);
        }
    }

    /**
     * @param  array<string, string>  $moduleLabels
     * @return array<string, mixed>
     */
    private function getInHouseOrderSetup(array $moduleLabels): array
    {
        $settings = $this->businessSettingService->getList(dataLimit: 'all', filters: ['type' => [
            WebConfigKey::IN_HOUSE_SHOP_VERIFIED_BADGE_STATUS,
            WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS,
        ]])->pluck('value', 'type');

        return [
            'isInHouseShopVerified' => (bool) $settings->get(WebConfigKey::IN_HOUSE_SHOP_VERIFIED_BADGE_STATUS),
            'isVerifiedBadgeEnabled' => (bool) $settings->get(WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS),
            'minimumOrderAmountModules' => $this->getInHouseMinimumOrderAmountSetup(moduleLabels: $moduleLabels),
            'freeDeliveryModules' => $this->getInHouseFreeDeliverySetup(moduleLabels: $moduleLabels),
        ];
    }

    /**
     * @param  array<string, string>  $moduleLabels
     * @return array<string, array{key: string, label: string, amount_field: string, amount: float}>
     */
    private function getInHouseMinimumOrderAmountSetup(array $moduleLabels): array
    {
        $moduleFields = array_intersect_key(BusinessSettingService::IN_HOUSE_MINIMUM_ORDER_AMOUNT_SETTINGS, $moduleLabels);
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => array_merge(...array_map('array_values', array_values($moduleFields)))])
            ->pluck('value', 'type');

        $rows = [];

        foreach ($moduleFields as $moduleKey => $fields) {
            if (! $settings->get($fields['status'])) {
                continue;
            }

            $rows[$moduleKey] = [
                'key' => $moduleKey,
                'label' => translate($moduleLabels[$moduleKey]),
                'amount_field' => $fields['amount'],
                'amount' => (float) $settings->get($fields['amount']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $moduleLabels
     * @return array<int, array{key: string, label: string, status_field: string, amount_field: string, is_active: bool, amount: float}>
     */
    private function getInHouseFreeDeliverySetup(array $moduleLabels): array
    {
        $moduleFields = [];
        $types = [];

        foreach ($moduleLabels as $moduleKey => $label) {
            $fields = BusinessSettingService::IN_HOUSE_FREE_DELIVERY_SETTINGS[$moduleKey] ?? null;

            if ($fields !== null) {
                $moduleFields[$moduleKey] = $fields;
                $types = [...$types, ...array_values($fields)];
            }
        }

        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => $types])
            ->pluck('value', 'type');

        $rows = [];

        foreach ($moduleFields as $moduleKey => $fields) {
            $label = $moduleLabels[$moduleKey];
            $status = $settings->get($fields['status']);
            $amount = (float) $settings->get($fields['amount']);

            $rows[] = [
                'key' => $moduleKey,
                'label' => translate($label),
                'status_field' => $fields['status'],
                'amount_field' => $fields['amount'],
                'is_active' => $status === null ? $amount > 0 : (bool) $status,
                'amount' => $amount,
            ];
        }

        return $rows;
    }

    /** @param  array<string, array{status: bool, amount: float}>  $freeDelivery */
    private function updateInHouseFreeDeliverySetup(array $freeDelivery): void
    {
        foreach ($freeDelivery as $moduleKey => $values) {
            $fields = BusinessSettingService::IN_HOUSE_FREE_DELIVERY_SETTINGS[$moduleKey] ?? null;

            if ($fields === null) {
                continue;
            }

            $this->businessSettingService->updateOrInsert(params: ['type' => $fields['status']], data: ['value' => $values['status'] ? 1 : 0]);
            $this->businessSettingService->updateOrInsert(params: ['type' => $fields['amount']], data: ['value' => currencyConverter(amount: $values['amount'])]);
        }
    }
}
