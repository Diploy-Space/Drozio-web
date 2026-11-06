<?php

namespace App\Services\New\Clearance;

use App\Enums\ClearanceDiscountType;
use App\Models\StockClearanceProduct;
use App\Services\BaseService;
use App\Traits\SaleCacheTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StockClearanceProductService extends BaseService
{
    use SaleCacheTrait;

    public const PRIORITY_TYPE = 'stock_clearance_product_list_priority';

    public const VENDOR_PRIORITY_TYPE = 'stock_clearance_vendor_priority';

    public const SORT_CLEARANCE_EXPIRATION = 'clearance_expiration_date';

    public const FILTER_RUNNING = 'running';

    public const FILTER_ACTIVE = 'active';

    private const VENDOR_OFFER_IN_HOMEPAGE = 'stock_clearance_vendor_offer_in_homepage';

    private const SETUP_BY_ADMIN = 'admin';

    private const SETUP_BY_VENDOR = 'vendor';

    public const REASON_DISCOUNT_MISSING = 'discount_missing';

    public const REASON_DISCOUNT_NOT_POSITIVE = 'discount_not_positive';

    public const REASON_PERCENTAGE_OUT_OF_RANGE = 'percentage_out_of_range';

    public const REASON_ABOVE_UNIT_PRICE = 'above_unit_price';

    public const REASON_ABOVE_VARIATION_PRICE = 'above_variation_price';

    public const REASON_FLAT_SETUP_MANAGED = 'flat_setup_managed';

    public function __construct(StockClearanceProduct $stockClearanceProduct)
    {
        parent::__construct($stockClearanceProduct);
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
            ->when($searchValue, fn (Builder $builder) => $builder->whereHas(
                'product',
                fn (Builder $product) => $product->where('name', 'like', "%{$searchValue}%")
            ))
            ->latest('id');

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
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
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

        if (! empty($filters[self::FILTER_RUNNING])) {
            $query->active()->whereHas('product', fn (Builder $product) => $product->active());
        }

        if (! empty($filters[self::FILTER_ACTIVE])) {
            $query->active();
        }

        unset($filters[self::FILTER_RUNNING], $filters[self::FILTER_ACTIVE]);

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /** @return array<int, int> */
    public function getProductIds(?string $addedBy = null, ?int $vendorId = null, bool $shownInHomepage = false, bool $running = false): array
    {
        return $this->query()
            ->when($running, fn (Builder $query) => $query->active())
            ->when($addedBy !== null, fn (Builder $query) => $query->where('added_by', $addedBy))
            ->when($vendorId !== null, fn (Builder $query) => $query->where('user_id', $vendorId))
            ->when($shownInHomepage, fn (Builder $query) => $query
                ->active()
                ->whereHas('setup', fn (Builder $setup) => $setup
                    ->where('show_in_homepage', 1)
                    ->whereIn('setup_by', $this->homepageSetupOwners()))
                ->whereHas('product', fn (Builder $product) => $product->active()))
            ->pluck('product_id')
            ->all();
    }

    /** @return array<int, string> */
    private function homepageSetupOwners(): array
    {
        return getWebConfig(name: self::VENDOR_OFFER_IN_HOMEPAGE)
            ? [self::SETUP_BY_ADMIN, self::SETUP_BY_VENDOR]
            : [self::SETUP_BY_ADMIN];
    }

    public function getRunningProducts(
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
    ): Collection|LengthAwarePaginator {
        $query = $this->query()->active()->with($relations);

        $this->applyPriority($query);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    private function applyPriority(Builder $query): void
    {
        $priority = getWebConfig(name: self::PRIORITY_TYPE);
        $vendorPriority = getWebConfig(name: self::VENDOR_PRIORITY_TYPE);

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            $query->latest('id');

            return;
        }

        if (($priority['temporary_close_sorting'] ?? null) === 'hide') {
            $query->whereHas('product', fn (Builder $product) => $product
                ->where('added_by', 'seller')->whereHas('seller.shop', fn (Builder $shop) => $shop->where('temporary_close', 0))
                ->orWhere(fn (Builder $inner) => Cache::get(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS) ? $inner : $inner->where('added_by', 'admin')));
        }

        if (($priority['out_of_stock_product'] ?? null) === 'hide') {
            $query->whereHas('product', fn (Builder $product) => $product
                ->where('product_type', 'digital')
                ->orWhere(fn (Builder $inner) => $inner->where('product_type', 'physical')->where('current_stock', '>', 0)));
        }

        if (($priority['sort_by'] ?? null) === self::SORT_CLEARANCE_EXPIRATION) {
            $query->whereHas('setup', fn (Builder $setup) => $setup->where('duration_end_date', '>=', now()));
        }

        $this->joinProducts($query);

        if (($priority['temporary_close_sorting'] ?? null) === 'desc') {
            $query->orderByRaw($this->shopTemporaryCloseExpression());
        }

        if (($priority['out_of_stock_product'] ?? null) === 'desc') {
            $query->orderByRaw("CASE WHEN products.product_type = 'digital' OR products.current_stock > 0 THEN 0 ELSE 1 END");
        }

        if (! empty($vendorPriority)) {
            $shopIds = implode(',', array_map('intval', $vendorPriority));
            $query->orderByRaw("FIELD(stock_clearance_products.shop_id, {$shopIds})");
        }

        $this->applyPrioritySort($query, $priority['sort_by'] ?? null);

        $query->orderBy('product_id');
    }

    private function shopTemporaryCloseExpression(): string
    {
        $inHouseClosed = (int) (bool) Cache::get(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS);

        return "CASE WHEN products.added_by = 'admin' THEN {$inHouseClosed}
            ELSE COALESCE((SELECT shops.temporary_close FROM shops WHERE shops.seller_id = products.user_id LIMIT 1), 0) END";
    }

    private function joinProducts(Builder $query): void
    {
        $query->join('products', 'products.id', '=', 'stock_clearance_products.product_id')
            ->select('stock_clearance_products.*');
    }

    private function applyPrioritySort(Builder $query, ?string $sortBy): void
    {
        match ($sortBy) {
            'latest_created' => $query->orderByDesc('product_id'),
            'most_order' => $query->orderByRaw('(select count(*) from order_details where order_details.product_id = stock_clearance_products.product_id) desc'),
            'reviews_count' => $query->orderByRaw('(select count(*) from reviews where reviews.product_id = stock_clearance_products.product_id and reviews.status = 1) desc'),
            'rating' => $query->orderByRaw('(select avg(reviews.rating) from reviews where reviews.product_id = stock_clearance_products.product_id and reviews.status = 1) desc'),
            'a_to_z' => $query->orderBy('products.name'),
            'z_to_a' => $query->orderByDesc('products.name'),
            self::SORT_CLEARANCE_EXPIRATION => $query->orderByRaw('(select duration_end_date from stock_clearance_setups where stock_clearance_setups.id = stock_clearance_products.setup_id) asc'),
            default => $query->latest('id'),
        };
    }

    public function attach(Model $product, Model $config, string $addedBy, ?int $vendorId, ?int $shopId, string $discountType, mixed $discountAmount): ?Model
    {
        if ($this->findBy(params: ['product_id' => $product->id]) !== null) {
            return null;
        }

        $isFlatSetup = $config->discount_type === ClearanceDiscountType::FLAT;

        return DB::transaction(function () use ($product, $config, $addedBy, $vendorId, $shopId, $discountType, $discountAmount, $isFlatSetup): Model {
            $row = $this->query()->create([
                'added_by' => $addedBy,
                'user_id' => $vendorId,
                'shop_id' => $shopId,
                'is_active' => 1,
                'setup_id' => $config->id,
                'product_id' => $product->id,
                'discount_type' => $isFlatSetup ? ClearanceDiscountType::PERCENTAGE : $discountType,
                'discount_amount' => $isFlatSetup
                    ? $config->discount_amount
                    : $this->convertAmountToDefaultCurrency($discountType, $discountAmount),
            ]);

            $this->forgetSaleCache();

            return $row;
        });
    }

    /**
     * @param  array<int, array{product: Model, discount_type: ?string, discount_amount: mixed}>  $candidates
     * @return array{attached: int, reason: ?string}
     */
    public function attachMany(array $candidates, Model $config, string $addedBy, ?int $vendorId = null, ?int $shopId = null): array
    {
        foreach ($candidates as $candidate) {
            if ($this->findBy(params: ['product_id' => $candidate['product']->id]) !== null) {
                continue;
            }

            $reason = $this->validateDiscount(
                $candidate['product'],
                $config,
                $candidate['discount_type'] ?? null,
                $candidate['discount_amount'] ?? null,
            );

            if ($reason !== null) {
                return ['attached' => 0, 'reason' => $reason];
            }
        }

        $attached = 0;

        foreach ($candidates as $candidate) {
            $row = $this->attach(
                product: $candidate['product'],
                config: $config,
                addedBy: $addedBy,
                vendorId: $vendorId,
                shopId: $shopId,
                discountType: $candidate['discount_type'] ?? ClearanceDiscountType::PERCENTAGE,
                discountAmount: $candidate['discount_amount'] ?? null,
            );

            $attached += $row === null ? 0 : 1;
        }

        return ['attached' => $attached, 'reason' => null];
    }

    public function updateDiscount(mixed $productId, string $discountType, mixed $discountAmount, ?int $vendorId = null): bool
    {
        $isUpdated = (bool) $this->query()
            ->where('product_id', $productId)
            ->when($vendorId !== null, fn (Builder $query) => $query->where('user_id', $vendorId))
            ->update([
                'discount_type' => $discountType,
                'discount_amount' => $this->convertAmountToDefaultCurrency($discountType, $discountAmount),
            ]);

        if ($isUpdated) {
            $this->forgetSaleCache();
        }

        return $isUpdated;
    }

    public function updateStatus(mixed $productId, bool $status, ?int $vendorId = null): bool
    {
        $isUpdated = (bool) $this->query()
            ->where('product_id', $productId)
            ->when($vendorId !== null, fn (Builder $query) => $query->where('user_id', $vendorId))
            ->update(['is_active' => (int) $status]);

        if ($isUpdated) {
            $this->forgetSaleCache();
        }

        return $isUpdated;
    }

    public function applyFlatDiscountToProducts(mixed $setupId, mixed $discountAmount): bool
    {
        $isUpdated = (bool) $this->query()->where('setup_id', $setupId)->update([
            'discount_type' => ClearanceDiscountType::PERCENTAGE,
            'discount_amount' => $discountAmount,
        ]);

        $this->forgetSaleCache();

        return $isUpdated;
    }

    public function detach(mixed $productId, ?int $vendorId = null): bool
    {
        $isDeleted = (bool) $this->query()
            ->where('product_id', $productId)
            ->when($vendorId !== null, fn (Builder $query) => $query->where('user_id', $vendorId))
            ->delete();

        if ($isDeleted) {
            $this->forgetSaleCache();
        }

        return $isDeleted;
    }

    public function detachAll(string $addedBy, ?int $vendorId = null): bool
    {
        $isDeleted = (bool) $this->query()
            ->where('added_by', $addedBy)
            ->when($vendorId !== null, fn (Builder $query) => $query->where('user_id', $vendorId))
            ->delete();

        if ($isDeleted) {
            $this->forgetSaleCache();
        }

        return $isDeleted;
    }

    public function validateDiscountUpdate(Model $product, Model $config, ?string $discountType, mixed $discountAmount): ?string
    {
        if ($config->discount_type === ClearanceDiscountType::FLAT) {
            return self::REASON_FLAT_SETUP_MANAGED;
        }

        return $this->validateDiscount(
            product: $product,
            config: $config,
            discountType: $discountType,
            discountAmount: $discountAmount,
        );
    }

    public function validateDiscount(Model $product, Model $config, ?string $discountType, mixed $discountAmount): ?string
    {
        if ($config->discount_type === ClearanceDiscountType::FLAT) {
            return null;
        }

        if ($discountAmount === null || $discountAmount === '') {
            return self::REASON_DISCOUNT_MISSING;
        }

        if ($discountAmount <= 0) {
            return self::REASON_DISCOUNT_NOT_POSITIVE;
        }

        if ($discountType === ClearanceDiscountType::PERCENTAGE) {
            return $discountAmount < 1 || $discountAmount > 100 ? self::REASON_PERCENTAGE_OUT_OF_RANGE : null;
        }

        $variationPrices = $this->variationPrices($product);

        if ($variationPrices === []) {
            return currencyConverter(amount: $discountAmount) > $product->unit_price ? self::REASON_ABOVE_UNIT_PRICE : null;
        }

        return $discountAmount > min($variationPrices) ? self::REASON_ABOVE_VARIATION_PRICE : null;
    }

    public function isDiscountBelowProductPrice(?Model $stockClearanceProduct): bool
    {
        $isFlatUnderProductWiseSetup = $stockClearanceProduct !== null
            && $stockClearanceProduct->discount_type === ClearanceDiscountType::FLAT
            && $stockClearanceProduct->setup?->discount_type === ClearanceDiscountType::PRODUCT_WISE;

        if (! $isFlatUnderProductWiseSetup) {
            return true;
        }

        $product = $stockClearanceProduct->product;
        $prices = array_merge([$product?->unit_price], $this->variationPrices($product));

        return min(array_filter($prices, static fn ($price) => $price !== null)) >= $stockClearanceProduct->discount_amount;
    }

    /**
     * @return array<int, float>
     */
    private function variationPrices(?Model $product): array
    {
        if (empty($product->variation) || $product->variation === '[]') {
            return [];
        }

        $variation = is_array($product->variation) ? $product->variation : json_decode($product->variation, true);

        return is_array($variation) ? array_column($variation, 'price') : [];
    }

    private function convertAmountToDefaultCurrency(string $discountType, mixed $discountAmount): mixed
    {
        return $discountType === ClearanceDiscountType::PERCENTAGE
            ? $discountAmount
            : currencyConverter(amount: $discountAmount);
    }
}
