<?php

namespace Modules\AI\app\Services\Auction;

use App\Enums\BusinessModule;
use App\Models\Brand;
use App\Models\Category;
use Modules\Auction\app\Enums\ItemCondition;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class AuctionResourceService
{
    use VatTaxManagement;

    private const POSITION_CATEGORY = 0;

    public function generalSetupData(): array
    {
        return [
            'categories' => $this->getAuctionCategoryOptions(),
            'brands' => $this->getAuctionBrandOptions(),
            'product_types' => ['physical'],
            'item_conditions' => $this->getItemConditionOptions(),
        ];
    }

    private function getItemConditionOptions(): array
    {
        return addon_published_status(moduleName: 'Auction') ? ItemCondition::ALL : [];
    }

    public function taxOptions(): array
    {
        $taxData = $this->getTaxSystemType();
        $taxVats = $taxData['taxVats'];

        if (is_array($taxVats)) {
            $taxVats = collect($taxVats);
        }

        return $taxVats
            ->mapWithKeys(function ($tax) {
                $name = strtolower((string) ($tax->name ?? $tax['name'] ?? ''));
                $id = (int) ($tax->id ?? $tax['id'] ?? 0);

                return $name !== '' && $id > 0 ? [$name => $id] : [];
            })
            ->toArray();
    }

    private function getAuctionCategoryOptions(): array
    {
        return Category::query()
            ->businessModule(BusinessModule::AUCTION)
            ->where('position', self::POSITION_CATEGORY)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Category $category): array => [strtolower($category->name) => $category->id])
            ->toArray();
    }

    private function getAuctionBrandOptions(): array
    {
        return Brand::query()
            ->businessModule(BusinessModule::AUCTION)
            ->active()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Brand $brand): array => [strtolower($brand->name) => $brand->id])
            ->toArray();
    }
}
