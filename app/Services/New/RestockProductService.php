<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\RestockProduct;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class RestockProductService extends BaseService
{
    public const FILTER_SELLER_ID = 'seller_id';

    public const FILTER_ADDED_BY = 'added_by';

    public const FILTER_BRAND_ID = 'brand_id';

    public const FILTER_CATEGORY_ID = 'category_id';

    public const FILTER_SUB_CATEGORY_ID = 'sub_category_id';

    public const FILTER_DATE_RANGE = 'date_range';

    public const SORT_RECENTLY_UPDATED = 'recently-updated';

    public const ADDED_BY_IN_HOUSE = 'in_house';

    public const FILTER_PRODUCT_IDS = 'product_ids';

    public const FILTER_CUSTOMER_ID = 'customer_id';

    public const FILTER_BRAND_IDS = 'brand_ids';

    public function __construct(RestockProduct $restockProduct)
    {
        parent::__construct($restockProduct);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        $module = $this->businessModule();

        return parent::query()->where(function (Builder $restock) use ($module): void {
            $restock->whereHas('product', fn (Builder $product) => $product->businessModule($module));

            if ($module === BusinessModule::RETAIL) {
                $restock->orWhereDoesntHave('product');
            }
        });
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $sellerId = $filters[self::FILTER_SELLER_ID] ?? null;
        $addedBy = $filters[self::FILTER_ADDED_BY] ?? null;
        $dateRange = $filters[self::FILTER_DATE_RANGE] ?? [];
        $productIds = $filters[self::FILTER_PRODUCT_IDS] ?? null;
        $customerId = $filters[self::FILTER_CUSTOMER_ID] ?? null;
        $brandIds = $filters[self::FILTER_BRAND_IDS] ?? [];
        $productColumns = array_filter([
            'brand_id' => $filters[self::FILTER_BRAND_ID] ?? null,
            'category_id' => $filters[self::FILTER_CATEGORY_ID] ?? null,
            'sub_category_id' => $filters[self::FILTER_SUB_CATEGORY_ID] ?? null,
        ], static fn ($value) => $value !== null && $value !== '' && $value !== 'all');

        unset(
            $filters[self::FILTER_SELLER_ID],
            $filters[self::FILTER_ADDED_BY],
            $filters[self::FILTER_DATE_RANGE],
            $filters[self::FILTER_BRAND_ID],
            $filters[self::FILTER_CATEGORY_ID],
            $filters[self::FILTER_SUB_CATEGORY_ID],
            $filters[self::FILTER_PRODUCT_IDS],
            $filters[self::FILTER_CUSTOMER_ID],
            $filters[self::FILTER_BRAND_IDS],
        );

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when(! empty($brandIds), fn (Builder $builder) => $builder->whereHas(
                'product',
                fn (Builder $product) => $product->whereIn('brand_id', $brandIds),
            ))
            ->when($productIds !== null, fn (Builder $builder) => $builder->whereIn('product_id', $productIds))
            ->when($customerId !== null, fn (Builder $builder) => $builder
                ->whereHas('restockProductCustomers', fn (Builder $customer) => $customer->where('customer_id', $customerId))
                ->whereHas('product', fn (Builder $product) => $product->active()))
            ->when($addedBy !== null, fn (Builder $builder) => $builder->whereHas(
                'product',
                fn (Builder $product) => $addedBy === self::ADDED_BY_IN_HOUSE
                    ? $product->where('added_by', 'admin')
                    : $product->where('added_by', 'seller')
                        ->when($sellerId !== null && $sellerId !== 'all', fn (Builder $scoped) => $scoped->where('user_id', $sellerId)),
            ))
            ->when($addedBy === null && $sellerId, fn (Builder $builder) => $builder->whereHas(
                'product',
                fn (Builder $product) => $product->where('added_by', 'seller')->where('user_id', $sellerId),
            ))
            ->when($productColumns !== [], fn (Builder $builder) => $builder->whereHas(
                'product',
                fn (Builder $product) => $product->where($productColumns),
            ))
            ->when(count($dateRange) === 2, fn (Builder $builder) => $builder->whereBetween('created_at', $dateRange));

        match ($sortBy) {
            self::SORT_RECENTLY_UPDATED => $query->orderByDesc('updated_at'),
            default => $query->latest('id'),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        $sellerId = $params[self::FILTER_SELLER_ID] ?? null;
        unset($params[self::FILTER_SELLER_ID]);

        $query = $this->query()
            ->with($relations)
            ->when($sellerId !== null, fn (Builder $builder) => $builder->whereHas(
                'product',
                fn (Builder $product) => $product->where('added_by', 'seller')->where('user_id', $sellerId),
            ));

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function buildVariantCode(?string $colorName, iterable $choiceOptions, array $choiceValues): string
    {
        $variantCode = $colorName ?? '';

        foreach ($choiceOptions as $choice) {
            $choiceValue = str_replace(' ', '', (string) ($choiceValues[data_get($choice, 'name')] ?? ''));
            $variantCode .= $variantCode != null ? '-'.$choiceValue : $choiceValue;
        }

        return $variantCode;
    }

    /** @return array{product_count: int, customer_count: int, latest: ?Model} */
    public function getAuthorRestockSummary(string $addedBy, int|string|null $sellerId): array
    {
        $restockProducts = $this->getList(
            dataLimit: 'all',
            relations: ['product'],
            withCount: ['restockProductCustomers'],
            filters: [self::FILTER_ADDED_BY => $addedBy, self::FILTER_SELLER_ID => $sellerId],
            sortBy: self::SORT_RECENTLY_UPDATED,
        );

        return [
            'product_count' => $restockProducts->groupBy('product_id')->count(),
            'customer_count' => (int) $restockProducts->sum('restock_product_customers_count'),
            'latest' => $restockProducts->first(),
        ];
    }

    /** @return array<int, array<int, ?string>> */
    public function getVariantsByProductIds(array $productIds): array
    {
        return $this->query()
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'variant'])
            ->groupBy('product_id')
            ->map(static fn ($rows): array => $rows->pluck('variant')->all())
            ->all();
    }

    public function deleteBy(array $params): int
    {
        return $this->query()->where($params)->delete();
    }

    public function firstOrCreateForVariant(mixed $productId, ?string $variant): Model
    {
        return $this->model->newQuery()->firstOrCreate(
            ['product_id' => $productId, 'variant' => $variant],
            ['product_id' => $productId, 'variant' => $variant],
        );
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->model->newQuery()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function deleteWithoutCustomers(): int
    {
        return $this->model->newQuery()->whereDoesntHave('restockProductCustomers')->delete();
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function hasCustomerRequest(mixed $productId, ?string $variant, mixed $customerId): bool
    {
        return $this->query()
            ->where(['product_id' => $productId, 'variant' => $variant])
            ->whereHas('restockProductCustomers', fn (Builder $customer) => $customer->where('customer_id', $customerId))
            ->whereHas('product', fn (Builder $product) => $product->active())
            ->exists();
    }

    /** @return array<int, int> */
    public function getCustomerRequestIds(int $customerId, ?int $restockProductId = null): array
    {
        return $this->query()
            ->when($restockProductId !== null, fn (Builder $builder) => $builder->where('id', $restockProductId))
            ->whereHas('restockProductCustomers', fn (Builder $customer) => $customer->where('customer_id', $customerId))
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function getCustomerFcmTopics(int $customerId): array
    {
        return $this->query()
            ->whereHas('restockProductCustomers', fn (Builder $customer) => $customer->where('customer_id', $customerId))
            ->get()
            ->map(fn ($restockProduct) => getRestockProductFCMTopic(restockRequest: $restockProduct))
            ->all();
    }
}
