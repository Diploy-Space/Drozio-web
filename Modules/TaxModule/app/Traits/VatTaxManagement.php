<?php

namespace Modules\TaxModule\app\Traits;

use App\Enums\BusinessModule;
use Illuminate\Support\Facades\Cache;
use Modules\TaxModule\app\Models\SystemTaxSetup;
use Modules\TaxModule\app\Models\Tax;
use Modules\TaxModule\app\Models\Taxable;

trait VatTaxManagement
{
    use VatTaxConfiguration;

    public static function getTaxSystemType($getTaxVatList = true, $tax_payer = 'vendor', $businessModule = BusinessModule::RETAIL): array
    {
        $cacheKey = "tax_system_type_{$businessModule}_{$tax_payer}_".($getTaxVatList ? 'with_vat' : 'no_vat');

        $cacheKeys = Cache::get('cache_tax_system_types_and_config', []);
        if (! in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;
            Cache::put('cache_tax_system_types_and_config', $cacheKeys, 60 * 60 * 24 * 7);
        }

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($getTaxVatList, $tax_payer, $businessModule) {
            if (getCheckAddonPublishedStatus('TaxModule')) {
                $systemTaxVat = SystemTaxSetup::where('is_active', 1)
                    ->with(['additionalData' => function ($query) {
                        return $query->where('is_active', 1);
                    }])
                    ->where('tax_payer', $tax_payer)
                    ->where('business_module', $businessModule)
                    ->where('is_default', 1)
                    ->first();

                if (! $systemTaxVat) {
                    $systemTaxVat = self::getInactiveSystemVatTax(businessModule: $businessModule);

                    return [
                        'SystemTaxVat' => $systemTaxVat,
                        'SystemTaxVatType' => $systemTaxVat->tax_type,
                        'is_included' => $systemTaxVat->is_included,
                        'productWiseTax' => false,
                        'categoryWiseTax' => false,
                        'taxVats' => [],
                    ];
                }

                if ($getTaxVatList) {
                    $taxVats = Tax::where('is_active', 1)
                        ->where('is_default', 1)
                        ->where('business_module', $businessModule)
                        ->get();
                }

                if ($systemTaxVat?->tax_type == 'product_wise') {
                    $productWiseTax = true;
                } elseif ($systemTaxVat?->tax_type == 'category_wise') {
                    $categoryWiseTax = true;
                }
            }

            return [
                'SystemTaxVat' => $systemTaxVat ?? null,
                'SystemTaxVatType' => $systemTaxVat?->tax_type ?? 'order_wise',
                'is_included' => $systemTaxVat?->is_included ?? 0,
                'productWiseTax' => $productWiseTax ?? false,
                'categoryWiseTax' => $categoryWiseTax ?? false,
                'taxVats' => $taxVats ?? collect([]),
            ];
        });
    }

    public static function getAddTaxData($taxableType, $taxableId, $taxIds = [], $taxFor = 'tax_for_orders', $businessModule = BusinessModule::RETAIL): void
    {
        if (getCheckAddonPublishedStatus(moduleName: 'TaxModule')) {
            $SystemTaxVat = SystemTaxSetup::where('is_active', 1)
                ->where('business_module', $businessModule)
                ->where('is_default', 1)
                ->first();

            if ($SystemTaxVat === null) {
                return;
            }

            foreach ($taxIds as $tax_id) {
                Taxable::create([
                    'taxable_type' => $taxableType,
                    'taxable_id' => $taxableId,
                    'system_tax_setup_id' => $SystemTaxVat->id,
                    'tax_id' => $tax_id,
                    'tax_for' => $taxFor,
                ]);
            }
        }
    }

    public static function getUpdateTaxData($taxableType, $taxableId, $taxIds = [], $oldTaxIds = [], $taxFor = 'tax_for_orders', $businessModule = BusinessModule::RETAIL): void
    {
        if (getCheckAddonPublishedStatus(moduleName: 'TaxModule')) {
            $newTaxVatIds = array_map('intval', $taxIds ?? []);
            sort($newTaxVatIds);
            sort($oldTaxIds);

            if ($newTaxVatIds != $oldTaxIds) {
                Taxable::whereIn('tax_id', $oldTaxIds)
                    ->where('taxable_type', $taxableType)
                    ->where('taxable_id', $taxableId)
                    ->where('tax_for', $taxFor)
                    ->delete();
                $SystemTaxVat = SystemTaxSetup::where('is_active', 1)
                    ->where('business_module', $businessModule)
                    ->where('is_default', 1)
                    ->first();

                if ($SystemTaxVat === null) {
                    return;
                }

                foreach ($taxIds as $tax_id) {
                    Taxable::create([
                        'taxable_type' => $taxableType,
                        'taxable_id' => $taxableId,
                        'system_tax_setup_id' => $SystemTaxVat->id,
                        'tax_id' => $tax_id,
                        'tax_for' => $taxFor,
                    ]);
                }
            }
        }
    }
}
