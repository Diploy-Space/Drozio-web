<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrioritySetupController extends BaseController
{
    public const FEATURE_DEAL_PRIORITY = 'feature_deal_priority';

    public const FLASH_DEAL_PRIORITY = 'flash_deal_priority';

    /**
     * Every storefront rail this screen sorts, and the fields that rail's row stores. A rail
     * carries only the controls its list can act on, which is why no two are alike.
     *
     * @var array<string, array<int, string>>
     */
    private const SETUP_SETTINGS = [
        'brand_list_priority' => ['custom_sorting_status', 'sort_by'],
        'category_list_priority' => ['custom_sorting_status', 'sort_by'],
        'vendor_list_priority' => ['custom_sorting_status', 'sort_by', 'vacation_mode_sorting', 'temporary_close_sorting'],
        'featured_product_priority' => ['custom_sorting_status', 'sort_by', 'out_of_stock_product', 'temporary_close_sorting'],
        'top_vendor_list_priority' => ['custom_sorting_status', 'minimum_rating_point', 'sort_by', 'vacation_mode_sorting', 'temporary_close_sorting'],
        'category_wise_product_list_priority' => ['custom_sorting_status', 'sort_by', 'out_of_stock_product', 'temporary_close_sorting'],
        'top_rated_product_list_priority' => ['custom_sorting_status', 'minimum_rating_point', 'sort_by', 'out_of_stock_product', 'temporary_close_sorting'],
        'best_selling_product_list_priority' => ['custom_sorting_status', 'sort_by', 'out_of_stock_product', 'temporary_close_sorting'],
        'searched_product_list_priority' => ['custom_sorting_status', 'out_of_stock_product', 'temporary_close_sorting'],
        'vendor_product_list_priority' => ['custom_sorting_status', 'sort_by', 'out_of_stock_product'],
        'new_arrival_product_list_priority' => ['custom_sorting_status', 'duration', 'duration_type', 'sort_by', 'out_of_stock_product', 'temporary_close_sorting'],
    ];

    /**
     * The deal rails, sorted from their own list screen rather than this one.
     *
     * @var array<string, array<int, string>>
     */
    private const DEAL_SETTINGS = [
        self::FEATURE_DEAL_PRIORITY => ['custom_sorting_status', 'sort_by', 'out_of_stock_product', 'temporary_close_sorting'],
        self::FLASH_DEAL_PRIORITY => ['custom_sorting_status', 'sort_by', 'out_of_stock_product', 'temporary_close_sorting'],
    ];

    /** @var array<string, string> priority setting type => the name this screen renders it under */
    private const VIEW_KEYS = [
        'brand_list_priority' => 'brandPriority',
        'category_list_priority' => 'categoryPriority',
        'vendor_list_priority' => 'vendorListPriority',
        'featured_product_priority' => 'featureProductPriority',
        'top_vendor_list_priority' => 'topVendorPriority',
        'category_wise_product_list_priority' => 'categoryWiseProductListPriority',
        'top_rated_product_list_priority' => 'topRatedProductListPriority',
        'best_selling_product_list_priority' => 'bestSellingProductListPriority',
        'searched_product_list_priority' => 'searchedProductListPriority',
        'vendor_product_list_priority' => 'vendorProductListPriority',
        'new_arrival_product_list_priority' => 'newArrivalProductListPriority',
    ];

    /** @var array<string, mixed> */
    private const FIELD_DEFAULTS = [
        'custom_sorting_status' => 0,
        'duration' => 1,
        'duration_type' => 'month',
    ];

    private const CACHE_TYPES = ['shops', 'brands', 'categories'];

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.business-settings.priority-setup.index', $this->getPrioritySetup());
    }

    public function update(Request $request): RedirectResponse
    {
        $this->storePrioritySetup(data: $request->all());
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Priority Setup')]));

        return back();
    }

    public function updateByType(Request $request): RedirectResponse
    {
        $this->storeDealPrioritySetup(setting: $request['type'], data: $request->all());
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Priority Setup')]));

        return back();
    }

    /**
     * Every rail this screen renders, decoded as its partials read it: an object, or null where
     * the rail has never been saved.
     *
     * @return array<string, ?object>
     */
    private function getPrioritySetup(): array
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => array_keys(self::SETUP_SETTINGS)])
            ->keyBy('type');

        $setup = [];
        foreach (self::VIEW_KEYS as $setting => $viewKey) {
            $setup[$viewKey] = json_decode((string) $settings->get($setting)?->value) ?: null;
        }

        return $setup;
    }

    /**
     * A rail the form did not post keeps what it has, so a section hidden behind a business
     * module is never reset to its defaults.
     *
     * @param  array<string, array<string, mixed>>  $data
     */
    private function storePrioritySetup(array $data): void
    {
        DB::transaction(function () use ($data): void {
            foreach (self::SETUP_SETTINGS as $setting => $fields) {
                if (empty($data[$setting])) {
                    continue;
                }

                $this->businessSettingService->updateOrInsert(
                    params: ['type' => $setting],
                    data: ['value' => json_encode($this->priorityValues($fields, $data[$setting]))],
                );
            }
        });

        foreach (self::CACHE_TYPES as $cacheType) {
            cacheRemoveByType(type: $cacheType);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function storeDealPrioritySetup(string $setting, array $data): void
    {
        if (! isset(self::DEAL_SETTINGS[$setting])) {
            return;
        }

        $this->businessSettingService->updateOrInsert(
            params: ['type' => $setting],
            data: ['value' => json_encode($this->priorityValues(self::DEAL_SETTINGS[$setting], $data))],
        );

        if ($setting === self::FLASH_DEAL_PRIORITY) {
            cacheRemoveByType(type: 'flash_deals');
        }
    }

    /**
     * @param  array<int, string>  $fields
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function priorityValues(array $fields, array $data): array
    {
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $data[$field] ?? self::FIELD_DEFAULTS[$field] ?? null;
        }

        return $values;
    }
}
