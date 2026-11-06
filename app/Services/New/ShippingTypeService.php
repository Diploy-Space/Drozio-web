<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\ShippingType;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ShippingTypeService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const TYPE_ORDER_WISE = 'order_wise';

    public const TYPE_CATEGORY_WISE = 'category_wise';

    public const TYPE_PRODUCT_WISE = 'product_wise';

    /** @var array<int, string> */
    public const SHIPPING_TYPES = [self::TYPE_ORDER_WISE, self::TYPE_CATEGORY_WISE, self::TYPE_PRODUCT_WISE];

    public const SHIPPING_METHOD_INHOUSE = 'inhouse_shipping';

    public const SHIPPING_METHOD_SELLERWISE = 'sellerwise_shipping';

    public const ADDED_BY_ADMIN = 'admin';

    public const ADMIN_SELLER_ID = 0;

    public function __construct(
        ShippingType $shippingType,
        private readonly CategoryShippingCostService $categoryShippingCostService,
    ) {
        parent::__construct($shippingType);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        return parent::query()->where('context', $this->businessModule());
    }

    public function resolveTypeForSeller(mixed $sellerId): string
    {
        return $this->findBy(params: ['seller_id' => $sellerId])?->shipping_type ?? self::TYPE_ORDER_WISE;
    }

    public function resolveProductShippingCost(object|array $product, mixed $quantity): float
    {
        $shippingType = $this->resolveTypeForSeller($this->shippingSellerIdFor($product));

        if ($shippingType === self::TYPE_CATEGORY_WISE) {
            return $this->categoryWiseCost($product, $quantity);
        }

        if ($shippingType === self::TYPE_PRODUCT_WISE) {
            return (float) ($product['multiply_qty'] == 1
                ? $quantity * $product['shipping_cost']
                : $product['shipping_cost']);
        }

        return 0.0;
    }

    public function resolveTypeForProduct(object|array $product): string
    {
        return $this->resolveTypeForSeller($this->shippingSellerIdFor($product));
    }

    public function isOrderWiseForProduct(object|array $product): bool
    {
        return $this->resolveTypeForProduct(product: $product) === self::TYPE_ORDER_WISE;
    }

    /**
     * @param  array{min: mixed, max: mixed}  $orderWiseCostRange
     * @return array{delivery_cost: mixed, delivery_cost_max: mixed, delivery_cost_min: mixed, shipping_type: string}
     */
    public function getProductDeliveryCharge(object|array $product, mixed $quantity, array $orderWiseCostRange): array
    {
        $shippingMethod = getWebConfig(name: $this->responsibilitySetting());
        $isInHouseProduct = $product['added_by'] === self::ADDED_BY_ADMIN;
        $shippingType = null;
        $categorySellerId = self::ADMIN_SELLER_ID;
        $deliveryCost = 0;
        $costRange = ['min' => null, 'max' => null];

        if ($shippingMethod == self::SHIPPING_METHOD_INHOUSE) {
            $shippingType = $this->findBy(params: ['seller_id' => self::ADMIN_SELLER_ID]);
            $categorySellerId = self::ADMIN_SELLER_ID;
        } elseif ($shippingMethod == self::SHIPPING_METHOD_SELLERWISE) {
            $shippingType = $isInHouseProduct
                ? $this->findBy(params: ['seller_id' => self::ADMIN_SELLER_ID])
                : $this->query()->where('seller_id', '!=', self::ADMIN_SELLER_ID)->where('seller_id', $product['user_id'])->first();
            $categorySellerId = $isInHouseProduct ? self::ADMIN_SELLER_ID : $product['user_id'];
        }

        if ($shippingType?->shipping_type == self::TYPE_CATEGORY_WISE) {
            $categoryShippingCost = $this->categoryShippingCostService->findBy(params: [
                'seller_id' => $categorySellerId,
                'category_id' => $product['category_id'],
            ]);
            $deliveryCost = $categoryShippingCost
                ? ($categoryShippingCost->multiply_qty != 0 ? ($categoryShippingCost->cost * $quantity) : $categoryShippingCost->cost)
                : 0;
        } elseif ($shippingType?->shipping_type == self::TYPE_PRODUCT_WISE) {
            $deliveryCost = $product['multiply_qty'] != 0 ? ($product['shipping_cost'] * $quantity) : $product['shipping_cost'];
        } elseif ($shippingType?->shipping_type == self::TYPE_ORDER_WISE) {
            $costRange = $orderWiseCostRange;
        }

        return [
            'delivery_cost' => $deliveryCost,
            'delivery_cost_max' => $costRange['max'] ?? 0,
            'delivery_cost_min' => $costRange['min'] ?? 0,
            'shipping_type' => $shippingType?->shipping_type ?? '',
        ];
    }

    protected function responsibilitySetting(): string
    {
        return 'shipping_method';
    }

    private function shippingSellerIdFor(object|array $product): mixed
    {
        $isInHouseShipping = getWebConfig(name: $this->responsibilitySetting()) === self::SHIPPING_METHOD_INHOUSE;

        return $isInHouseShipping || $product['added_by'] === self::ADDED_BY_ADMIN
            ? self::ADMIN_SELLER_ID
            : $product['user_id'];
    }

    private function categoryWiseCost(object|array $product, mixed $quantity): float
    {
        $categoryId = 0;

        foreach (json_decode($product['category_ids'] ?? '') ?? [] as $category) {
            if ($category->position == 1) {
                $categoryId = $category->id;
            }
        }

        $categoryShippingCost = $this->categoryShippingCostService->findBy(params: [
            'seller_id' => $this->shippingSellerIdFor($product),
            'category_id' => $categoryId,
        ]);

        if ($categoryShippingCost === null) {
            return 0.0;
        }

        return (float) ($categoryShippingCost->multiply_qty == 1
            ? $quantity * $categoryShippingCost->cost
            : $categoryShippingCost->cost);
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
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('shipping_type', 'like', "%{$searchValue}%")
                    ->when(is_numeric($searchValue), fn (Builder $query) => $query->orWhere('id', $searchValue));
            }));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create($data);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return false;
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrCreate($params, $data);
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
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
}
