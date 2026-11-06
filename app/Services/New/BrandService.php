<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Brand;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use App\Traits\GeneratesUniqueSlug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class BrandService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }
    use GeneratesUniqueSlug;

    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const SORT_NAME_ASC = 'a-z';

    public const SORT_NAME_DESC = 'z-a';

    public const SORT_NEWEST_ID = 'newest-id';

    public const SORT_OLDEST_ID = 'oldest-id';

    public const SORT_PRIORITY = 'priority';

    public const SORT_ACTIVE_PRODUCTS_FIRST = 'active-products-first';

    public const SORT_NAME_ASC_LATEST = 'a-z-latest';

    public const SORT_NAME_DESC_LATEST = 'z-a-latest';

    public const FILTER_NAME_LIKE = 'name_like';

    public const FILTER_PRODUCT_IDS = 'product_ids';

    public const COUNT_RELATION_PRODUCTS = 'brandProducts';

    public const COUNT_FILTER_CLEARANCE_ONLY = 'clearance_only';

    public const COUNT_FILTER_FLASH_DEAL_ONLY = 'flash_deal_only';

    public const COUNT_FILTER_FLASH_DEAL_ID = 'flash_deal_id';

    public const COUNT_FILTER_SHOP_ID = 'shop_id';

    public const COUNT_FILTER_PRODUCT_IDS = 'product_ids';

    public const FILTER_HAS_FLASH_DEAL_PRODUCTS = 'has_flash_deal_products';

    public const FILTER_FLASH_DEAL_ID = 'flash_deal_id';

    private const PRIORITY_SETTING = 'brand_list_priority';

    private const ORDER_COUNT_ALIAS = 'order_count';

    private const ACTIVE_PRODUCTS_EXISTS_ALIAS = 'has_active_products';

    private const IMAGE_DIRECTORY = 'brand/';

    private const INDEXABLE_CHUNK_SIZE = 1000;

    public function __construct(Brand $brand)
    {
        parent::__construct($brand);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
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
        array $appends = [],
        array $productCountFilters = [],
    ): Collection|LengthAwarePaginator {
        $query = $this->relationQuery($relations)
            ->withCount($this->productCountSelections($withCount, $productCountFilters))
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('name', 'like', "%{$searchValue}%")
                    ->when(is_numeric($searchValue), fn (Builder $query) => $query->orWhere('id', $searchValue))
                    ->orWhereIn('id', $this->translatedIds($searchValue));
            }));

        if (! empty($filters[self::FILTER_PRODUCT_IDS])) {
            $query->whereHas(
                self::COUNT_RELATION_PRODUCTS,
                fn (Builder $builder) => $builder->whereIn('id', $filters[self::FILTER_PRODUCT_IDS]),
            );
        }

        if (! empty($filters[self::FILTER_FLASH_DEAL_ID])) {
            $query->whereHas('brandProducts.flashDealProducts.flashDeal', fn (Builder $deal) => $deal
                ->where('id', $filters[self::FILTER_FLASH_DEAL_ID]));
        } elseif (! empty($filters[self::FILTER_HAS_FLASH_DEAL_PRODUCTS])) {
            $query->whereHas('brandProducts.flashDealProducts.flashDeal');
        }

        if (! empty($filters[self::FILTER_NAME_LIKE])) {
            $query->where('name', 'like', '%'.$filters[self::FILTER_NAME_LIKE].'%');
        }

        unset(
            $filters[self::FILTER_PRODUCT_IDS],
            $filters[self::FILTER_HAS_FLASH_DEAL_PRODUCTS],
            $filters[self::FILTER_FLASH_DEAL_ID],
            $filters[self::FILTER_NAME_LIKE],
        );

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_OLDEST => $query->oldest(),
            self::SORT_NAME_ASC => $query->orderBy('name'),
            self::SORT_NAME_DESC => $query->orderByDesc('name'),
            self::SORT_NAME_ASC_LATEST => $query->orderBy('name')->latest(),
            self::SORT_NAME_DESC_LATEST => $query->orderByDesc('name')->latest(),
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            self::SORT_OLDEST_ID => $query->orderBy('id'),
            self::SORT_PRIORITY => $this->applyPriority($query),
            self::SORT_ACTIVE_PRODUCTS_FIRST => $this->applyActiveProductsFirstPriority($query),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($appends === [] ? $this->listQueryString($searchValue, $sortBy, $filters) : $appends);
    }

    public function getShopBrandList(
        array $brandIds,
        string $addedBy,
        ?int $sellerId = null,
        ?array $clearanceProductIds = null,
    ): Collection {
        $scoped = fn ($builder) => $builder
            ->active()
            ->when($addedBy === ProductService::ADDED_BY_ADMIN, fn ($inner) => $inner->where('added_by', $addedBy))
            ->when(
                $sellerId && $addedBy === ProductService::ADDED_BY_SELLER,
                fn ($inner) => $inner->where('added_by', $addedBy)->where('user_id', $sellerId),
            );

        $brands = $this->query()
            ->active()
            ->whereIn('id', $brandIds)
            ->with(['brandProducts' => fn ($relation) => $scoped($relation)->withCount('orderDetails')])
            ->withCount(['brandProducts' => fn ($builder) => $scoped($builder)
                ->when($clearanceProductIds !== null, fn ($inner) => $inner->whereIn('id', $clearanceProductIds))])
            ->orderBy('id')
            ->get();

        return $this->applyShopListPriority($brands);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function productCountSelections(array $withCount, array $countFilters): array
    {
        if ($countFilters === [] || ! in_array(self::COUNT_RELATION_PRODUCTS, $withCount, true)) {
            return $withCount;
        }

        $selections = array_values(array_diff($withCount, [self::COUNT_RELATION_PRODUCTS]));
        $selections[self::COUNT_RELATION_PRODUCTS] = fn (Builder $builder) => $this->applyProductCountFilters($builder, $countFilters);

        return $selections;
    }

    protected function applyProductCountFilters(Builder $builder, array $countFilters): Builder
    {
        if (array_key_exists(self::COUNT_FILTER_SHOP_ID, $countFilters)) {
            $builder->where('shop_id', $countFilters[self::COUNT_FILTER_SHOP_ID]);
        }

        if (! empty($countFilters[self::COUNT_FILTER_CLEARANCE_ONLY])) {
            $builder->whereHas('clearanceSale', fn (Builder $clearance) => $clearance->active());
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

    private function applyShopListPriority(Collection $brands): Collection
    {
        $priority = getWebConfig(name: self::PRIORITY_SETTING);

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            return $brands;
        }

        return match ($priority['sort_by'] ?? null) {
            'most_order' => $brands
                ->map(function (Model $brand): Model {
                    $brand->{self::ORDER_COUNT_ALIAS} = $brand->brandProducts->sum('order_details_count');

                    return $brand;
                })
                ->sortByDesc(self::ORDER_COUNT_ALIAS),
            'latest_created' => $brands->sortByDesc('id'),
            'first_created' => $brands->sortBy('id'),
            'a_to_z' => $brands->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            'z_to_a' => $brands->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE),
            default => $brands,
        };
    }

    public function hasCustomListPriority(): bool
    {
        $priority = getWebConfig(name: self::PRIORITY_SETTING);

        return (bool) $priority && ($priority['custom_sorting_status'] ?? 0) == 1;
    }

    private function applyActiveProductsFirstPriority(Builder $query): void
    {
        $query->withExists([
            self::COUNT_RELATION_PRODUCTS.' as '.self::ACTIVE_PRODUCTS_EXISTS_ALIAS => fn (Builder $builder) => $builder
                ->active()
                ->businessModule($this->businessModule()),
        ])->orderByDesc(self::ACTIVE_PRODUCTS_EXISTS_ALIAS);

        $this->applyPriority($query);
    }

    private function applyPriority(Builder $query): void
    {
        $priority = getWebConfig(name: self::PRIORITY_SETTING);

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            $query->latest()->orderBy('id');

            return;
        }

        match ($priority['sort_by'] ?? null) {
            'most_order' => $this->applyOrderCountSort($query),
            'first_created' => $query->orderBy('id'),
            'a_to_z' => $query->orderBy('name')->orderBy('id'),
            'z_to_a' => $query->orderByDesc('name')->orderBy('id'),
            default => $query->orderByDesc('id'),
        };
    }

    private function applyOrderCountSort(Builder $query): void
    {
        $query->withCount([
            'brandProductOrderDetails as '.self::ORDER_COUNT_ALIAS => fn (Builder $builder) => $builder
                ->where('products.status', 1)
                ->where('products.business_module', $this->businessModule()),
        ])->orderByDesc(self::ORDER_COUNT_ALIAS)->orderBy('id');
    }

    public function getIndexableSlugs(): LazyCollection
    {
        return $this->query()
            ->withoutGlobalScope('translate')
            ->select(['id', 'slug', 'updated_at'])
            ->where('status', 1)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->lazyById(self::INDEXABLE_CHUNK_SIZE);
    }

    public function find(mixed $id, array $relations = ['translations', 'storage', 'seo']): ?Model
    {
        return $this->relationQuery($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        $query = $this->relationQuery($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data, ?UploadedFile $image = null, ?UploadedFile $metaImage = null): ?Model
    {
        return DB::transaction(function () use ($data, $image, $metaImage): Model {
            $name = $this->defaultLocaleValue($data, 'name');

            $brand = $this->query()->create([
                ...array_filter([
                    'status' => isset($data['status']) ? (int) $data['status'] : null,
                    'image_alt_text' => $data['image_alt_text'] ?? null,
                ], static fn ($value) => $value !== null),
                'name' => $name,
                'slug' => $this->generateModelUniqueSlug(name: $name, type: 'brand'),
                'image' => $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $image),
                'image_storage_type' => $image !== null ? $this->storageDisk() : null,
                'business_module' => $this->businessModule(),
            ]);

            $this->syncTranslations($brand->id, $data);
            $this->syncSeoMetaInfo($brand, $data, $metaImage, $image);

            return $brand;
        });
    }

    public function update(mixed $id, array $data, ?UploadedFile $image = null, ?UploadedFile $metaImage = null): ?Model
    {
        $brand = $this->query()->find($id);

        if ($brand === null) {
            return null;
        }

        return DB::transaction(function () use ($brand, $data, $image, $metaImage): Model {
            $columns = array_filter([
                'status' => isset($data['status']) ? (int) $data['status'] : null,
                'image_alt_text' => $data['image_alt_text'] ?? null,
            ], static fn ($value) => $value !== null);

            if ($name = $this->defaultLocaleValue($data, 'name')) {
                $columns['name'] = $name;
                $columns['slug'] = $this->generateModelUniqueSlug(name: $name, type: 'brand', id: $brand->id);
            }

            if ($image !== null) {
                $columns['image'] = $this->updateFile(self::IMAGE_DIRECTORY, $brand->image, 'webp', $image);
                $columns['image_storage_type'] = $this->storageDisk();
            }

            $brand->update($columns);

            $this->syncTranslations($brand->id, $data);
            $this->syncSeoMetaInfo($brand, $data, $metaImage);

            return $brand->refresh();
        });
    }

    public function getIds(array $filters = []): array
    {
        return $this->applyFilters($this->query(), $filters)->pluck('id')->toArray();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    public function delete(mixed $id): bool
    {
        $brand = $this->query()->find($id);

        if ($brand === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($brand): bool {
            if ($brand->image) {
                $this->deleteFile(filePath: self::IMAGE_DIRECTORY.$brand->image);
            }

            $this->deleteTranslations([$brand->id]);
            $this->deleteSeoMetaInfo($brand->id);

            return (bool) $brand->delete();
        });
    }

    /** @return array{active: int, inactive: int} */
    public function getStatusCounts(?string $searchValue = null, array $filters = []): array
    {
        $query = $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('name', 'like', "%{$searchValue}%")
                    ->when(is_numeric($searchValue), fn (Builder $query) => $query->orWhere('id', $searchValue))
                    ->orWhereIn('id', $this->translatedIds($searchValue));
            }));

        $this->applyFilters($query, $filters);

        $counts = $query->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'active' => (int) ($counts[1] ?? 0),
            'inactive' => (int) ($counts[0] ?? 0),
        ];
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

    public function getBusinessModule(mixed $brandId): string
    {
        return $this->model->newQuery()->where('id', $brandId)->value('business_module') ?? BusinessModule::RETAIL;
    }

    public function getActiveIdByName(string $name): ?int
    {
        $id = $this->query()->active()->where('name', 'like', "%{$name}%")->value('id');
        if ($id) {
            return (int) $id;
        }

        $translatedId = $this->translatedIds($name)[0] ?? null;

        return $translatedId ? (int) $translatedId : null;
    }

    public function getActiveWithProductCounts(int $limit, ?string $nameLike = null): Collection
    {
        return $this->query()
            ->active()
            ->withCount(['brandProducts' => fn (Builder $q) => $q->businessModule($this->businessModule())])
            ->having('brand_products_count', '>', 0)
            ->when($nameLike, fn (Builder $q) => $q->where('name', 'like', "%{$nameLike}%"))
            ->orderByDesc('brand_products_count')
            ->limit($limit)
            ->get();
    }
}
