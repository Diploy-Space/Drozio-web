<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Category;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use App\Traits\GeneratesUniqueSlug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Modules\TaxModule\app\Models\Taxable;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class CategoryService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }
    use GeneratesUniqueSlug;
    use VatTaxManagement;

    public const POSITION_CATEGORY = 0;

    public const POSITION_SUB_CATEGORY = 1;

    public const POSITION_SUB_SUB_CATEGORY = 2;

    public const NAME_MAX_LENGTH = 50;

    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const SORT_NAME_ASC = 'a-z';

    public const SORT_NAME_DESC = 'z-a';

    public const SORT_NEWEST_ID = 'newest-id';

    public const SORT_OLDEST_ID = 'oldest-id';

    public const SORT_PRIORITY = 'priority';

    public const SORT_LIST_PRIORITY = 'list-priority';

    public const STATUS_TYPE_HOME = 'home_status';

    public const STATUS_TYPE_AUCTION_HOME = 'auction_home_status';

    public const STATUS_COLUMNS = [self::STATUS_TYPE_HOME, self::STATUS_TYPE_AUCTION_HOME];

    public const HOME_PRODUCT_LIMIT = 10;

    public const FIND_WHAT_YOU_NEED_CHILD_LIMIT = 4;

    public const COUNT_FILTER_CLEARANCE_ONLY = 'clearance_only';

    public const COUNT_FILTER_DISCOUNTED_ONLY = 'discounted_only';

    public const COUNT_FILTER_DIGITAL_ONLY = 'digital_only';

    public const COUNT_FILTER_PRODUCT_IDS = 'product_ids';

    public const COUNT_FILTER_FLASH_DEAL_ONLY = 'flash_deal_only';

    public const COUNT_FILTER_FLASH_DEAL_ID = 'flash_deal_id';

    public const COUNT_FILTER_SHOP_ID = 'shop_id';

    public const FILTER_HAS_ACTIVE_PRODUCTS = 'has_active_products';

    public const FILTER_PRODUCT_IDS = 'product_ids';

    public const FILTER_HAS_FLASH_DEAL_PRODUCTS = 'has_flash_deal_products';

    public const FILTER_FLASH_DEAL_ID = 'flash_deal_id';

    public const FILTER_NAME_LIKE = 'name_like';

    public const PRODUCT_RELATION = 'product';

    public const SUB_CATEGORY_PRODUCT_RELATION = 'subCategoryProduct';

    public const SUB_SUB_CATEGORY_PRODUCT_RELATION = 'subSubCategoryProduct';

    private const LIST_PRIORITY_SETTING = 'category_list_priority';

    private const PRODUCT_PRIORITY_SETTING = 'category_wise_product_list_priority';

    private const ORDER_COUNT_ALIAS = 'order_count';

    public const IMAGE_DIRECTORY = 'category/';

    private const INDEXABLE_CHUNK_SIZE = 1000;

    public function __construct(Category $category)
    {
        parent::__construct($category);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function maxPosition(): int
    {
        return self::POSITION_SUB_SUB_CATEGORY;
    }

    protected function homeStatusColumn(): string
    {
        return self::STATUS_TYPE_HOME;
    }

    protected function query(): Builder
    {
        return parent::query()->businessModule($this->businessModule());
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
        ?int $position = self::POSITION_CATEGORY,
        array $parentIds = [],
        int $productLimit = 0,
        bool $withProductCounts = false,
        array $productCountFilters = [],
        bool $withChildCounts = false,
    ): Collection|LengthAwarePaginator {
        $query = $this->listQuery($position, $searchValue, $parentIds, $relations)
            ->withCount($withCount);

        if ($withChildCounts) {
            $this->applyChildCounts($query);
        }

        if (! empty($filters[self::FILTER_HAS_ACTIVE_PRODUCTS])) {
            $query->whereHas(self::PRODUCT_RELATION, fn (Builder $builder) => $builder->active());
        }

        if (! empty($filters[self::FILTER_PRODUCT_IDS])) {
            $query->whereHas(
                $this->getProductRelationForPosition($position),
                fn (Builder $builder) => $builder->active()->whereIn('id', $filters[self::FILTER_PRODUCT_IDS]),
            );
        }

        if (! empty($filters[self::FILTER_FLASH_DEAL_ID])) {
            $query->whereHas('product.flashDealProducts.flashDeal', fn (Builder $deal) => $deal
                ->where('id', $filters[self::FILTER_FLASH_DEAL_ID]));
        } elseif (! empty($filters[self::FILTER_HAS_FLASH_DEAL_PRODUCTS])) {
            $query->whereHas('product.flashDealProducts.flashDeal');
        }

        if (! empty($filters[self::FILTER_NAME_LIKE])) {
            $query->where('name', 'like', '%'.$filters[self::FILTER_NAME_LIKE].'%');
        }

        unset(
            $filters[self::FILTER_HAS_ACTIVE_PRODUCTS],
            $filters[self::FILTER_PRODUCT_IDS],
            $filters[self::FILTER_HAS_FLASH_DEAL_PRODUCTS],
            $filters[self::FILTER_FLASH_DEAL_ID],
            $filters[self::FILTER_NAME_LIKE],
        );

        if ($productLimit > 0) {
            $query->with(['product' => fn ($relation) => $this->applyProductPriority(
                $relation->active()->with([
                    'flashDealProducts' => fn ($builder) => $builder->with('flashDeal'),
                    'clearanceSale' => fn ($builder) => $builder->active(),
                ]),
            )->limit($productLimit)]);
        }

        if ($withProductCounts) {
            $this->applyProductCounts($query, $productCountFilters);
        }

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_OLDEST => $query->oldest(),
            self::SORT_NAME_ASC => $query->orderBy('name'),
            self::SORT_NAME_DESC => $query->orderByDesc('name'),
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            self::SORT_OLDEST_ID => $query->orderBy('id'),
            self::SORT_PRIORITY => $query->priority(),
            self::SORT_LIST_PRIORITY => $this->applyListPriority($query),
            default => $query->latest('updated_at'),
        };

        $this->applyFilters($query, $filters);

        $categories = $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));

        if ($productLimit > 0) {
            $categories->each(fn (Model $category) => $this->stampFlashDealStatus($category));
        }

        return $categories;
    }

    private function getProductRelationForPosition(?int $position): string
    {
        return match ($position) {
            self::POSITION_SUB_CATEGORY => self::SUB_CATEGORY_PRODUCT_RELATION,
            self::POSITION_SUB_SUB_CATEGORY => self::SUB_SUB_CATEGORY_PRODUCT_RELATION,
            default => self::PRODUCT_RELATION,
        };
    }

    private function applyChildCounts(Builder $query): void
    {
        $subCategoryIds = DB::table('categories as sub_categories')
            ->select('sub_categories.id')
            ->whereColumn('sub_categories.parent_id', 'categories.id');

        $query->withCount(['childes as sub_category_count'])
            ->addSelect(['sub_sub_category_count' => DB::table('categories as sub_sub_categories')
                ->selectRaw('count(*)')
                ->whereIn('sub_sub_categories.parent_id', $subCategoryIds),
            ]);
    }

    private function applyProductCounts(Builder $query, array $countFilters): void
    {
        $countable = fn (Builder $builder) => $this->applyProductCountFilters($builder->active(), $countFilters);

        $query->withCount(['product' => $countable])
            ->with(['childes' => fn ($relation) => $relation
                ->where('position', self::POSITION_SUB_CATEGORY)
                ->withCount(['subCategoryProduct' => $countable])
                ->with(['childes' => fn ($inner) => $inner
                    ->where('position', self::POSITION_SUB_SUB_CATEGORY)
                    ->withCount(['subSubCategoryProduct' => $countable]),
                ]),
            ]);
    }

    protected function applyProductCountFilters(Builder $builder, array $countFilters): Builder
    {
        if (array_key_exists(self::COUNT_FILTER_SHOP_ID, $countFilters)) {
            $builder->where('shop_id', $countFilters[self::COUNT_FILTER_SHOP_ID]);
        }

        if (! empty($countFilters[self::COUNT_FILTER_CLEARANCE_ONLY])) {
            $builder->whereHas('clearanceSale', fn (Builder $clearance) => $clearance->active());
        }

        if (! empty($countFilters[self::COUNT_FILTER_DISCOUNTED_ONLY])) {
            $builder->where('discount', '>', 0);
        }

        if (! empty($countFilters[self::COUNT_FILTER_DIGITAL_ONLY])) {
            $builder->where('product_type', ProductService::PRODUCT_TYPE_DIGITAL);
        }

        if (array_key_exists(self::COUNT_FILTER_PRODUCT_IDS, $countFilters)) {
            $builder->whereIn('id', $countFilters[self::COUNT_FILTER_PRODUCT_IDS] ?: [0]);
        }

        if (! empty($countFilters[self::COUNT_FILTER_FLASH_DEAL_ID])) {
            $builder->whereHas('flashDealProducts.flashDeal', fn (Builder $deal) => $deal
                ->where('id', $countFilters[self::COUNT_FILTER_FLASH_DEAL_ID]));
        } elseif (! empty($countFilters[self::COUNT_FILTER_FLASH_DEAL_ONLY])) {
            $builder->whereHas('flashDealProducts.flashDeal');
        }

        return $builder;
    }

    private function applyListPriority(Builder $query): void
    {
        $priority = getWebConfig(name: self::LIST_PRIORITY_SETTING);

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            $query->orderByDesc('priority')->orderBy('id');

            return;
        }

        match ($priority['sort_by'] ?? null) {
            'most_order' => $this->applyOrderCountSort($query),
            'latest_created' => $query->orderByDesc('id'),
            'first_created' => $query->orderBy('id'),
            'a_to_z' => $query->orderBy('name')->orderBy('id'),
            'z_to_a' => $query->orderByDesc('name')->orderBy('id'),
            default => $query->orderByDesc('priority')->orderBy('id'),
        };
    }

    private function applyOrderCountSort(Builder $query): void
    {
        $query->addSelect([self::ORDER_COUNT_ALIAS => $this->categoryOrderCountSubQuery()])
            ->orderByDesc(self::ORDER_COUNT_ALIAS)
            ->orderBy('id');
    }

    private function categoryOrderCountSubQuery(): Builder
    {
        $activeCategoryProducts = Product::query()->active()->whereColumn('products.category_id', 'categories.id');

        return OrderDetail::query()
            ->selectRaw('count(*)')
            ->whereIn('order_details.product_id', $activeCategoryProducts->select('products.id'));
    }

    private function applyProductPriority($relation)
    {
        $priority = getWebConfig(name: self::PRODUCT_PRIORITY_SETTING);

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            return $relation->orderByDesc($this->productSortColumn('order_details_count'));
        }

        if (($priority['out_of_stock_product'] ?? null) === 'hide') {
            $relation->where('product_type', '!=', 'digital')->where('current_stock', '>', 0);
        }

        return match ($priority['sort_by'] ?? null) {
            'latest_created' => $relation->orderByDesc('id'),
            'first_created' => $relation->orderBy('id'),
            'most_order' => $relation->orderByDesc($this->productSortColumn('order_details_count')),
            'reviews_count' => $relation->orderByDesc($this->productSortColumn('reviews_count')),
            'rating' => $relation->orderByDesc($this->productSortColumn('reviews_avg_rating')),
            'a_to_z' => $relation->orderBy('name'),
            'z_to_a' => $relation->orderByDesc('name'),
            default => $relation,
        };
    }

    private function productSortColumn(string $alias): Expression
    {
        return match ($alias) {
            'reviews_count' => DB::raw('(select count(*) from reviews where reviews.product_id = products.id and reviews.status = 1)'),
            'reviews_avg_rating' => DB::raw('(select avg(reviews.rating) from reviews where reviews.product_id = products.id and reviews.status = 1)'),
            default => DB::raw('(select count(*) from order_details where order_details.product_id = products.id)'),
        };
    }

    private function stampFlashDealStatus(Model $category): void
    {
        $products = $category->product;

        $products?->each(function (Model $product): void {
            $flashDeal = $product->flashDealProducts->first()?->flashDeal;
            $product->flash_deal_status = $flashDeal?->isCurrentlyActive() ? 1 : 0;
        });

        $category->products = $products ?? Collection::make();
    }

    /** @return array{active: int, inactive: int} */
    public function getStatusCounts(
        ?string $searchValue = null,
        array $filters = [],
        ?string $column = null,
        int $position = self::POSITION_CATEGORY,
        array $parentIds = [],
    ): array {
        $column ??= self::STATUS_TYPE_HOME;
        $query = $this->listQuery($position, $searchValue, $parentIds);

        $this->applyFilters($query, $filters);

        $counts = $query->select($column)
            ->selectRaw('count(*) as total')
            ->groupBy($column)
            ->pluck('total', $column);

        return [
            'active' => (int) ($counts[1] ?? 0),
            'inactive' => (int) ($counts[0] ?? 0),
        ];
    }

    public function getIndexableSlugs(): LazyCollection
    {
        return $this->query()
            ->withoutGlobalScope('translate')
            ->select(['id', 'slug', 'updated_at'])
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->lazyById(self::INDEXABLE_CHUNK_SIZE);
    }

    public function find(mixed $id, array $relations = ['translations', 'seo']): ?Model
    {
        return $this->relationQuery($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        $query = $this->relationQuery($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function getWithDescendants(mixed $id, array $relations = ['childes.childes']): ?Model
    {
        return $this->model->newQuery()->with($relations)->find($id);
    }

    public function getCategoryPath(mixed $id): array
    {
        $category = $this->query()->with('parent.parent')->find($id);
        $path = [];

        while ($category !== null) {
            array_unshift($path, (int) $category->id);
            $category = $category->relationLoaded('parent') ? $category->parent : null;
        }

        return $path;
    }

    public function getIdByName(string $name): ?int
    {
        $id = $this->query()->where('name', 'like', "%{$name}%")->value('id');
        if ($id) {
            return (int) $id;
        }

        $translatedId = $this->translatedIds($name)[0] ?? null;

        return $translatedId ? (int) $translatedId : null;
    }

    public function findBySlug(string $slug, array $relations = ['seo']): ?Model
    {
        return $this->model->newQuery()
            ->businessModule($this->businessModule())
            ->with($relations)
            ->where('slug', $slug)
            ->first();
    }

    public function getFilterPosition(Model $category): int
    {
        $declared = [self::POSITION_CATEGORY, self::POSITION_SUB_CATEGORY, self::POSITION_SUB_SUB_CATEGORY];

        if ($category->position !== null && in_array((int) $category->position, $declared, true)) {
            return (int) $category->position;
        }

        return $this->getDepthFromAncestors((int) $category->parent_id);
    }

    private function getDepthFromAncestors(int $parentId): int
    {
        $depth = self::POSITION_CATEGORY;

        while ($parentId > 0 && $depth < self::POSITION_SUB_SUB_CATEGORY) {
            $parentId = (int) $this->query()->where('id', $parentId)->value('parent_id');
            $depth++;
        }

        return $depth;
    }

    /** @return array{category_ids: array<int, int>, sub_category_ids: array<int, int>, sub_sub_category_ids: array<int, int>} */
    public function getCategoryIdsForFilter(array $categoryIds, array $subCategoryIds, array $subSubCategoryIds): array
    {
        $categories = $this->getCategoriesByIds($categoryIds);
        $subCategories = $this->getCategoriesByIds($subCategoryIds);
        $subSubCategories = $this->getCategoriesByIds($subSubCategoryIds);

        $subSubParentIds = $subSubCategories->pluck('parent_id');
        $subParentIds = $subCategories->pluck('parent_id');

        return [
            'category_ids' => $categories
                ->reject(fn (Model $category): bool => $subParentIds->contains($category->id))
                ->pluck('id')
                ->toArray(),
            'sub_category_ids' => $subCategories
                ->reject(fn (Model $subCategory): bool => $subSubParentIds->contains($subCategory->id))
                ->pluck('id')
                ->toArray(),
            'sub_sub_category_ids' => $subSubCategories->pluck('id')->toArray(),
        ];
    }

    private function getCategoriesByIds(array $categoryIds): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScopes()
            ->whereIn('id', $categoryIds === [] ? [0] : $categoryIds)
            ->get(['id', 'parent_id']);
    }

    public function getShopCategoryList(
        array $categoryIds,
        string $addedBy,
        ?int $sellerId = null,
        bool $clearanceOnly = false,
    ): Collection {
        $scoped = fn ($builder) => $builder
            ->active()
            ->where('added_by', $addedBy)
            ->when($sellerId !== null, fn ($inner) => $inner->where('user_id', $sellerId));

        $countable = fn ($builder) => $scoped($builder)
            ->when($clearanceOnly, fn ($inner) => $inner
                ->whereHas('clearanceSale', fn (Builder $sale) => $sale->active()));

        $categories = $this->query()
            ->with(['product' => fn ($relation) => $scoped($relation)->withCount('orderDetails')])
            ->with(['childes.childes' => fn ($relation) => $relation->withCount(['product' => $countable])])
            ->where('position', self::POSITION_CATEGORY)
            ->whereIn('id', $categoryIds)
            ->withCount(['product' => $countable])
            ->get();

        return $this->applyShopListPriority($categories);
    }

    private function applyShopListPriority(Collection $categories): Collection
    {
        $priority = getWebConfig(name: self::LIST_PRIORITY_SETTING);

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            return $categories->sortByDesc('priority');
        }

        return match ($priority['sort_by'] ?? null) {
            'most_order' => $this->sortByShopOrderCount($categories),
            'latest_created' => $categories->sortByDesc('id'),
            'first_created' => $categories->sortBy('id'),
            'a_to_z' => $categories->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            'z_to_a' => $categories->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE),
            default => $categories->sortByDesc('priority'),
        };
    }

    private function sortByShopOrderCount(Collection $categories): Collection
    {
        return $categories
            ->map(function (Model $category): Model {
                $category->order_count ??= $category->relationLoaded('product')
                    ? ($category->product?->sum('order_details_count') ?? 0)
                    : 0;

                return $category;
            })
            ->sortByDesc('order_count');
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function updateStatus(mixed $id, bool $status, ?string $column = null): bool
    {
        $category = $this->query()->find($id);

        if ($category === null) {
            return false;
        }

        $category->update([$column ?? self::STATUS_TYPE_HOME => $status]);

        return true;
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function getListForParentSelect(
        int $position = self::POSITION_CATEGORY,
        int|string $dataLimit = 'all',
        int $page = 1,
    ): Collection|LengthAwarePaginator {
        $query = $this->relationQuery(['translations'])
            ->where('position', $position)
            ->orderBy('name');

        return $dataLimit === 'all' ? $query->get() : $query->paginate(perPage: $dataLimit, page: $page);
    }

    public function getIds(array $filters = []): array
    {
        return $this->applyFilters($this->query(), $filters)->pluck('id')->toArray();
    }

    public function getChildList(mixed $parentId): Collection
    {
        return $this->relationQuery(['translations'])->where('parent_id', $parentId)->get();
    }

    /** @return array{category_ids: array<int>, sub_category_ids: array<int>, sub_sub_category_ids: array<int>, direct_category_ids: array<int>, direct_sub_category_ids: array<int>} */
    public function getMatchingCategoryIds(array $categoryIds, array $subCategoryIds, array $subSubCategoryIds): array
    {
        $categoryIds = $this->selectedCategoryIds($categoryIds);
        $subCategoryIds = $this->selectedCategoryIds($subCategoryIds);
        $subSubCategoryIds = $this->selectedCategoryIds($subSubCategoryIds);

        $parentIdsOfSelectedSubSubCategories = $this->getParentIds($subSubCategoryIds);
        $parentIdsOfSelectedSubCategories = $this->getParentIds(array_merge($subCategoryIds, $parentIdsOfSelectedSubSubCategories));

        $directCategoryIds = array_values(array_intersect($categoryIds, $parentIdsOfSelectedSubCategories));
        $directSubCategoryIds = array_values(array_intersect($subCategoryIds, $parentIdsOfSelectedSubSubCategories));

        return [
            'category_ids' => array_values(array_diff($categoryIds, $directCategoryIds)),
            'sub_category_ids' => array_values(array_diff($subCategoryIds, $directSubCategoryIds)),
            'sub_sub_category_ids' => $subSubCategoryIds,
            'direct_category_ids' => $directCategoryIds,
            'direct_sub_category_ids' => $directSubCategoryIds,
        ];
    }

    private function getParentIds(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        return $this->query()
            ->whereIn('id', array_unique($categoryIds))
            ->pluck('parent_id')
            ->map(static fn ($parentId): int => (int) $parentId)
            ->unique()
            ->values()
            ->all();
    }

    private function selectedCategoryIds(array $categoryIds): array
    {
        return array_values(array_unique(array_map('intval', array_filter($categoryIds, 'is_numeric'))));
    }

    /** @return array{category_ids: array<int>, sub_category_ids: array<int>, sub_sub_category_ids: array<int>} */
    public function getIdsByPosition(array $categoryIds, bool $dropSubCategoriesWithSelectedChildren = false): array
    {
        $categories = $categoryIds === []
            ? new Collection
            : $this->query()->whereIn('id', $categoryIds)->get(['id', 'parent_id', 'position']);

        $subCategories = $categories->where('position', self::POSITION_SUB_CATEGORY);
        $subSubCategories = $categories->where('position', self::POSITION_SUB_SUB_CATEGORY);

        if ($dropSubCategoriesWithSelectedChildren) {
            $selectedParentIds = $subSubCategories->pluck('parent_id');
            $subCategories = $subCategories->reject(fn (Model $category): bool => $selectedParentIds->contains($category->id));
        }

        return [
            'category_ids' => $categories->where('position', self::POSITION_CATEGORY)->pluck('id')->values()->all(),
            'sub_category_ids' => $subCategories->pluck('id')->values()->all(),
            'sub_sub_category_ids' => $subSubCategories->pluck('id')->values()->all(),
        ];
    }

    public function getHomeListWithActiveProducts(): Collection
    {
        return $this->model->newQuery()
            ->businessModule($this->businessModule())
            ->whereHas('product', fn (Builder $product) => $product->active())
            ->where(self::STATUS_TYPE_HOME, true)
            ->get();
    }

    public function getFindWhatYouNeedList(int $childLimit = self::FIND_WHAT_YOU_NEED_CHILD_LIMIT): Collection
    {
        return $this->model->newQuery()
            ->businessModule($this->businessModule())
            ->where('parent_id', 0)
            ->with(['childes' => fn ($relation) => $relation->withCount(['subCategoryProduct' => fn (Builder $product) => $product->active()])])
            ->withCount(['product' => fn (Builder $product) => $product->active()])
            ->get()
            ->each(fn (Model $category) => $category->setRelation('childes', $category->childes->take($childLimit)->values()))
            ->filter(fn (Model $category): bool => $category->childes->isNotEmpty())
            ->values();
    }

    public function getTree(bool $withProductCounts = false): Collection
    {
        $query = $this->model->newQuery()
            ->businessModule($this->businessModule())
            ->where('position', self::POSITION_CATEGORY)
            ->priority();

        if (! $withProductCounts) {
            return $query->with(['childes.childes'])->get();
        }

        return $query->with(['childes' => fn ($relation) => $relation
            ->where('position', self::POSITION_SUB_CATEGORY)
            ->withCount(['subCategoryProduct'])
            ->with(['childes' => fn ($inner) => $inner
                ->where('position', self::POSITION_SUB_SUB_CATEGORY)
                ->withCount(['subSubCategoryProduct']),
            ]),
        ])->get();
    }

    public function create(
        array $data,
        int $position = self::POSITION_CATEGORY,
        ?UploadedFile $image = null,
        ?UploadedFile $metaImage = null,
    ): ?Model {
        if ($position > $this->maxPosition()) {
            return null;
        }

        $parentId = $position === self::POSITION_CATEGORY ? 0 : (int) ($data['parent_id'] ?? 0);
        $businessModule = $this->resolveBusinessModule($position, $parentId);

        if ($businessModule === null) {
            return null;
        }

        return DB::transaction(function () use ($data, $position, $parentId, $businessModule, $image, $metaImage): Model {
            $name = $this->defaultLocaleValue($data, 'name');

            $category = $this->query()->create([
                'name' => $name,
                'slug' => $this->generateModelUniqueSlug(name: $name, type: 'category'),
                'icon' => $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $image),
                'icon_storage_type' => $image !== null ? $this->storageDisk() : null,
                'parent_id' => $parentId,
                'position' => $position,
                'priority' => $data['priority'] ?? 0,
                $this->homeStatusColumn() => 1,
                'business_module' => $businessModule,
            ]);

            $this->syncTranslations($category->id, $data);
            $this->syncSeoMetaInfo($category, $data, $metaImage, $image);

            if ($position === self::POSITION_CATEGORY) {
                $this->getAddTaxData(
                    taxableType: Category::class,
                    taxableId: $category->id,
                    taxIds: $data['tax_ids'] ?? [],
                    businessModule: $this->businessModule(),
                );
            }

            return $category;
        });
    }

    public function isSubCategoryMovingToNewParent(mixed $id, ?int $parentId): bool
    {
        if ($parentId === null) {
            return false;
        }

        $category = $this->query()->find($id);

        return $category !== null
            && $category->position === self::POSITION_SUB_CATEGORY
            && (int) $category->parent_id !== $parentId;
    }

    public function update(
        mixed $id,
        array $data,
        ?UploadedFile $image = null,
        ?UploadedFile $metaImage = null,
    ): ?Model {
        $category = $this->query()->with('taxVats')->find($id);

        if ($category === null) {
            return null;
        }

        $parentId = isset($data['parent_id']) && $data['parent_id'] ? (int) $data['parent_id'] : null;

        if ($parentId !== null && $this->resolveBusinessModule($category->position, $parentId) === null) {
            return null;
        }

        return DB::transaction(function () use ($category, $data, $parentId, $image, $metaImage): Model {
            $columns = ['priority' => $data['priority'] ?? 0];

            if ($name = $this->defaultLocaleValue($data, 'name')) {
                $columns['name'] = $name;
                $columns['slug'] = $this->generateModelUniqueSlug(name: $name, type: 'category', id: $category->id);
            }

            if ($image !== null) {
                $columns['icon'] = $this->updateFile(self::IMAGE_DIRECTORY, $category->icon, 'webp', $image);
                $columns['icon_storage_type'] = $this->storageDisk();
            }

            if ($parentId !== null) {
                $columns['parent_id'] = $parentId;
            }

            if ($category->position === self::POSITION_CATEGORY) {
                $columns[$this->homeStatusColumn()] = $data[$this->homeStatusColumn()] ?? 0;
            }

            $category->fill($columns)->touch();

            $this->syncTranslations($category->id, $data);
            $this->syncSeoMetaInfo($category, $data, $metaImage);

            $this->getUpdateTaxData(
                taxableType: Category::class,
                taxableId: $category->id,
                taxIds: $data['tax_ids'] ?? [],
                oldTaxIds: $category->taxVats->pluck('tax_id')->toArray(),
                businessModule: $this->businessModule(),
            );

            return $category->refresh();
        });
    }

    public function delete(mixed $id): bool
    {
        $category = $this->query()->with('childes.childes')->find($id);

        if ($category === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($category): bool {
            $descendantIds = [];

            foreach ($category->childes as $child) {
                foreach ($child->childes as $grandChild) {
                    $descendantIds[] = $grandChild->id;
                    $this->deleteIcon($grandChild->icon);
                }

                $descendantIds[] = $child->id;
                $this->deleteIcon($child->icon);
            }

            $this->deleteIcon($category->icon);

            $this->purgeRelatedRecords([...$descendantIds, $category->id]);

            $this->query()->whereIn('id', $descendantIds)->delete();

            return (bool) $category->delete();
        });
    }

    public function isDuplicateName(string $name, int $position, ?int $parentId = null, mixed $excludeId = null): bool
    {
        return $this->query()
            ->where('name', $name)
            ->where('position', $position)
            ->when($excludeId !== null, fn (Builder $query) => $query->where('id', '!=', $excludeId))
            ->when(! empty($parentId), fn (Builder $query) => $query->where('parent_id', $parentId))
            ->exists();
    }

    private function listQuery(?int $position, ?string $searchValue, array $parentIds, array $relations = []): Builder
    {
        return $this->relationQuery($relations)
            ->when($position !== null, fn (Builder $builder) => $builder->where('position', $position))
            ->when(! empty($parentIds), fn (Builder $builder) => $builder->whereIn('parent_id', $parentIds))
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('name', 'like', "%{$searchValue}%")
                    ->when(is_numeric($searchValue), fn (Builder $matched) => $matched->orWhere('id', 'like', "%{$searchValue}%"))
                    ->orWhereIn('id', $this->translatedIds($searchValue));
            }));
    }

    private function resolveBusinessModule(int $position, ?int $parentId): ?string
    {
        if ($position === self::POSITION_CATEGORY) {
            return $this->businessModule();
        }

        $parentModule = $this->query()->where('id', $parentId)->value('business_module');

        return $parentModule ?: null;
    }

    private function deleteIcon(?string $icon): void
    {
        if ($icon) {
            $this->deleteFile(filePath: self::IMAGE_DIRECTORY.$icon);
        }
    }

    /** @param array<int, int> $categoryIds */
    private function purgeRelatedRecords(array $categoryIds): void
    {
        $this->deleteTranslations($categoryIds);
        $this->deleteSeoMetaInfo($categoryIds);
        $this->deleteTaxables($categoryIds);
    }

    /** @param array<int, int> $categoryIds */
    private function deleteTaxables(array $categoryIds): void
    {
        Taxable::query()
            ->where('taxable_type', Category::class)
            ->whereIn('taxable_id', $categoryIds)
            ->delete();
    }
}
