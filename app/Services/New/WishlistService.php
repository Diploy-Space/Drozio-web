<?php

namespace App\Services\New;

use App\Models\Wishlist;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class WishlistService extends BaseService
{
    public const FILTER_HAS_PRODUCT = 'has_product';

    public const FILTER_HAS_PRODUCT_NAMED = 'has_product_named';

    public const FILTER_BUSINESS_MODULE = 'business_module';

    public const RELATION_ACTIVE_WISHLIST_PRODUCT = 'wishlistProduct';

    public const RELATION_ACTIVE_PRODUCT = 'product';

    public function __construct(Wishlist $wishlist)
    {
        parent::__construct($wishlist);
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
        $query = $this->query()->with($relations)->withCount($withCount)->latest('id');

        if (! empty($filters[self::FILTER_HAS_PRODUCT])) {
            $query->whereHas(
                self::RELATION_ACTIVE_WISHLIST_PRODUCT,
                fn (Builder $product) => $product->when(
                    $searchValue,
                    fn (Builder $matched) => $matched->where('name', 'like', "%{$searchValue}%")
                        ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$searchValue}%")),
                ),
            );
        }

        if (array_key_exists(self::FILTER_HAS_PRODUCT_NAMED, $filters)) {
            $productName = $filters[self::FILTER_HAS_PRODUCT_NAMED];

            $query->whereHas(
                self::RELATION_ACTIVE_WISHLIST_PRODUCT,
                fn (Builder $product) => $product->when(
                    ! empty($productName),
                    fn (Builder $matched) => $matched->where('name', 'like', "%{$productName}%"),
                ),
            );
        }

        if (array_key_exists(self::FILTER_BUSINESS_MODULE, $filters)) {
            $query->whereHas(
                'productFullInfo',
                fn (Builder $product) => $product->businessModule($filters[self::FILTER_BUSINESS_MODULE]),
            );
        }

        unset($filters[self::FILTER_HAS_PRODUCT], $filters[self::FILTER_HAS_PRODUCT_NAMED], $filters[self::FILTER_BUSINESS_MODULE]);

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create($data);
    }

    public function delete(mixed $id): bool
    {
        return (bool) $this->query()->whereKey($id)->delete();
    }

    public function deleteBy(array $params): int
    {
        $query = $this->query();

        if (array_key_exists(self::FILTER_BUSINESS_MODULE, $params)) {
            $query->whereHas(
                'productFullInfo',
                fn (Builder $product) => $product->businessModule($params[self::FILTER_BUSINESS_MODULE]),
            );

            unset($params[self::FILTER_BUSINESS_MODULE]);
        }

        $this->applyFilters($query, $params);

        return $query->delete();
    }

    public function toggleForCustomer(int $customerId, int $productId): bool
    {
        $wishlist = $this->findBy(params: ['customer_id' => $customerId, 'product_id' => $productId]);

        if ($wishlist !== null) {
            $this->delete(id: $wishlist->id);

            return false;
        }

        $this->create(data: ['customer_id' => $customerId, 'product_id' => $productId]);

        return true;
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function countForCustomer(?int $customerId): int
    {
        return $this->query()
            ->whereHas(self::RELATION_ACTIVE_WISHLIST_PRODUCT)
            ->whereHas('productFullInfo', fn (Builder $product) => $product->whereNotIn('business_module', $this->unavailableBusinessModules()))
            ->where('customer_id', $customerId)
            ->count();
    }

    /** @return array<string, int> */
    public function getCustomerCountsByBusinessModule(?int $customerId): array
    {
        if ($customerId === null) {
            return [];
        }

        return $this->query()
            ->join('products', 'products.id', '=', 'wishlists.product_id')
            ->whereHas(self::RELATION_ACTIVE_WISHLIST_PRODUCT)
            ->where('wishlists.customer_id', $customerId)
            ->whereNotIn('products.business_module', $this->unavailableBusinessModules())
            ->selectRaw('products.business_module, COUNT(*) as total')
            ->groupBy('products.business_module')
            ->pluck('total', 'business_module')
            ->map(fn (mixed $total) => (int) $total)
            ->all();
    }

    /** @return array<int, int> */
    public function getProductIds(?int $customerId, ?string $activeProductRelation = self::RELATION_ACTIVE_WISHLIST_PRODUCT): array
    {
        $query = $this->query()
            ->where('customer_id', $customerId)
            ->whereHas('productFullInfo', fn (Builder $product) => $product->whereNotIn('business_module', $this->unavailableBusinessModules()));

        if ($activeProductRelation !== null) {
            $query->whereHas($activeProductRelation, fn (Builder $product) => $product->active());
        }

        return $query->pluck('product_id')->all();
    }

    /** @return array<int, string> */
    private function unavailableBusinessModules(): array
    {
        return array_values(array_diff(
            array_keys(businessModuleManager()->all()),
            array_keys(businessModuleManager()->available()),
        ));
    }
}
