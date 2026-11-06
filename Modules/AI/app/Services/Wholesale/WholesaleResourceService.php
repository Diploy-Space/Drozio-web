<?php

namespace Modules\AI\app\Services\Wholesale;

use App\Enums\BusinessModule;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use Modules\AI\app\Services\ProductResourceService;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class WholesaleResourceService
{
    use VatTaxManagement;

    protected Category $category;

    protected Brand $brand;

    protected Attribute $attribute;

    protected Color $color;

    protected ProductResourceService $productResourceService;

    public function __construct()
    {
        $this->category = new Category;
        $this->brand = new Brand;
        $this->attribute = new Attribute;
        $this->color = new Color;
        $this->productResourceService = new ProductResourceService;
    }

    public function generalSetupData(): array
    {
        return [
            'categories' => $this->topLevelCategories(),
            'sub_categories' => $this->childCategoriesByParent(1),
            'sub_sub_categories' => $this->childCategoriesByParent(2),
            'brands' => $this->brands(),
            'units' => $this->units(),
        ];
    }

    public function variationData(): array
    {
        return [
            'attributes' => $this->attribute->newQuery()
                ->businessModule(BusinessModule::WHOLESALE)
                ->get(['id', 'name'])
                ->mapWithKeys(fn ($item) => [strtolower($item->name) => $item->id])
                ->toArray(),
            'color' => $this->color->newQuery()
                ->get(['id', 'name', 'code'])
                ->mapWithKeys(fn ($item) => [
                    strtolower($item->name) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'code' => $item->code,
                    ],
                ])
                ->toArray(),
        ];
    }

    public function getTaxOptions(): array
    {
        $taxData = $this->getTaxSystemType(businessModule: BusinessModule::WHOLESALE);

        if (! $taxData['productWiseTax'] || $taxData['is_included']) {
            return [];
        }

        return collect($taxData['taxVats'])
            ->mapWithKeys(function ($tax): array {
                $name = strtolower((string) ($tax->name ?? ''));
                $id = (int) ($tax->id ?? 0);

                return $name !== '' && $id > 0 ? [$name => $id] : [];
            })
            ->all();
    }

    public function units(): array
    {
        return $this->productResourceService->units();
    }

    private function topLevelCategories(): array
    {
        return $this->category->newQuery()
            ->businessModule(BusinessModule::WHOLESALE)
            ->where('position', 0)
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($item) => [strtolower($item->name) => (int) $item->id])
            ->toArray();
    }

    private function childCategoriesByParent(int $position): array
    {
        $children = [];

        foreach ($this->category->newQuery()->businessModule(BusinessModule::WHOLESALE)->where('position', $position)->get(['id', 'name', 'parent_id']) as $category) {
            $children[(int) $category->parent_id][strtolower($category->name)] = (int) $category->id;
        }

        return $children;
    }

    private function brands(): array
    {
        return $this->brand->newQuery()
            ->businessModule(BusinessModule::WHOLESALE)
            ->where('status', 1)
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($item) => [strtolower($item->name) => $item->id])
            ->toArray();
    }
}
