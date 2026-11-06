<?php

namespace App\View\Composers\Web;

use App\Services\New\BrandService;
use App\Services\New\CategoryService;
use App\Services\New\Deal\FeaturedDealService;
use App\Services\New\ProductRetailService;
use App\Services\New\ShopService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class HeaderComposer
{
    private const CATEGORY_LIMIT = 11;

    private const BRAND_LIMIT = 9;

    private const ACTIVE = 1;

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    public function __construct(
        private readonly CategoryService $retailCategoryService,
        private readonly BrandService $retailBrandService,
        private readonly ProductRetailService $retailProductService,
        private readonly ShopService $shopService,
        private readonly FeaturedDealService $featuredDealService,
    ) {}

    public function compose(View $view): void
    {
        $view->with($this->resolved ??= $this->build());
    }

    /** @return array<string, mixed> */
    private function build(): array
    {
        return [
            'headerCategories' => $this->collect($this->retailCategoryService->getList(
                dataLimit: self::CATEGORY_LIMIT,
                relations: ['childes.childes'],
                sortBy: CategoryService::SORT_LIST_PRIORITY,
                position: CategoryService::POSITION_CATEGORY,
                withProductCounts: true,
            )),
            'headerBrands' => $this->collect($this->retailBrandService->getList(
                dataLimit: self::BRAND_LIMIT,
                withCount: ['brandProducts' => fn (Builder $builder) => $builder->active()],
                filters: ['status' => self::ACTIVE],
                sortBy: BrandService::SORT_ACTIVE_PRODUCTS_FIRST,
            )),
            'headerFeaturedDealCount' => $this->featuredDealCount(),
            'headerInHouseShop' => $this->shopService->findBy(['author_type' => ShopService::AUTHOR_TYPE_ADMIN]),
        ];
    }

    private function collect(Collection|LengthAwarePaginator $records): Collection
    {
        return $records instanceof LengthAwarePaginator ? $records->getCollection() : $records;
    }

    private function featuredDealCount(): int
    {
        $deal = $this->featuredDealService->getRunningDeals()->first();

        return $deal === null ? 0 : $this->retailProductService->count([
            ProductRetailService::FILTER_ACTIVE => true,
            ProductRetailService::FILTER_FEATURED_DEAL => $deal->id,
        ]);
    }
}
