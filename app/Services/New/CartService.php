<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Cart;
use App\Services\BaseService;
use App\Utils\Helpers;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class CartService extends BaseService
{
    use VatTaxManagement;

    public const FILTER_ACTIVE_PRODUCT = 'active_product';

    public const GUEST = 1;

    public const CUSTOMER = 0;

    public const SORT_OLDEST = 'oldest';

    public const SHIPPING_METHOD_INHOUSE = 'inhouse_shipping';

    public const SHIPPING_METHOD_SELLERWISE = 'sellerwise_shipping';

    public const SELLER_IS_ADMIN = 'admin';

    public const ADMIN_SHIPPING_SELLER_ID = 0;

    public const PRODUCT_TYPE_PHYSICAL = 'physical';

    public const PRODUCT_TYPE_DIGITAL = 'digital';

    public const TYPE_CHECKED = 'checked';

    public const IN_HOUSE_CART_SELLER_ID = 1;

    public const GUEST_CART_GROUP_PREFIX = 'guest';

    public const STATUS_FAILED = 0;

    public const STATUS_SUCCESS = 1;

    public const STATUS_SHIPPING_METHOD_REQUIRED = 2;

    public const REDIRECT_TO_CHECKOUT = 'checkout';

    public const VARIANT_TYPE_MULTI = 'multi_variant';

    public const VARIANT_TYPE_SINGLE = 'single_variant';

    public const DIGITAL_READY_AFTER_SELL = 'ready_after_sell';

    public const DIGITAL_READY_PRODUCT = 'ready_product';

    public const REASON_PRODUCT_UNAVAILABLE = 'product-unavailable';

    public const REASON_SHOP_UNAVAILABLE = 'shop-unavailable';

    public const REASON_OUT_OF_STOCK = 'out-of-stock';

    public const REASON_BELOW_MINIMUM_ORDER_QUANTITY = 'below-minimum-order-quantity';

    public const REASON_BELOW_MINIMUM_ORDER_AMOUNT = 'below-minimum-order-amount';

    public const REASON_SHIPPING_METHOD_NOT_FOUND = 'shipping-method-not-found';

    public const REASON_SHIPPING_METHOD_REQUIRED = 'shipping-method-required';

    public const REASON_SHIPPING_UNAVAILABLE = 'shipping-unavailable';

    public const REASON_ADDED_FOR_CHECKOUT = 'added-for-checkout';

    public const REASON_ADDED = 'added';

    public const REASON_CART_ITEM_NOT_FOUND = 'cart-item-not-found';

    public const REASON_QUANTITY_UPDATED = 'quantity-updated';

    public const REASON_STOCK_LIMITED = 'stock-limited';

    public const REASON_PRODUCT_NOT_AVAILABLE = 'product-not-available';

    public const REASON_NOT_ENOUGH_STOCK = 'not-enough-stock';

    public const REASON_VARIATION_NOT_ENOUGH_STOCK = 'variation-not-enough-stock';

    public const REASON_DIGITAL_FILE_MISSING = 'digital-file-missing';

    public const REASON_CART_EMPTY = 'cart-empty';

    public const REASON_NO_CHECKED_ITEMS = 'no-checked-items';

    public const REASON_SHIPPING_NOT_AVAILABLE_FOR_SHOP = 'shipping-not-available-for-shop';

    public const REASON_SHOP_SHIPPING_METHOD_REQUIRED = 'shop-shipping-method-required';

    public const SHIPPING_METHOD_REQUIRED_REASONS = [self::REASON_SHIPPING_METHOD_REQUIRED, self::REASON_SHOP_SHIPPING_METHOD_REQUIRED];

    public const REASON_UNAVAILABLE_VENDORS = 'unavailable-vendors';

    public const REASON_UNAVAILABLE_PRODUCT = 'unavailable-product';

    public const ERROR_TYPE_MINIMUM_ORDER_AMOUNT = 'minimum-order-amount';

    public const ERROR_TYPE_EMPTY_CART = 'empty-cart';

    public const ERROR_TYPE_EMPTY_SHIPPING = 'empty-shipping';

    public const REDIRECT_HOME = 'home';

    public const REDIRECT_SHOP_CART = 'shop-cart';

    public const SELLER_IS_SELLER = 'seller';

    public const DISCOUNT_TYPE_COUPON = 'coupon_discount';

    public const EXTRA_DISCOUNT_TYPE_FREE_SHIPPING = 'free_shipping_over_order_amount';

    public const COUPON_BEARER_INHOUSE = 'inhouse';

    public const FREE_DELIVERY_BEARER_ADMIN = 'admin';

    public const TAX_TYPE_PRODUCT_WISE = 'product_wise';

    public const TAX_TYPE_CATEGORY_WISE = 'category_wise';

    public const TAX_TYPE_ORDER_WISE = 'order_wise';

    /** @var array<int, string> */
    public const ORDERED_CART_SESSION_KEYS = [
        'coupon_code',
        'coupon_type',
        'coupon_bearer',
        'coupon_discount',
        'payment_method',
        'shipping_method_id',
        'billing_address_id',
        'order_id',
        'cart_group_id',
        'order_note',
    ];

    public const APPLIED_TAX_TYPE_INCLUDE = 'include';

    public const APPLIED_TAX_TYPE_EXCLUDE = 'exclude';

    public const POS_EXTRA_DISCOUNT_PERCENT = 'percent';

    private const EMPTY_FREE_DELIVERY_ORDER_AMOUNT = [
        'status' => 0,
        'amount' => 0,
        'percentage' => 0,
        'shipping_cost_saved' => 0,
    ];

    private const EMPTY_GROUP_COUPON_DISCOUNT = [
        'discount' => 0,
        'coupon_bearer' => self::COUPON_BEARER_INHOUSE,
        'coupon_code' => 0,
        'coupon_type' => null,
    ];

    private const SHIPPING_METHOD_SETTING = 'shipping_method';

    private const COMPANY_NAME_SETTING = 'company_name';

    private const BILLING_INPUT_BY_CUSTOMER_SETTING = 'billing_input_by_customer';

    private const COUPON_TYPE_FREE_DELIVERY = 'free_delivery';

    private const MINIMUM_ORDER_AMOUNT_STATUS_SETTING = 'minimum_order_amount_status';

    private const MINIMUM_ORDER_AMOUNT_BY_SELLER_SETTING = 'minimum_order_amount_by_seller';

    private const MINIMUM_ORDER_AMOUNT_SETTING = 'minimum_order_amount';

    private const SELLER_MINIMUM_ORDER_AMOUNT_COLUMN = 'minimum_order_amount';

    public function __construct(
        Cart $cart,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly CartShippingService $cartShippingService,
    ) {
        parent::__construct($cart);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function shippingResponsibilitySetting(): string
    {
        return self::SHIPPING_METHOD_SETTING;
    }

    /** @return array<int, string> */
    protected function orderedCartSessionKeys(): array
    {
        return self::ORDERED_CART_SESSION_KEYS;
    }

    /** @return array{status: string, by_seller: string, in_house_amount: string} */
    protected function minimumOrderAmountSettings(): array
    {
        return [
            'status' => self::MINIMUM_ORDER_AMOUNT_STATUS_SETTING,
            'by_seller' => self::MINIMUM_ORDER_AMOUNT_BY_SELLER_SETTING,
            'in_house_amount' => self::MINIMUM_ORDER_AMOUNT_SETTING,
        ];
    }

    protected function sellerMinimumOrderAmountColumn(): string
    {
        return self::SELLER_MINIMUM_ORDER_AMOUNT_COLUMN;
    }

    protected function billingInputSetting(): string
    {
        return self::BILLING_INPUT_BY_CUSTOMER_SETTING;
    }

    /** @return array<int|string, mixed> */
    protected function cartProductRelations(): array
    {
        $activeTaxVats = fn ($query) => $query->with(['tax'])->whereHas('tax', fn ($tax) => $tax->where('is_active', 1));

        return [
            'product' => fn ($query) => $query->active()->with([
                'category' => fn ($category) => $category->with(['taxVats' => $activeTaxVats]),
                'clearanceSale' => fn ($clearanceSale) => $clearanceSale->active(),
                'taxVats' => $activeTaxVats,
            ]),
            'seller.shop',
            'allProducts.seller.shop',
        ];
    }

    protected function resolveItemDiscount(Model $cartItem): float
    {
        return getProductPriceByType(product: $cartItem->product, type: 'discounted_amount', result: 'value', price: $cartItem->price);
    }

    protected function resolveItemStock(Model $cartItem): mixed
    {
        $totalCurrentStock = $cartItem->product['current_stock'] ?? 0;

        foreach (! empty($cartItem->product['variation']) ? json_decode($cartItem->product['variation']) : [] as $variation) {
            if ($cartItem['variant'] == $variation->type) {
                $totalCurrentStock = $variation->qty;
            }
        }

        return $totalCurrentStock;
    }

    public function isBillingInputByCustomer(): bool
    {
        return (bool) getWebConfig(name: $this->billingInputSetting());
    }

    /** @return array{status: string, responsibility: string, in_house_status: string, in_house_amount: string, seller_amount: string, seller_status_column: string, seller_amount_column: string} */
    protected function freeDeliverySettings(): array
    {
        return [
            'status' => 'free_delivery_status',
            'responsibility' => 'free_delivery_responsibility',
            'in_house_status' => BusinessSettingService::IN_HOUSE_FREE_DELIVERY_SETTINGS[BusinessModule::RETAIL]['status'],
            'in_house_amount' => 'free_delivery_over_amount',
            'seller_amount' => 'free_delivery_over_amount_seller',
            'seller_status_column' => 'free_delivery_status',
            'seller_amount_column' => 'free_delivery_over_amount',
        ];
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
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount);

        $filters = $this->applyProductFilter($query, $filters);

        match ($sortBy) {
            self::SORT_OLDEST => $query->orderBy('id'),
            default => $query->orderByDesc('id'),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function updateShippingForCarts(iterable $cartList): void
    {
        $shippingMethod = getWebConfig(name: $this->shippingResponsibilitySetting());

        foreach (collect($cartList)->groupBy('cart_group_id') as $cartGroupItems) {
            $shippingType = $this->resolveShippingType($shippingMethod, $cartGroupItems->first());

            foreach ($cartGroupItems as $cartItem) {
                if ($shippingType === ShippingTypeService::TYPE_ORDER_WISE) {
                    $this->updateWhere(
                        params: ['id' => $cartItem->id],
                        data: ['shipping_cost' => 0, 'shipping_type' => $shippingType],
                    );

                    continue;
                }

                $this->cartShippingService->deleteBy(params: ['cart_group_id' => $cartItem?->cart_group_id]);

                if ($cartItem?->product === null) {
                    continue;
                }

                $this->updateWhere(params: ['id' => $cartItem->id], data: [
                    'shipping_cost' => $cartItem['product_type'] === self::PRODUCT_TYPE_PHYSICAL
                        ? $this->shippingTypeService->resolveProductShippingCost(
                            product: $cartItem->product,
                            quantity: $cartItem['quantity'],
                        )
                        : 0,
                    'shipping_type' => $shippingType,
                ]);
            }
        }
    }

    private function resolveShippingType(?string $shippingMethod, mixed $firstItem): string
    {
        if ($shippingMethod === self::SHIPPING_METHOD_INHOUSE) {
            return $this->shippingTypeService->resolveTypeForSeller(self::ADMIN_SHIPPING_SELLER_ID);
        }

        if ($shippingMethod === self::SHIPPING_METHOD_SELLERWISE) {
            return $firstItem?->seller_is === self::SELLER_IS_ADMIN
                ? $this->shippingTypeService->resolveTypeForSeller(self::ADMIN_SHIPPING_SELLER_ID)
                : $this->shippingTypeService->resolveTypeForSeller($firstItem?->seller_id);
        }

        return '';
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function deleteBy(array $params): int
    {
        return $this->query()->where($params)->delete();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $filters = $this->applyProductFilter($query, $filters);

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function applyProductFilter(Builder $query, array $filters): array
    {
        if (! empty($filters[self::FILTER_ACTIVE_PRODUCT])) {
            $query->whereHas('product', fn (Builder $builder) => $builder->active());
        }

        unset($filters[self::FILTER_ACTIVE_PRODUCT]);

        return $filters;
    }

    public function getCartsForProduct(int $productId): Collection
    {
        return $this->query()->with('product')->where('product_id', $productId)->get();
    }

    public function create(array $data): ?Model
    {
        return $this->insertCartRow(cartRow: $data);
    }

    public function getCartGroupIds(?int $customerId, int|string|null $guestId, ?string $type = null): array
    {
        return $this->query()
            ->whereHas('product', fn (Builder $builder) => $builder->active())
            ->where($this->ownerParams(customerId: $customerId, guestId: $guestId))
            ->when($type === self::TYPE_CHECKED, fn (Builder $builder) => $builder->where('is_checked', 1))
            ->groupBy('cart_group_id')
            ->pluck('cart_group_id')
            ->toArray();
    }

    public function getOwnerCartGroupIds(int|string|null $ownerId, int $isGuest): array
    {
        return $this->query()
            ->where(['customer_id' => $ownerId, 'is_guest' => $isGuest])
            ->pluck('cart_group_id')
            ->toArray();
    }

    public function getOwnerVariantList(int|string|null $ownerId): Collection
    {
        return $this->query()->where('customer_id', $ownerId)->select(['id', 'variant'])->get();
    }

    public function updateCheckedItems(int|string|null $ownerId, int $isGuest, array $ids): void
    {
        $this->query()->where(['customer_id' => $ownerId, 'is_guest' => $isGuest])->update(['is_checked' => 0]);

        if ($ids !== []) {
            $this->query()
                ->where(['customer_id' => $ownerId, 'is_guest' => $isGuest])
                ->whereIn('id', $ids)
                ->update(['is_checked' => 1]);
        }
    }

    public function getCartItemList(
        ?int $customerId = null,
        int|string|null $guestId = null,
        ?string $cartGroupId = null,
        ?string $type = null,
    ): Collection {
        $cartItems = $this->query()
            ->with($this->cartProductRelations())
            ->whereHas('product', fn (Builder $builder) => $builder->active())
            ->when(! $cartGroupId, fn (Builder $builder) => $builder->whereIn(
                'cart_group_id',
                $this->getCartGroupIds(customerId: $customerId, guestId: $guestId),
            ))
            ->when($cartGroupId, fn (Builder $builder) => $builder->where('cart_group_id', $cartGroupId))
            ->when($type === self::TYPE_CHECKED, fn (Builder $builder) => $builder->where('is_checked', 1))
            ->get();

        foreach ($cartItems as $item) {
            $item->discount = $this->resolveItemDiscount(cartItem: $item);
        }

        return $cartItems;
    }

    public function getSubtotalWithoutShipping(iterable $cartItems): float|int
    {
        $total = 0;

        foreach ($cartItems as $item) {
            $total += ($item['price'] * $item['quantity']) - $item['discount'] * $item['quantity'];
        }

        return $total;
    }

    public function getShippingCost(
        ?int $customerId = null,
        int|string|null $guestId = null,
        ?string $cartGroupId = null,
        ?string $type = null,
    ): float|int|string {
        $isCheckedOnly = $type === self::TYPE_CHECKED;
        $cartGroupIds = $cartGroupId === null
            ? $this->getCartGroupIds(customerId: $customerId, guestId: $guestId, type: $isCheckedOnly ? self::TYPE_CHECKED : null)
            : [];

        $cartShippingCost = $this->query()
            ->where('product_type', self::PRODUCT_TYPE_PHYSICAL)
            ->whereHas('product', fn (Builder $builder) => $builder->active())
            ->when($cartGroupId === null, fn (Builder $builder) => $builder->whereIn('cart_group_id', $cartGroupIds))
            ->when($cartGroupId !== null, fn (Builder $builder) => $builder->where('cart_group_id', $cartGroupId))
            ->when($isCheckedOnly, fn (Builder $builder) => $builder->where('is_checked', 1))
            ->sum('shipping_cost');

        $orderWiseShippingCost = $this->cartShippingService->getOrderWiseShippingCost(
            cartGroupId: $cartGroupId,
            cartGroupIds: $cartGroupIds,
            isCheckedOnly: $isCheckedOnly,
        );

        return $orderWiseShippingCost + $cartShippingCost;
    }

    public function refreshShippingCosts(
        ?int $customerId,
        int|string|null $guestId,
        ?string $cartGroupId = null,
        ?string $type = null,
    ): void {
        $isCheckedOnly = $type === self::TYPE_CHECKED;
        $cartGroupIds = $cartGroupId === null
            ? $this->getCartGroupIds(customerId: $customerId, guestId: $guestId, type: $isCheckedOnly ? self::TYPE_CHECKED : null)
            : [];

        $cartItems = $this->query()
            ->where('product_type', self::PRODUCT_TYPE_PHYSICAL)
            ->with(['product'])
            ->whereHas('product', fn (Builder $builder) => $builder->active())
            ->when($cartGroupId === null, fn (Builder $builder) => $builder->whereIn('cart_group_id', $cartGroupIds))
            ->when($cartGroupId !== null, fn (Builder $builder) => $builder->where('cart_group_id', $cartGroupId))
            ->where($this->ownerParams(customerId: $customerId, guestId: $guestId))
            ->when($isCheckedOnly, fn (Builder $builder) => $builder->where('is_checked', 1))
            ->get();

        $this->updateShippingForCarts(cartList: $cartItems);
    }

    public function refreshPrices(?int $customerId, int|string|null $guestId): void
    {
        $cartItems = $this->query()
            ->with(['product.digitalVariation'])
            ->whereHas('product', fn (Builder $builder) => $builder->active())
            ->where($this->ownerParams(customerId: $customerId, guestId: $guestId))
            ->get();

        foreach ($cartItems as $cartItem) {
            $this->refreshItemTax(cartItem: $cartItem, product: $cartItem?->product);
            $this->refreshItemPrice(cartItem: $cartItem, product: $cartItem?->product);
        }
    }

    public function moveGuestCartToCustomer(int|string|null $guestId, int $customerId): void
    {
        $guestCartItems = $this->query()->where(['is_guest' => self::GUEST, 'customer_id' => $guestId])->get();

        foreach ($guestCartItems as $cartItem) {
            $customerSellerCart = $this->query()->where([
                'customer_id' => $customerId,
                'is_guest' => self::CUSTOMER,
                'seller_id' => $cartItem['seller_id'],
                'seller_is' => $cartItem['seller_is'],
            ])->first();

            $this->query()->where([
                'customer_id' => $customerId,
                'is_guest' => self::CUSTOMER,
                'product_id' => $cartItem['product_id'],
                'variant' => $cartItem['variant'],
                'seller_id' => $cartItem['seller_id'],
                'seller_is' => $cartItem['seller_is'],
            ])->delete();

            $cartItem->cart_group_id = $customerSellerCart !== null
                ? $customerSellerCart['cart_group_id']
                : str_replace(self::GUEST_CART_GROUP_PREFIX, (string) $customerId, $cartItem['cart_group_id']);
            $cartItem->customer_id = $customerId;
            $cartItem->is_guest = self::CUSTOMER;
            $cartItem->save();
        }
    }

    public function addToCart(
        Model $product,
        array $data,
        ?int $customerId,
        int|string|null $guestId,
        bool $isShopClosed,
        string $shippingType,
        ?Collection $sellerShippingList,
        ?string $colorName = null,
        ?Model $digitalVariation = null,
        ?Model $shippingMethod = null,
    ): array {
        if ($product['status'] == 0) {
            return ['status' => self::STATUS_FAILED, 'reason' => self::REASON_PRODUCT_UNAVAILABLE];
        }

        if ($isShopClosed) {
            return ['status' => self::STATUS_FAILED, 'reason' => self::REASON_SHOP_UNAVAILABLE];
        }

        if ($product['product_type'] == self::PRODUCT_TYPE_DIGITAL) {
            return $this->addDigitalProduct(
                product: $product,
                data: $data,
                customerId: $customerId,
                guestId: $guestId,
                shippingType: $shippingType,
                digitalVariation: $digitalVariation,
            );
        }

        return $this->addPhysicalProduct(
            product: $product,
            data: $data,
            customerId: $customerId,
            guestId: $guestId,
            shippingType: $shippingType,
            sellerShippingList: $sellerShippingList,
            colorName: $colorName,
            shippingMethod: $shippingMethod,
        );
    }

    public function updateItemQuantity(
        ?Model $cartItem,
        ?Model $product,
        mixed $quantity,
        bool $isBuyNow,
        ?int $customerId,
        int|string|null $guestId,
    ): array {
        if ($cartItem === null) {
            return ['status' => self::STATUS_FAILED, 'qty' => $quantity, 'reason' => self::REASON_CART_ITEM_NOT_FOUND];
        }

        $status = self::STATUS_SUCCESS;
        $updatedQuantity = 0;
        $variations = json_decode($product->retailDetail?->variation ?? '[]');

        if (count($variations)) {
            foreach ($variations as $variation) {
                if ($variation->type == $cartItem['variant'] && $variation->qty < $quantity) {
                    $status = self::STATUS_FAILED;
                    $updatedQuantity = $cartItem['quantity'];
                }
            }
        } elseif (($product['product_type'] == self::PRODUCT_TYPE_PHYSICAL) && $product['current_stock'] < $quantity) {
            $status = self::STATUS_FAILED;
            $updatedQuantity = $cartItem['quantity'];
        }

        if ($status) {
            $updatedQuantity = $quantity;
            $cartItem['quantity'] = $quantity;
            $cartItem['shipping_cost'] = $product->product_type == self::PRODUCT_TYPE_PHYSICAL
                ? $this->shippingTypeService->resolveProductShippingCost(product: $product, quantity: $quantity)
                : 0;
        }

        $cartItem->save();

        if ($isBuyNow) {
            $ownerParams = $this->ownerParams(customerId: $customerId, guestId: $guestId);
            $this->query()->where($ownerParams)->update(['is_checked' => 0]);
            $this->query()->where(['id' => $cartItem['id'], 'customer_id' => $ownerParams['customer_id']])->update(['is_checked' => 1]);
        }

        return [
            'status' => $status,
            'qty' => $updatedQuantity,
            'reason' => $status == self::STATUS_SUCCESS ? self::REASON_QUANTITY_UPDATED : self::REASON_STOCK_LIMITED,
        ];
    }

    /** @param array{reason: string, minimum_order_qty?: mixed, minimum_order_amount?: mixed, shop_name?: ?string} $result */
    public function reasonMessage(array $result): string
    {
        return match ($result['reason']) {
            self::REASON_PRODUCT_UNAVAILABLE => translate('Product is unavailable'),
            self::REASON_SHOP_UNAVAILABLE => translate('Shop is unavailable'),
            self::REASON_OUT_OF_STOCK => translate('Out of Stock'),
            self::REASON_BELOW_MINIMUM_ORDER_QUANTITY => translate('Minimum order quantity').' '.$result['minimum_order_qty'],
            self::REASON_BELOW_MINIMUM_ORDER_AMOUNT => translate('The minimum order amount for :shop is :amount', ['amount' => webCurrencyConverter(amount: $result['minimum_order_amount']), 'shop' => $result['shop_name']]),
            self::REASON_SHIPPING_METHOD_NOT_FOUND => translate('Not found'),
            self::REASON_SHIPPING_METHOD_REQUIRED => translate('Select shipping method'),
            self::REASON_SHIPPING_UNAVAILABLE => translate('Shipping Not Available for this Shop'),
            self::REASON_ADDED_FOR_CHECKOUT => translate('Added successfully').'!',
            self::REASON_ADDED => translate('Successfully added').'!',
            self::REASON_CART_ITEM_NOT_FOUND => translate('Product not found in cart'),
            self::REASON_QUANTITY_UPDATED => translate('Updated successfully'),
            self::REASON_STOCK_LIMITED => translate('Sorry stock is limited'),
        };
    }

    public function hasEnoughStock(iterable $cartItems): bool
    {
        $status = true;

        foreach ($cartItems as $cartItem) {
            if (! $cartItem->product) {
                $status = false;

                continue;
            }

            $product = $cartItem->product;
            $variations = json_decode($product->retailDetail?->variation ?? '[]');

            if (count($variations)) {
                foreach ($variations as $variation) {
                    if ($variation->type == $cartItem['variant'] && $variation->qty < $cartItem->quantity) {
                        $status = false;
                    }
                }
            } elseif (($product['product_type'] == self::PRODUCT_TYPE_PHYSICAL) && $product['current_stock'] < $cartItem->quantity) {
                $status = false;
            }
        }

        return $status;
    }

    public function getShopMinimumOrderAmount(bool $isInHouse, ?Model $seller): float
    {
        $minimumOrderAmountSettings = $this->minimumOrderAmountSettings();

        if (! getWebConfig(name: $minimumOrderAmountSettings['status'])) {
            return 0.0;
        }

        if ($isInHouse) {
            return (float) getWebConfig(name: $minimumOrderAmountSettings['in_house_amount']);
        }

        return getWebConfig(name: $minimumOrderAmountSettings['by_seller'])
            ? (float) ($seller?->{$this->sellerMinimumOrderAmountColumn()} ?? 0)
            : 0.0;
    }

    public function verifySingleProductMinimumOrderAmount(Model $product, float|int $totalAmount): array
    {
        $status = self::STATUS_SUCCESS;
        $minimumOrderAmount = 0;
        $shopName = null;

        if (getWebConfig(name: $this->minimumOrderAmountSettings()['status'])) {
            $isInHouse = $product->added_by == self::SELLER_IS_ADMIN;
            $minimumOrderAmount = $this->getShopMinimumOrderAmount(isInHouse: $isInHouse, seller: $product->seller);
            $status = $minimumOrderAmount > $totalAmount ? self::STATUS_FAILED : self::STATUS_SUCCESS;

            if ($minimumOrderAmount > $totalAmount) {
                $shopName = $isInHouse ? getInHouseShopConfig(key: 'name') : $product->seller?->shop?->name;
            }
        }

        return [
            'minimum_order_amount' => $minimumOrderAmount,
            'amount' => $totalAmount,
            'added_by' => $product->added_by,
            'status' => $status,
            'shop_name' => $shopName,
        ];
    }

    public function findFreeDeliveryCartItem(?string $cartGroupId): ?Model
    {
        return $this->query()
            ->whereHas('product', fn (Builder $builder) => $builder->active())
            ->where(['product_type' => self::PRODUCT_TYPE_PHYSICAL, 'cart_group_id' => $cartGroupId, 'is_checked' => 1])
            ->first();
    }

    public function getFreeDeliveryOrderAmount(?string $cartGroupId, ?Model $freeDeliveryCartItem, ?Model $seller): array
    {
        $freeDeliveryData = [
            'amount' => 0,
            'percentage' => 0,
            'amount_need' => 0,
            'shipping_cost_saved' => 0,
            'cart_id' => $cartGroupId,
        ];

        $freeDeliverySettings = $this->freeDeliverySettings();
        $freeDeliveryData['status'] = (int) (getWebConfig(name: $freeDeliverySettings['status']) ?? 0);
        $freeDeliveryData['responsibility'] = (string) getWebConfig(name: $freeDeliverySettings['responsibility']);
        $freeDeliveryOverAmount = (float) getWebConfig(name: $freeDeliverySettings['in_house_amount']);
        $freeDeliveryOverAmountSeller = (float) getWebConfig(name: $freeDeliverySettings['seller_amount']);

        if (! $freeDeliveryData['status'] || ! $cartGroupId) {
            return $freeDeliveryData;
        }

        if ($freeDeliveryCartItem === null) {
            $freeDeliveryData['status'] = 0;

            return $freeDeliveryData;
        }

        if ($freeDeliveryCartItem->seller_is == self::SELLER_IS_ADMIN) {
            $inHouseFreeDeliveryStatus = getWebConfig(name: $freeDeliverySettings['in_house_status']);
            $isInHouseFreeDeliveryActive = $inHouseFreeDeliveryStatus === null
                ? $freeDeliveryOverAmount > 0
                : (bool) $inHouseFreeDeliveryStatus;

            $freeDeliveryData['amount'] = $freeDeliveryOverAmount;
            $freeDeliveryData['status'] = $isInHouseFreeDeliveryActive && $freeDeliveryOverAmount > 0 ? 1 : 0;
        } else {
            $freeDeliveryData['status'] = $seller?->{$freeDeliverySettings['seller_status_column']} ?? 0;

            if ($freeDeliveryData['responsibility'] == self::SELLER_IS_ADMIN) {
                $freeDeliveryData['amount'] = $freeDeliveryOverAmountSeller;
                $freeDeliveryData['status'] = $freeDeliveryOverAmountSeller > 0 ? 1 : 0;
            }

            if ($freeDeliveryData['responsibility'] == 'seller' && $freeDeliveryData['status'] == 1) {
                $freeDeliveryData['amount'] = $seller->{$freeDeliverySettings['seller_amount_column']};
                $freeDeliveryData['status'] = $seller->{$freeDeliverySettings['seller_amount_column']} > 0 ? 1 : 0;
            }
        }

        $amount = $this->getSubtotalWithoutShipping(
            cartItems: $this->getCartItemList(cartGroupId: $freeDeliveryCartItem->cart_group_id, type: self::TYPE_CHECKED),
        );
        $freeDeliveryData['amount_need'] = $freeDeliveryData['amount'] - $amount;
        $freeDeliveryData['percentage'] = ($freeDeliveryData['amount'] > 0) && $amount > 0 && ($freeDeliveryData['amount'] >= $amount)
            ? number_format(($amount / $freeDeliveryData['amount']) * 100)
            : 100;

        if ($freeDeliveryData['status'] == 1 && $freeDeliveryData['percentage'] == 100) {
            $freeDeliveryData['shipping_cost_saved'] = $this->getShippingCost(
                cartGroupId: $freeDeliveryCartItem->cart_group_id,
                type: self::TYPE_CHECKED,
            );
        }

        return $freeDeliveryData;
    }

    public function addOrderItemsAgain(iterable $orderDetails, Collection $products, array $colorCodesByName, int $customerId): array
    {
        $orderProductCount = 0;
        $addToCartCount = 0;
        $updatedCartCount = 0;
        $failedAddToCartCount = 0;
        $errorReasons = [];

        foreach ($orderDetails as $orderDetail) {
            $orderProductCount++;
            $product = $products->get($orderDetail->product_id);

            if (! $product) {
                $errorReasons[] = ['reason' => self::REASON_PRODUCT_NOT_AVAILABLE, 'name' => json_decode($orderDetail->product_details, true)['name'] ?? ''];

                continue;
            }

            if (($product['product_type'] == self::PRODUCT_TYPE_PHYSICAL) && (($product['current_stock'] < $orderDetail['qty']) || ($product['minimum_order_qty'] > $product['current_stock']))) {
                $errorReasons[] = ['reason' => self::REASON_NOT_ENOUGH_STOCK, 'name' => $product['name'] ?? ''];
                $failedAddToCartCount++;

                continue;
            }

            $reason = $this->addOrderItemAgain(
                orderDetail: $orderDetail,
                product: $product,
                colorCodesByName: $colorCodesByName,
                customerId: $customerId,
                errorReasons: $errorReasons,
            );

            if ($reason === null) {
                $failedAddToCartCount++;

                continue;
            }

            $addToCartCount++;

            if ($reason === self::REASON_QUANTITY_UPDATED) {
                $updatedCartCount++;
            }
        }

        return [
            'order_product_count' => $orderProductCount,
            'add_to_cart_count' => $addToCartCount,
            'updated_cart_count' => $updatedCartCount,
            'failedAddToCartCount' => $failedAddToCartCount,
            'errorReasons' => $errorReasons,
        ];
    }

    private function addOrderItemAgain(Model $orderDetail, Model $product, array $colorCodesByName, int $customerId, array &$errorReasons): ?string
    {
        ['color' => $color, 'choices' => $choices] = $this->resolveReorderVariation(
            orderDetail: $orderDetail,
            colorCodesByName: $colorCodesByName,
        );

        $existingSellerCart = $this->query()->where([
            'customer_id' => $customerId,
            'seller_id' => $this->cartSellerId(product: $product),
            'seller_is' => $product->added_by,
        ])->first();

        $cartGroupId = $existingSellerCart !== null
            ? $existingSellerCart['cart_group_id']
            : $customerId.'-'.Str::random(5).'-'.time();

        $isValid = true;
        $price = 0;
        $variations = json_decode($product->retailDetail?->variation ?? '[]');

        if ($variations) {
            foreach ($variations as $variation) {
                if ($variation->type == $orderDetail->variant) {
                    $price = $variation->price;

                    if ($variation->qty < $orderDetail->qty) {
                        $isValid = false;
                        $errorReasons[] = ['reason' => self::REASON_VARIATION_NOT_ENOUGH_STOCK, 'name' => $product['name']];
                    }
                }
            }
        } else {
            $price = $product->unit_price;
        }

        if ($product->product_type == self::PRODUCT_TYPE_DIGITAL) {
            ['price' => $price, 'is_valid' => $isValid] = $this->resolveReorderDigitalPrice(
                product: $product,
                orderDetail: $orderDetail,
                price: $price,
                isValid: $isValid,
                errorReasons: $errorReasons,
            );
        }

        $tax = Helpers::tax_calculation(product: $product, price: $price, tax: $product['tax'], tax_type: 'percent');

        if (! $isValid || $price == 0) {
            return null;
        }

        $existingCart = $this->findCustomerCartLine(customerId: $customerId, productId: $orderDetail->product_id, variant: $orderDetail->variant);
        $quantity = $orderDetail->qty < $product['minimum_order_qty'] ? $product['minimum_order_qty'] : $orderDetail->qty;

        if ($existingCart !== null) {
            $existingCart->quantity = $quantity;
            $existingCart->save();

            return self::REASON_QUANTITY_UPDATED;
        }

        $cart = $this->model->newInstance();
        $cart->business_module = $this->businessModule();
        $cart['cart_group_id'] = $cartGroupId;
        $cart['color'] = $color;
        $cart['product_id'] = $orderDetail->product_id;
        $cart['product_type'] = $product->product_type;
        $cart['choices'] = json_encode($choices);
        $cart['variations'] = ! is_null($color) || ! empty($choices)
            ? (is_array($orderDetail?->variation) ? json_encode($orderDetail?->variation) : $orderDetail?->variation)
            : json_encode([]);
        $cart['variant'] = $orderDetail->variant;
        $cart['customer_id'] = $customerId;
        $cart['quantity'] = $quantity;
        $cart['price'] = $price;
        $cart['tax'] = $tax;
        $cart['tax_model'] = $product->tax_model;
        $cart['slug'] = $product->slug;
        $cart['name'] = $product->name;
        $cart['is_checked'] = 1;
        $cart['discount'] = Helpers::getProductDiscount($product, $price);
        $cart['thumbnail'] = $product->thumbnail;
        $cart['seller_id'] = $this->cartSellerId(product: $product);
        $cart['seller_is'] = $product->added_by;
        $cart['shipping_cost'] = $product->product_type == self::PRODUCT_TYPE_PHYSICAL
            ? $this->shippingTypeService->resolveProductShippingCost(product: $product, quantity: $quantity)
            : 0;
        $cart['shop_info'] = $this->shopName(product: $product);
        $cart['shipping_type'] = $this->shippingTypeService->resolveTypeForProduct(product: $product);
        $cart->save();

        return self::REASON_ADDED;
    }

    private function findCustomerCartLine(int $customerId, int $productId, ?string $variant): ?Model
    {
        return $this->query()
            ->where(['customer_id' => $customerId, 'is_guest' => 0, 'product_id' => $productId])
            ->when(
                filled($variant),
                fn (Builder $query): Builder => $query->where('variant', $variant),
                fn (Builder $query): Builder => $query->where(fn (Builder $blankVariant): Builder => $blankVariant->whereNull('variant')->orWhere('variant', '')),
            )
            ->first();
    }

    /** @return array{color: ?string, choices: array<string, mixed>} */
    private function resolveReorderVariation(Model $orderDetail, array $colorCodesByName): array
    {
        $color = null;
        $choices = [];

        if (! $orderDetail->variation) {
            return ['color' => $color, 'choices' => $choices];
        }

        $variation = is_array($orderDetail?->variation) ? $orderDetail?->variation : json_decode($orderDetail?->variation ?? '', true);
        $choiceIndex = 1;

        if (isset($variation['color']) && $variation['color']) {
            $color = $colorCodesByName[$variation['color']] ?? null;

            foreach ($variation as $variationKey => $value) {
                if ($variationKey != 'color') {
                    $choices['choice_'.$choiceIndex] = $value;
                    $choiceIndex++;
                }
            }

            return ['color' => $color, 'choices' => $choices];
        }

        foreach ($variation ?: [] as $value) {
            if ($value) {
                $choices['choice_'.$choiceIndex] = $value;
            }
            $choiceIndex++;
        }

        return ['color' => $color, 'choices' => $choices];
    }

    /** @return array{price: mixed, is_valid: bool} */
    private function resolveReorderDigitalPrice(Model $product, Model $orderDetail, mixed $price, bool $isValid, array &$errorReasons): array
    {
        $digitalProductType = $product->retailDetail?->digital_product_type;
        $digitalFileReady = $product->retailDetail?->digital_file_ready;

        if ($digitalProductType == self::DIGITAL_READY_AFTER_SELL) {
            return ['price' => $product->unit_price, 'is_valid' => $isValid];
        }

        if ($digitalProductType == self::DIGITAL_READY_PRODUCT && ! empty($digitalFileReady)) {
            return ['price' => $product->unit_price, 'is_valid' => $isValid];
        }

        if ($digitalProductType == self::DIGITAL_READY_PRODUCT && empty($digitalFileReady) && $product->digitalVariation) {
            $isValid = false;
            $errorReasons[] = ['reason' => self::REASON_DIGITAL_FILE_MISSING, 'name' => $product['name']];

            foreach ($product->digitalVariation as $digitalVariation) {
                if ($digitalVariation['variant_key'] == $orderDetail['variant']) {
                    $price = $digitalVariation['price'];
                    $isValid = true;
                }
            }
        }

        return ['price' => $price, 'is_valid' => $isValid];
    }

    private function addPhysicalProduct(
        Model $product,
        array $data,
        ?int $customerId,
        int|string|null $guestId,
        string $shippingType,
        ?Collection $sellerShippingList,
        ?string $colorName,
        ?Model $shippingMethod,
    ): array {
        $quantity = $data['quantity'] ?? null;

        if (($product['product_type'] == self::PRODUCT_TYPE_PHYSICAL) && ($product['current_stock'] < $quantity)) {
            return ['status' => self::STATUS_FAILED, 'reason' => self::REASON_OUT_OF_STOCK];
        }

        if ($product['minimum_order_qty'] > $quantity) {
            return $this->belowMinimumOrderQuantity(product: $product);
        }

        $selection = $this->resolveVariantSelection(product: $product, data: $data, colorName: $colorName);
        $variant = $selection['variant'];
        $price = 0;

        if ($variant != null) {
            foreach (json_decode($product->retailDetail?->variation ?? '[]') as $variation) {
                if ($variation->type == $variant) {
                    $price = $variation->price;

                    if ($variation->qty < $quantity) {
                        return ['status' => self::STATUS_FAILED, 'reason' => self::REASON_OUT_OF_STOCK];
                    }
                }
            }
        } else {
            $price = $product->unit_price;
        }

        $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $price);
        $ownerParams = $this->ownerParams(customerId: $customerId, guestId: $guestId);

        $cartRow = [
            'color' => $data['color'] ?? null,
            'product_id' => $product['id'],
            'product_type' => $product['product_type'],
            'choices' => json_encode($selection['choices']),
            'variations' => json_encode($selection['variations']),
            'variant' => $variant,
        ] + $this->buildCartRow(
            product: $product,
            data: $data,
            ownerParams: $ownerParams,
            price: $price,
            discount: $discount,
            shippingType: $shippingType,
        );

        $cartRow['cart_group_id'] = $this->resolveCartGroupId(product: $product, ownerParams: $ownerParams, customerId: $customerId);

        $cart = $this->query()->where([
            'product_id' => $data['id'] ?? null,
            'customer_id' => $ownerParams['customer_id'],
            'is_guest' => $ownerParams['is_guest'],
            'variant' => $variant,
        ])->first();

        if ($cart !== null) {
            $cartRow['cart_group_id'] = $cart['cart_group_id'];
            $this->query()->where('id', $cart['id'])->update($cartRow);
        } else {
            $cart = $this->insertCartRow(cartRow: $cartRow);
        }

        if (($data['buy_now'] ?? null) == 1) {
            $buyNowResult = $this->completePhysicalBuyNow(
                product: $product,
                data: $data,
                cart: $cart,
                ownerParams: $ownerParams,
                customerId: $customerId,
                guestId: $guestId,
                price: $price,
                discount: $discount,
                shippingType: $shippingType,
                sellerShippingList: $sellerShippingList,
                shippingMethod: $shippingMethod,
            );

            if ($buyNowResult !== null) {
                return $buyNowResult;
            }
        }

        if ($product->product_type == self::PRODUCT_TYPE_PHYSICAL) {
            $cart['free_delivery_order_amount'] = $this->getFreeDeliveryOrderAmountAfterWrite(
                cart: $cart,
                product: $product,
                data: $data,
                customerId: $customerId,
                guestId: $guestId,
            );
        }

        return [
            'status' => self::STATUS_SUCCESS,
            'in_cart_key' => $cart['id'],
            'cart' => $cart,
            'reason' => self::REASON_ADDED,
            'product_variant_type' => $this->productVariantType(product: $product),
        ];
    }

    private function completePhysicalBuyNow(
        Model $product,
        array $data,
        Model $cart,
        array $ownerParams,
        ?int $customerId,
        int|string|null $guestId,
        mixed $price,
        mixed $discount,
        string $shippingType,
        ?Collection $sellerShippingList,
        ?Model $shippingMethod,
    ): ?array {
        $minimumOrderAmountFailure = $this->checkBuyNowMinimumOrderAmount(
            product: $product,
            cart: $cart,
            ownerParams: $ownerParams,
            quantity: $data['quantity'] ?? null,
            price: $price,
            discount: $discount,
        );

        if ($minimumOrderAmountFailure !== null) {
            return $minimumOrderAmountFailure;
        }

        if ($product['product_type'] == self::PRODUCT_TYPE_DIGITAL) {
            return $this->addedForCheckout(cart: $cart);
        }

        if ($product['product_type'] == self::PRODUCT_TYPE_PHYSICAL && $shippingType == ShippingTypeService::TYPE_ORDER_WISE) {
            return $this->completeOrderWiseBuyNow(
                product: $product,
                data: $data,
                cart: $cart,
                ownerParams: $ownerParams,
                customerId: $customerId,
                guestId: $guestId,
                sellerShippingList: $sellerShippingList,
                shippingMethod: $shippingMethod,
            );
        }

        if ($product['product_type'] == self::PRODUCT_TYPE_PHYSICAL
            && ($shippingType == ShippingTypeService::TYPE_CATEGORY_WISE || $shippingType == ShippingTypeService::TYPE_PRODUCT_WISE)) {
            $cart->update([
                'is_checked' => 1,
                'shipping_cost' => $this->shippingTypeService->resolveProductShippingCost(product: $product, quantity: $data['quantity'] ?? null),
            ]);

            $cart['free_delivery_order_amount'] = $this->getFreeDeliveryOrderAmountAfterWrite(
                cart: $cart,
                product: $product,
                data: $data,
                customerId: $customerId,
                guestId: $guestId,
            );

            return $this->addedForCheckout(cart: $cart) + ['cart_shipping_cost' => 0];
        }

        $cart->update(['is_checked' => 1]);

        return null;
    }

    private function completeOrderWiseBuyNow(
        Model $product,
        array $data,
        Model $cart,
        array $ownerParams,
        ?int $customerId,
        int|string|null $guestId,
        ?Collection $sellerShippingList,
        ?Model $shippingMethod,
    ): array {
        $hasShippingMethods = $sellerShippingList && count($sellerShippingList) > 0;

        if (! (($data['shipping_method_exist'] ?? null) && ($data['shipping_method_id'] ?? null) && count($sellerShippingList ?? []) > 0)) {
            return [
                'status' => $hasShippingMethods ? self::STATUS_SHIPPING_METHOD_REQUIRED : self::STATUS_FAILED,
                'reason' => $hasShippingMethods ? self::REASON_SHIPPING_METHOD_REQUIRED : self::REASON_SHIPPING_UNAVAILABLE,
                'shipping_method_list' => $sellerShippingList,
            ];
        }

        $cart->update(['is_checked' => 1]);

        $ownerCartGroupIds = $this->getOwnerCartGroupIds(ownerId: $ownerParams['customer_id'], isGuest: $ownerParams['is_guest']);
        if (count($ownerCartGroupIds) > 0) {
            $this->cartShippingService->deleteByCartGroupIds(cartGroupIds: $ownerCartGroupIds);
        }

        if ($shippingMethod === null) {
            return ['status' => self::STATUS_FAILED, 'reason' => self::REASON_SHIPPING_METHOD_NOT_FOUND];
        }

        $this->cartShippingService->saveForCartGroup(
            cartGroupId: $cart['cart_group_id'],
            shippingMethodId: $data['shipping_method_id'],
            shippingCost: $shippingMethod->cost ?? 0,
        );

        $cart['free_delivery_order_amount'] = $this->getFreeDeliveryOrderAmountAfterWrite(
            cart: $cart,
            product: $product,
            data: $data,
            customerId: $customerId,
            guestId: $guestId,
        );

        return $this->addedForCheckout(cart: $cart) + ['cart_shipping_cost' => $shippingMethod->cost ?? 0];
    }

    private function addDigitalProduct(
        Model $product,
        array $data,
        ?int $customerId,
        int|string|null $guestId,
        string $shippingType,
        ?Model $digitalVariation,
    ): array {
        $quantity = $data['quantity'] ?? null;

        if ($product['minimum_order_qty'] > $quantity) {
            return $this->belowMinimumOrderQuantity(product: $product);
        }

        $price = $product->unit_price;
        if (($data['variant_key'] ?? null) && $digitalVariation) {
            $price = $digitalVariation['price'];
        }

        $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $price);
        $ownerParams = $this->ownerParams(customerId: $customerId, guestId: $guestId);

        $cartRow = [
            'customer_id' => $ownerParams['customer_id'],
            'product_id' => $data['id'] ?? null,
            'product_type' => $product['product_type'],
            'digital_product_type' => $product->retailDetail?->digital_product_type,
            'choices' => json_encode([]),
            'variations' => json_encode([]),
            'variant' => $data['variant_key'] ?? null,
        ] + $this->buildCartRow(
            product: $product,
            data: $data,
            ownerParams: $ownerParams,
            price: $price,
            discount: $discount,
            shippingType: $shippingType,
        );

        $cartRow['cart_group_id'] = $this->resolveCartGroupId(product: $product, ownerParams: $ownerParams, customerId: $customerId);

        $cart = $this->query()->where([
            'product_id' => $data['id'] ?? null,
            'customer_id' => $ownerParams['customer_id'],
            'is_guest' => $ownerParams['is_guest'],
            'variant' => $data['variant_key'] ?? null,
        ])->first();

        if ($cart !== null) {
            $this->query()->where('id', $cart['id'])->update($cartRow);
        } else {
            $cart = $this->insertCartRow(cartRow: $cartRow);
        }

        if (($data['buy_now'] ?? null) == 1) {
            $minimumOrderAmountFailure = $this->checkBuyNowMinimumOrderAmount(
                product: $product,
                cart: $cart,
                ownerParams: $ownerParams,
                quantity: $quantity,
                price: $price,
                discount: $discount,
            );

            if ($minimumOrderAmountFailure !== null) {
                return $minimumOrderAmountFailure;
            }

            if ($product['product_type'] == self::PRODUCT_TYPE_DIGITAL) {
                return $this->addedForCheckout(cart: $cart);
            }
        }

        return [
            'status' => self::STATUS_SUCCESS,
            'in_cart_key' => $cart['id'],
            'cart' => $cart,
            'reason' => self::REASON_ADDED,
            'product_variant_type' => $this->productVariantType(product: $product),
        ];
    }

    /** @return array{variant: string, choices: array<string, mixed>, variations: array<string, mixed>} */
    private function resolveVariantSelection(Model $product, array $data, ?string $colorName): array
    {
        $variant = '';
        $variations = [];
        $choices = [];

        if (array_key_exists('color', $data)) {
            $variant .= $colorName;
            $variations['color'] = $variant;
        }

        foreach (json_decode($product->choice_options) as $choice) {
            $choiceValue = $data[$choice->name] ?? null;
            $choices[$choice->name] = $choiceValue;
            $variations[$choice->title] = $choiceValue;
            $variant .= ($variant != null ? '-' : '').str_replace(' ', '', (string) $choiceValue);
        }

        if (! ($data['buy_now'] ?? null)
            && ($data['shipping_method_exist'] ?? null)
            && array_key_exists('product_variation_code', $data)
            && $data['product_variation_code']) {
            $variant = str_replace(' ', '', $data['product_variation_code']);
        }

        return ['variant' => $variant, 'choices' => $choices, 'variations' => $variations];
    }

    private function buildCartRow(Model $product, array $data, array $ownerParams, mixed $price, mixed $discount, string $shippingType): array
    {
        return [
            'customer_id' => $ownerParams['customer_id'],
            'product_id' => $data['id'] ?? null,
            'product_type' => $product['product_type'],
            'quantity' => $data['quantity'] ?? null,
            'price' => $price,
            'discount' => $discount,
            'is_checked' => 1,
            'slug' => $product['slug'],
            'name' => $product['name'],
            'thumbnail' => $product['thumbnail'],
            'seller_id' => $this->cartSellerId(product: $product),
            'seller_is' => $product['added_by'],
            'created_at' => now(),
            'updated_at' => now(),
            'shop_info' => $this->shopName(product: $product),
            'shipping_cost' => $product['product_type'] == self::PRODUCT_TYPE_PHYSICAL
                ? $this->shippingTypeService->resolveProductShippingCost(product: $product, quantity: $data['quantity'] ?? null)
                : 0,
            'shipping_type' => $shippingType,
            'is_guest' => $ownerParams['is_guest'],
        ];
    }

    private function resolveCartGroupId(Model $product, array $ownerParams, ?int $customerId): string
    {
        $existingSellerCart = $this->query()->where([
            'customer_id' => $ownerParams['customer_id'],
            'is_guest' => $ownerParams['is_guest'],
            'seller_id' => $this->cartSellerId(product: $product),
            'seller_is' => $product->added_by,
        ])->first();

        if ($existingSellerCart !== null) {
            return $existingSellerCart['cart_group_id'];
        }

        return ($customerId === null ? self::GUEST_CART_GROUP_PREFIX : $customerId).'-'.Str::random(5).'-'.time();
    }

    private function insertCartRow(array $cartRow): ?Model
    {
        $cartId = $this->query()->insertGetId([...$cartRow, 'business_module' => $this->businessModule()]);

        return $this->query()->where('id', $cartId)->first();
    }

    private function checkBuyNowMinimumOrderAmount(
        Model $product,
        Model $cart,
        array $ownerParams,
        mixed $quantity,
        mixed $price,
        mixed $discount,
    ): ?array {
        $verification = $this->verifySingleProductMinimumOrderAmount(
            product: $product,
            totalAmount: ($price * $quantity) - ($discount * $quantity),
        );

        if ($verification['status'] == self::STATUS_FAILED) {
            return [
                'status' => self::STATUS_FAILED,
                'reason' => self::REASON_BELOW_MINIMUM_ORDER_AMOUNT,
                'minimum_order_amount' => $verification['minimum_order_amount'],
                'shop_name' => $verification['shop_name'],
            ];
        }

        $this->query()->where($ownerParams)->update(['is_checked' => 0]);
        $this->query()->where('id', $cart['id'])->update(['is_checked' => 1]);

        return null;
    }

    private function getFreeDeliveryOrderAmountAfterWrite(Model $cart, Model $product, array $data, ?int $customerId, int|string|null $guestId): array
    {
        $freeDeliveryCartItem = $this->findFreeDeliveryCartItem(cartGroupId: $cart['cart_group_id']);

        if ($this->isFreeDeliveryEnabled() && $cart['cart_group_id'] && $freeDeliveryCartItem !== null) {
            $this->refreshPrices(customerId: $customerId, guestId: $guestId);
            $this->refreshShippingCosts(
                customerId: $customerId,
                guestId: $customerId === null ? ($data['guest_id'] ?? null) : $guestId,
                cartGroupId: $cart['cart_group_id'],
                type: self::TYPE_CHECKED,
            );
        }

        return $this->getFreeDeliveryOrderAmount(
            cartGroupId: $cart['cart_group_id'],
            freeDeliveryCartItem: $freeDeliveryCartItem,
            seller: $product->seller,
        );
    }

    public function isFreeDeliveryEnabled(): bool
    {
        return (bool) (int) (getWebConfig(name: $this->freeDeliverySettings()['status']) ?? 0);
    }

    private function addedForCheckout(Model $cart): array
    {
        return [
            'status' => self::STATUS_SUCCESS,
            'redirect_to' => self::REDIRECT_TO_CHECKOUT,
            'cart' => $cart,
            'reason' => self::REASON_ADDED_FOR_CHECKOUT,
        ];
    }

    private function belowMinimumOrderQuantity(Model $product): array
    {
        return [
            'status' => self::STATUS_FAILED,
            'reason' => self::REASON_BELOW_MINIMUM_ORDER_QUANTITY,
            'minimum_order_qty' => $product['minimum_order_qty'],
        ];
    }

    private function productVariantType(Model $product): string
    {
        return count(json_decode($product->retailDetail?->variation ?? '[]', true)) > 0
            ? self::VARIANT_TYPE_MULTI
            : self::VARIANT_TYPE_SINGLE;
    }

    private function cartSellerId(Model $product): mixed
    {
        return $product->added_by == self::SELLER_IS_ADMIN ? self::IN_HOUSE_CART_SELLER_ID : $product->user_id;
    }

    private function shopName(Model $product): ?string
    {
        return $product->added_by == self::SELLER_IS_ADMIN
            ? getInHouseShopConfig(key: 'name')
            : $product->seller?->shop?->name;
    }

    /** @return array{customer_id: int|string|null, is_guest: int} */
    private function ownerParams(?int $customerId, int|string|null $guestId): array
    {
        return $customerId === null
            ? ['customer_id' => $guestId, 'is_guest' => self::GUEST]
            : ['customer_id' => $customerId, 'is_guest' => self::CUSTOMER];
    }

    private function refreshItemTax(Model $cartItem, ?Model $product): void
    {
        if (empty($cartItem->variant)) {
            $productTax = Helpers::tax_calculation(product: $product, price: $product->unit_price, tax: $product['tax'], tax_type: 'percent');
            if ($cartItem->tax != $productTax) {
                $this->query()->where('id', $cartItem['id'])->update(['tax' => $productTax]);
            }

            return;
        }

        if ($cartItem->product_type == self::PRODUCT_TYPE_PHYSICAL) {
            foreach (json_decode($product?->retailDetail?->variation ?? '', true) ?: [] as $variation) {
                if (! isset($variation['price'])) {
                    continue;
                }

                $productTax = Helpers::tax_calculation(product: $product, price: $variation['price'], tax: $product['tax'], tax_type: 'percent');
                if (isset($variation['type']) && $variation['type'] == $cartItem->variant && $productTax != $cartItem['tax']) {
                    $this->query()->where('id', $cartItem['id'])->update(['tax' => $productTax]);
                }
            }

            return;
        }

        if ($cartItem->product_type == self::PRODUCT_TYPE_DIGITAL && $product?->digitalVariation && ! empty($product?->digitalVariation)) {
            foreach ($product->digitalVariation as $variation) {
                $productTax = Helpers::tax_calculation(product: $product, price: $product->unit_price, tax: $product['tax'], tax_type: 'percent');
                if ($cartItem->variant == $variation['variant_key'] && $variation['tax'] != $cartItem['tax']) {
                    $this->query()->where('id', $cartItem['id'])->update(['tax' => $productTax]);
                }
            }
        }
    }

    private function refreshItemPrice(Model $cartItem, ?Model $product): void
    {
        if (empty($cartItem->variant)) {
            if ($cartItem->price != $product->unit_price) {
                $this->query()->where('id', $cartItem['id'])->update(['price' => $product->unit_price]);
            }

            return;
        }

        if ($cartItem->product_type == self::PRODUCT_TYPE_PHYSICAL) {
            foreach (json_decode($product?->retailDetail?->variation ?? '', true) ?: [] as $variation) {
                if (isset($variation['type']) && isset($variation['price']) && $variation['type'] == $cartItem->variant && $variation['price'] != $cartItem['price']) {
                    $this->query()->where('id', $cartItem['id'])->update(['price' => $variation['price']]);
                }
            }

            return;
        }

        if ($cartItem->product_type == self::PRODUCT_TYPE_DIGITAL && $product?->digitalVariation && ! empty($product?->digitalVariation)) {
            foreach ($product->digitalVariation as $variation) {
                if ($cartItem->variant == $variation['variant_key'] && $variation['price'] != $cartItem['price']) {
                    $this->query()->where('id', $cartItem['id'])->update(['price' => $variation['price']]);
                }
            }
        }
    }

    public function getRefreshedCartItemList(
        ?int $customerId,
        int|string|null $sessionGuestId,
        int|string|null $requestGuestId,
        ?string $cartGroupId = null,
        ?string $type = null,
    ): Collection {
        $this->refreshPrices(customerId: $customerId, guestId: $sessionGuestId ?? 0);
        $this->refreshShippingCosts(customerId: $customerId, guestId: $requestGuestId, cartGroupId: $cartGroupId, type: $type);

        return $this->getCartItemList(
            customerId: $customerId,
            guestId: $this->ownerGuestId(sessionGuestId: $sessionGuestId, requestGuestId: $requestGuestId),
            cartGroupId: $cartGroupId,
            type: $type,
        );
    }

    public function getDiscountedSubtotal(iterable $cartItems): float|int
    {
        $total = 0;

        foreach ($cartItems as $item) {
            $total += ($item['price'] - $this->resolveItemDiscount(cartItem: $item)) * $item['quantity'];
        }

        return $total;
    }

    public function getCartGrandTotal(
        ?int $customerId,
        int|string|null $sessionGuestId,
        int|string|null $requestGuestId,
        ?string $cartGroupId = null,
        ?string $type = null,
    ): float|int {
        $type = $type === self::TYPE_CHECKED ? self::TYPE_CHECKED : null;

        $cartItems = $this->getRefreshedCartItemList(
            customerId: $customerId,
            sessionGuestId: $sessionGuestId,
            requestGuestId: $requestGuestId,
            cartGroupId: $cartGroupId,
            type: $type,
        );

        $shippingCost = $this->getShippingCost(
            customerId: $customerId,
            guestId: $this->ownerGuestId(sessionGuestId: $sessionGuestId, requestGuestId: $requestGuestId),
            cartGroupId: $cartGroupId,
            type: $type,
        );

        return $this->getDiscountedSubtotal(cartItems: $cartItems) + $shippingCost;
    }

    public function getOnlyProductPriceGrandTotal(
        ?int $customerId,
        int|string|null $sessionGuestId,
        int|string|null $requestGuestId,
        ?string $cartGroupId = null,
        ?string $type = null,
    ): float|int {
        return $this->getDiscountedSubtotal(cartItems: $this->getRefreshedCartItemList(
            customerId: $customerId,
            sessionGuestId: $sessionGuestId,
            requestGuestId: $requestGuestId,
            cartGroupId: $cartGroupId,
            type: $type === self::TYPE_CHECKED ? self::TYPE_CHECKED : null,
        ));
    }

    /**
     * @param  array<int, string>  $cartGroupIds
     * @return array<string, float|int|string>
     */
    public function getGroupShippingCosts(
        ?int $customerId,
        int|string|null $requestGuestId,
        array $cartGroupIds,
    ): array {
        $shippingCosts = [];

        foreach ($cartGroupIds as $cartGroupId) {
            $shippingCosts[$cartGroupId] = $this->getRefreshedGroupShippingCost(
                customerId: $customerId,
                requestGuestId: $requestGuestId,
                cartGroupId: $cartGroupId,
            );
        }

        return $shippingCosts;
    }

    public function getGroupFreeDeliveryOrderAmount(
        ?int $customerId,
        int|string|null $sessionGuestId,
        int|string|null $requestGuestId,
        ?string $cartGroupId,
    ): array {
        $freeDeliveryCartItem = $this->findFreeDeliveryCartItem(cartGroupId: $cartGroupId);

        if ($this->isFreeDeliveryEnabled() && $cartGroupId && $freeDeliveryCartItem !== null) {
            $this->refreshPrices(customerId: $customerId, guestId: $sessionGuestId ?? 0);
            $this->refreshShippingCosts(customerId: $customerId, guestId: $requestGuestId, cartGroupId: $cartGroupId, type: self::TYPE_CHECKED);
        }

        return $this->buildGroupFreeDeliveryOrderAmount(cartGroupId: $cartGroupId, freeDeliveryCartItem: $freeDeliveryCartItem);
    }

    public function getShippingCostSavedForFreeDelivery(
        ?int $customerId,
        int|string|null $sessionGuestId,
        int|string|null $requestGuestId,
        ?string $cartGroupId = null,
        ?string $type = null,
    ): float|int|string {
        $isCheckedOnly = $type === self::TYPE_CHECKED;

        $cartGroupIds = $this->query()
            ->where('product_type', self::PRODUCT_TYPE_PHYSICAL)
            ->whereHas('product', fn (Builder $builder) => $builder->active())
            ->when($cartGroupId != null, fn (Builder $builder) => $builder->where('cart_group_id', $cartGroupId))
            ->when($cartGroupId == null, fn (Builder $builder) => $builder->whereIn('cart_group_id', $this->getCartGroupIds(
                customerId: $customerId,
                guestId: $this->ownerGuestId(sessionGuestId: $sessionGuestId, requestGuestId: $requestGuestId),
                type: $isCheckedOnly ? self::TYPE_CHECKED : null,
            )))
            ->when($isCheckedOnly, fn (Builder $builder) => $builder->where('is_checked', 1))
            ->pluck('cart_group_id')
            ->unique()
            ->values();

        $costSaved = 0;

        foreach ($cartGroupIds as $groupId) {
            $costSaved += $this->getGroupFreeDeliveryOrderAmount(
                customerId: $customerId,
                sessionGuestId: $sessionGuestId,
                requestGuestId: $requestGuestId,
                cartGroupId: $groupId,
            )['shipping_cost_saved'];
        }

        return $costSaved;
    }

    /**
     * @return array{minimum_order_amount: mixed, amount: float|int, status: int, reasons: array<int, array{reason: string, minimum_order_amount: mixed, shop_name: ?string}>, shippingCost: mixed, cart_group_id: ?string}
     */
    public function verifyCartListMinimumOrderAmount(
        ?int $customerId,
        int|string|null $sessionGuestId,
        int|string|null $requestGuestId,
        ?string $cartGroupId = null,
    ): array {
        $status = self::STATUS_SUCCESS;
        $amount = 0;
        $shippingCost = 0;
        $reasons = [];
        $minimumOrderAmount = 0;
        $minimumOrderAmountSettings = $this->minimumOrderAmountSettings();
        $isMinimumOrderAmountBySeller = getWebConfig(name: $minimumOrderAmountSettings['by_seller']);
        $inHouseMinimumOrderAmount = getWebConfig(name: $minimumOrderAmountSettings['in_house_amount']);

        if (getWebConfig(name: $minimumOrderAmountSettings['status'])) {
            $query = $this->query()
                ->whereHas('product', fn (Builder $builder) => $builder->active())
                ->with(['seller.shop', 'allProducts' => fn ($product) => $product->active()])
                ->where([
                    'customer_id' => $customerId ?? ($sessionGuestId ?? $requestGuestId),
                    'is_guest' => $customerId === null ? self::GUEST : '0',
                    'is_checked' => 1,
                ]);

            $groups = [];

            if ($cartGroupId) {
                $cartItem = $query->where('cart_group_id', $cartGroupId)->first();

                if ($cartItem !== null && $cartItem->allProducts) {
                    $groups[$cartGroupId] = [
                        'is_in_house' => $cartItem->allProducts->added_by == self::SELLER_IS_ADMIN,
                        'cart_item' => $cartItem,
                    ];
                }
            } else {
                foreach ($query->get()->groupBy('cart_group_id') as $groupId => $groupCartItems) {
                    $groups[$groupId] = [
                        'is_in_house' => $groupCartItems->first()->seller_is == self::SELLER_IS_ADMIN,
                        'cart_item' => $groupCartItems->first(),
                    ];
                }
            }

            foreach ($groups as $groupId => $group) {
                $minimumOrderAmount = $group['is_in_house']
                    ? $inHouseMinimumOrderAmount
                    : ($isMinimumOrderAmountBySeller ? ($group['cart_item']?->seller?->{$this->sellerMinimumOrderAmountColumn()} ?? 0) : 0);

                $shippingCost = $this->getRefreshedGroupShippingCost(
                    customerId: $customerId,
                    requestGuestId: $requestGuestId,
                    cartGroupId: (string) $groupId,
                );
                $newAmount = $this->getCartGrandTotal(
                    customerId: $customerId,
                    sessionGuestId: $sessionGuestId,
                    requestGuestId: $requestGuestId,
                    cartGroupId: (string) $groupId,
                    type: self::TYPE_CHECKED,
                ) - $shippingCost;

                if ($minimumOrderAmount > $newAmount) {
                    $status = self::STATUS_FAILED;
                    $reasons[] = [
                        'reason' => self::REASON_BELOW_MINIMUM_ORDER_AMOUNT,
                        'minimum_order_amount' => $minimumOrderAmount,
                        'shop_name' => $group['is_in_house'] ? getInHouseShopConfig(key: 'name') : $group['cart_item']?->seller?->shop?->name,
                    ];
                }

                $amount = $amount + $newAmount;
            }
        }

        return [
            'minimum_order_amount' => $minimumOrderAmount ?? 0,
            'amount' => $amount ? floatval($amount) : 0,
            'status' => $status,
            'reasons' => $reasons,
            'shippingCost' => $shippingCost ?? 0,
            'cart_group_id' => $cartGroupId ?? null,
        ];
    }

    /**
     * @return array{status: int, redirect?: string, reason?: string, product_name?: string, minimum_order_qty?: mixed}
     */
    public function validateStockAvailability(
        ?int $customerId,
        int|string|null $sessionGuestId,
        int|string|null $requestGuestId,
    ): array {
        $cartItems = $this->query()
            ->whereHas('product', fn (Builder $builder) => $builder->active())
            ->with(['allProducts' => fn ($product) => $product->active()])
            ->where([
                'customer_id' => $customerId ?? ($sessionGuestId ?? $requestGuestId),
                'is_guest' => $customerId === null ? self::GUEST : '0',
                'is_checked' => 1,
            ])
            ->get();

        foreach ($cartItems as $cartItem) {
            if ($cartItem->allProducts && $cartItem->allProducts->minimum_order_qty > $cartItem->quantity) {
                return [
                    'status' => self::STATUS_FAILED,
                    'redirect' => self::REDIRECT_SHOP_CART,
                    'reason' => self::REASON_BELOW_MINIMUM_ORDER_QUANTITY,
                    'product_name' => $cartItem->allProducts->name,
                    'minimum_order_qty' => $cartItem->allProducts->minimum_order_qty,
                ];
            }
        }

        return ['status' => self::STATUS_SUCCESS];
    }

    /**
     * @param  array{admin: int, seller: array<int, int>}  $activeShippingMethodCounts
     * @return array{status: int, physical_product_view: bool, errorType?: string, redirect?: string, blockers: array<int, array<string, mixed>>}
     */
    public function getCheckoutBlockers(
        ?int $customerId,
        int|string|null $sessionGuestId,
        int|string|null $requestGuestId,
        array $activeShippingMethodCounts,
    ): array {
        $response = ['status' => self::STATUS_SUCCESS, 'physical_product_view' => false];
        $blockers = [];

        $minimumOrderAmount = $this->verifyCartListMinimumOrderAmount(
            customerId: $customerId,
            sessionGuestId: $sessionGuestId,
            requestGuestId: $requestGuestId,
        );

        if ($minimumOrderAmount['status'] == self::STATUS_FAILED) {
            $response['status'] = self::STATUS_FAILED;
            $response['errorType'] = self::ERROR_TYPE_MINIMUM_ORDER_AMOUNT;
            $response['redirect'] = self::REDIRECT_SHOP_CART;
            array_push($blockers, ...$minimumOrderAmount['reasons']);
        }

        $ownerGuestId = $this->ownerGuestId(sessionGuestId: $sessionGuestId, requestGuestId: $requestGuestId);
        $cartGroupIds = $this->getCartGroupIds(customerId: $customerId, guestId: $ownerGuestId);
        $checkedCartGroupIds = $this->getCartGroupIds(customerId: $customerId, guestId: $ownerGuestId, type: self::TYPE_CHECKED);
        $shippingMethod = getWebConfig(name: $this->shippingResponsibilitySetting());

        if (count($cartGroupIds) <= 0) {
            $this->addCheckoutBlocker($response, $blockers, ['reason' => self::REASON_CART_EMPTY], self::REDIRECT_HOME, self::ERROR_TYPE_EMPTY_CART);
        } elseif (count($checkedCartGroupIds) <= 0) {
            $this->addCheckoutBlocker($response, $blockers, ['reason' => self::REASON_NO_CHECKED_ITEMS], self::REDIRECT_SHOP_CART, self::ERROR_TYPE_EMPTY_SHIPPING);
        }

        $hasUnavailableVendor = false;
        $isStockAvailable = true;
        $isInHouseShippingMethodChecked = false;

        foreach ($checkedCartGroupIds as $checkedCartGroupId) {
            $hasPhysicalProduct = $this->query()
                ->whereHas('product', fn (Builder $builder) => $builder->active())
                ->where(['cart_group_id' => $checkedCartGroupId, 'is_checked' => 1])
                ->pluck('product_type')
                ->contains(fn (mixed $productType): bool => $productType == self::PRODUCT_TYPE_PHYSICAL);

            if ($hasPhysicalProduct) {
                $response['physical_product_view'] = true;
            }

            $groupCartItems = $this->query()
                ->whereHas('product', fn (Builder $builder) => $builder->active())
                ->with(['product' => fn ($product) => $product->active()])
                ->groupBy('cart_group_id')
                ->where(['cart_group_id' => $checkedCartGroupId, 'is_checked' => 1])
                ->get();

            if (! $this->hasEnoughStock(cartItems: $groupCartItems)) {
                $isStockAvailable = false;
            }

            $firstCartItem = $groupCartItems->get(0);

            if ($firstCartItem !== null && $this->isCartVendorUnavailable(cartItem: $firstCartItem)) {
                $hasUnavailableVendor = true;
            }

            if (! $hasPhysicalProduct) {
                continue;
            }

            foreach ($groupCartItems as $cartItem) {
                $this->addShippingBlockers(
                    $response,
                    $blockers,
                    $isInHouseShippingMethodChecked,
                    $cartItem,
                    $shippingMethod,
                    $activeShippingMethodCounts,
                );
            }
        }

        if ($hasUnavailableVendor) {
            $this->addCheckoutBlocker($response, $blockers, ['reason' => self::REASON_UNAVAILABLE_VENDORS], self::REDIRECT_SHOP_CART);
        }

        if (! $isStockAvailable) {
            $this->addCheckoutBlocker($response, $blockers, ['reason' => self::REASON_UNAVAILABLE_PRODUCT], self::REDIRECT_SHOP_CART);
        }

        $response['blockers'] = $blockers;

        return $response;
    }

    /**
     * @param  array<string, array{discount: float|int}>  $groupCouponDiscounts
     * @return array{total_amount: float|int, group_amounts: array<string, float|int>}
     */
    public function getReferralDiscountBases(iterable $cartItems, float|int $totalCouponDiscount, array $groupCouponDiscounts): array
    {
        $cartItems = collect($cartItems);
        $groupAmounts = [];

        foreach ($cartItems->groupBy('cart_group_id') as $cartGroupId => $groupCartItems) {
            $groupAmounts[$cartGroupId] = $this->getDiscountedSubtotal(cartItems: $groupCartItems)
                - ($groupCouponDiscounts[$cartGroupId]['discount'] ?? 0);
        }

        return [
            'total_amount' => $this->getDiscountedSubtotal(cartItems: $cartItems) - $totalCouponDiscount,
            'group_amounts' => $groupAmounts,
        ];
    }

    /**
     * @param  array<string, array{discount: float|int, coupon_bearer: string, coupon_code: string|int, coupon_type: ?string}>  $groupCouponDiscounts
     * @param  array<string, float|int>  $groupReferralDiscounts
     * @return array<string, array<string, mixed>>
     */
    public function getVendorWiseCartList(
        ?int $customerId,
        int|string|null $sessionGuestId,
        int|string|null $requestGuestId,
        array $groupCouponDiscounts,
        array $groupReferralDiscounts,
        mixed $shippingAddressId,
        mixed $billingAddressId,
    ): array {
        $cartItemsByGroup = $this->getRefreshedCartItemList(
            customerId: $customerId,
            sessionGuestId: $sessionGuestId,
            requestGuestId: $requestGuestId,
            type: self::TYPE_CHECKED,
        )->groupBy('cart_group_id');

        $shippingMethod = getWebConfig(name: $this->shippingResponsibilitySetting());
        $adminShippingType = $this->shippingTypeService->findBy(params: ['seller_id' => self::ADMIN_SHIPPING_SELLER_ID]);
        $orderShippingAddressId = $shippingAddressId === null ? null : (int) $shippingAddressId;
        $orderBillingAddressId = $this->isBillingInputByCustomer() && $billingAddressId !== null
            ? (int) $billingAddressId
            : null;

        $vendorWiseCartList = [];

        foreach ($cartItemsByGroup as $cartGroupId => $groupCartItems) {
            $couponInfo = $groupCouponDiscounts[$cartGroupId] ?? self::EMPTY_GROUP_COUPON_DISCOUNT;
            $shippingCost = $this->getShippingCost(cartGroupId: (string) $cartGroupId, type: self::TYPE_CHECKED);
            $freeDelivery = $this->buildGroupFreeDeliveryOrderAmount(
                cartGroupId: (string) $cartGroupId,
                freeDeliveryCartItem: $this->findFreeDeliveryCartItem(cartGroupId: (string) $cartGroupId),
            );

            $isShippingFree = 0;
            $freeShippingDiscount = 0;
            $freeDeliveryBearer = null;
            $extraDiscountType = null;

            if ($freeDelivery['status'] && $freeDelivery['shipping_cost_saved'] > 0 && $couponInfo['coupon_type'] != self::COUPON_TYPE_FREE_DELIVERY) {
                $isShippingFree = 1;
                $extraDiscountType = self::EXTRA_DISCOUNT_TYPE_FREE_SHIPPING;
                $freeShippingDiscount = $shippingCost;
                $freeDeliveryBearer = $freeDelivery['responsibility'];
            }

            $firstCartItem = $groupCartItems->first();
            $cartGroupGrandTotal = $this->getDiscountedSubtotal(cartItems: $groupCartItems) + $shippingCost;

            $shippingType = $shippingMethod == self::SHIPPING_METHOD_INHOUSE || $firstCartItem['seller_is'] == self::SELLER_IS_ADMIN
                ? $adminShippingType
                : $this->shippingTypeService->findBy(params: ['seller_id' => $firstCartItem['seller_id']]);

            $cartShipping = $this->cartShippingService->findBy(params: ['cart_group_id' => $cartGroupId]);

            $vendorWiseCartList[$cartGroupId] = [
                'seller_id' => $firstCartItem['seller_id'],
                'seller_is' => $firstCartItem['seller_is'],
                'grand_total' => $cartGroupGrandTotal,
                'order_amount' => $cartGroupGrandTotal - ($couponInfo['discount'] ?? 0) - $freeShippingDiscount,
                'coupon_code' => $couponInfo['coupon_code'] ?? 0,
                'coupon_bearer' => $couponInfo['coupon_bearer'] ?? self::COUPON_BEARER_INHOUSE,
                'coupon_type' => $couponInfo['coupon_type'] ?? null,
                'coupon_discount' => $couponInfo['discount'] ?? 0,
                'discount_type' => $couponInfo['discount'] == 0 ? null : self::DISCOUNT_TYPE_COUPON,
                'shipping_cost' => $shippingCost,
                'shipping_address_id' => $orderShippingAddressId,
                'billing_address_id' => $orderBillingAddressId,
                'shipping_type' => isset($shippingType?->shipping_type) ? $shippingType->shipping_type : ShippingTypeService::TYPE_ORDER_WISE,
                'is_shipping_free' => $isShippingFree,
                'shipping_method_id' => $cartShipping !== null ? $cartShipping->shipping_method_id : 0,
                'free_delivery_discount' => $freeShippingDiscount,
                'extra_discount_type' => $extraDiscountType,
                'free_delivery_bearer' => $firstCartItem['seller_is'] == self::SELLER_IS_SELLER ? $freeDeliveryBearer : self::FREE_DELIVERY_BEARER_ADMIN,
                'refer_and_earn_discount' => $groupReferralDiscounts[$cartGroupId] ?? 0,
                'cart_list' => $groupCartItems,
                'cart_group_id' => $cartGroupId,
            ];
        }

        return $vendorWiseCartList;
    }

    /** @param  iterable<int|string, array<string, mixed>>  $vendorWiseCartList */
    public function getPayableAmount(iterable $vendorWiseCartList): float
    {
        $vendorWiseCartList = collect($vendorWiseCartList);

        return (float) $vendorWiseCartList->sum('order_amount_with_tax') - (float) $vendorWiseCartList->sum('refer_and_earn_discount');
    }

    /**
     * @param  array<string, array{discount: float|int, coupon_bearer: string, coupon_code: string|int, coupon_type: ?string}>  $groupCouponDiscounts
     * @param  array<string, float|int>  $groupReferralDiscounts
     * @return array<int, array<string, mixed>>
     */
    public function getOrderGenerateCartList(
        ?int $customerId,
        int|string|null $sessionGuestId,
        int|string|null $requestGuestId,
        array $groupCouponDiscounts,
        array $groupReferralDiscounts,
        mixed $shippingAddressId,
        mixed $billingAddressId,
    ): array {
        $taxConfig = self::getTaxSystemType(businessModule: $this->businessModule());

        $vendorWiseCartList = $this->getVendorWiseCartList(
            customerId: $customerId,
            sessionGuestId: $sessionGuestId,
            requestGuestId: $requestGuestId,
            groupCouponDiscounts: $groupCouponDiscounts,
            groupReferralDiscounts: $groupReferralDiscounts,
            shippingAddressId: $shippingAddressId,
            billingAddressId: $billingAddressId,
        );

        $orderGenerateCartList = [];

        foreach ($vendorWiseCartList as $vendorWiseCart) {
            $orderGenerateCartList[] = $this->applyVendorWiseCartTax(vendorWiseCart: $vendorWiseCart, taxConfig: $taxConfig);
        }

        return $orderGenerateCartList;
    }

    /** @return array<int, string> */
    public function clearOrderedCartGroups(array $cartGroupIds): array
    {
        $this->cartShippingService->deleteByCartGroupIds(cartGroupIds: $cartGroupIds);
        $this->query()->whereIn('cart_group_id', $cartGroupIds)->where('is_checked', 1)->delete();

        return $this->orderedCartSessionKeys();
    }

    public function getOwnerCartItemList(?int $customerId, int|string|null $guestId): Collection
    {
        $this->refreshPrices(customerId: $customerId, guestId: $guestId ?? 0);
        $this->refreshShippingCosts(customerId: $customerId, guestId: $guestId, type: self::TYPE_CHECKED);

        $activeTaxVats = fn ($query) => $query->with(['tax'])->whereHas('tax', fn ($tax) => $tax->where('is_active', 1));

        return $this->query()
            ->whereHas('product', fn (Builder $builder) => $builder->active())
            ->with([
                'shop',
                'product' => fn ($query) => $query->with([
                    'category' => fn ($category) => $category->with(['taxVats' => $activeTaxVats]),
                    'clearanceSale' => fn ($clearanceSale) => $clearanceSale->active(),
                    'taxVats' => $activeTaxVats,
                ]),
            ])
            ->where($this->ownerParams(customerId: $customerId, guestId: $guestId))
            ->get();
    }

    /** @return array{item_count: int, total_amount: float} */
    public function getOwnerCartSummary(?int $customerId, int|string|null $guestId): array
    {
        if ($customerId === null && empty($guestId)) {
            return ['item_count' => 0, 'total_amount' => 0.0];
        }

        $cartItems = $this->query()
            ->whereHas('product', fn (Builder $builder) => $builder->active())
            ->with(['product' => fn ($query) => $query->with([
                'clearanceSale' => fn ($clearanceSale) => $clearanceSale->active(),
            ])])
            ->where($this->ownerParams(customerId: $customerId, guestId: $guestId))
            ->get();

        return [
            'item_count' => $cartItems->count(),
            'total_amount' => (float) $this->getDiscountedSubtotal(cartItems: $cartItems),
        ];
    }

    /** @param  array<int, array<string, mixed>>  $orderGenerateCartList */
    public function applyCartListDetails(Collection $cartItems, array $orderGenerateCartList, ?int $customerId, int|string|null $guestId): Collection
    {
        $taxConfig = self::getTaxSystemType(businessModule: $this->businessModule());
        $physicalCartGroupIds = $this->query()
            ->where('product_type', self::PRODUCT_TYPE_PHYSICAL)
            ->whereIn('cart_group_id', $cartItems->pluck('cart_group_id')->unique()->values()->all())
            ->pluck('cart_group_id')
            ->unique()
            ->flip();
        $minimumOrderAmounts = [];
        $freeDeliveryOrderAmounts = [];

        foreach ($cartItems as $cartItem) {
            foreach ($orderGenerateCartList as $vendorCart) {
                if ($vendorCart['seller_id'] == $cartItem['seller_id'] && $vendorCart['seller_is'] == $cartItem['seller_is']) {
                    $cartTaxRows = collect($vendorCart['applied_tax_cart_list'])->where('cart_id', $cartItem['id']);
                    $cartItem['applied_tax'] = $cartTaxRows->sum('applied_tax_amount');
                    $cartItem['shipping_cost_tax'] = $cartTaxRows->sum('applied_shipping_cost_tax');
                }
            }

            $cartGroupId = (string) $cartItem['cart_group_id'];

            $cartItem['is_product_available'] = $cartItem->product !== null ? 1 : 0;
            $cartItem['choices'] = json_decode($cartItem['choices'] ?? '');
            $cartItem['variations'] = json_decode($cartItem['variations'] ?? '');
            $cartItem['applied_tax_type'] = $taxConfig['is_included'] ? self::APPLIED_TAX_TYPE_INCLUDE : self::APPLIED_TAX_TYPE_EXCLUDE;

            if (! array_key_exists($cartGroupId, $minimumOrderAmounts)) {
                $minimumOrderAmounts[$cartGroupId] = $this->verifyCartListMinimumOrderAmount(
                    customerId: $customerId,
                    sessionGuestId: null,
                    requestGuestId: $guestId,
                    cartGroupId: $cartGroupId,
                )['minimum_order_amount'];
            }

            $cartItem['minimum_order_amount_info'] = $minimumOrderAmounts[$cartGroupId];

            if (isset($physicalCartGroupIds[$cartGroupId])) {
                if (! array_key_exists($cartGroupId, $freeDeliveryOrderAmounts)) {
                    $freeDeliveryOrderAmounts[$cartGroupId] = $this->getGroupFreeDeliveryOrderAmount(
                        customerId: $customerId,
                        sessionGuestId: null,
                        requestGuestId: $guestId,
                        cartGroupId: $cartGroupId,
                    );
                }

                $cartItem['free_delivery_order_amount'] = $freeDeliveryOrderAmounts[$cartGroupId];
            } else {
                $cartItem['free_delivery_order_amount'] = self::EMPTY_FREE_DELIVERY_ORDER_AMOUNT;
            }

            if ($cartItem->product !== null) {
                $cartItem->product['total_current_stock'] = $this->resolveItemStock(cartItem: $cartItem);
            }

            $cartItem['discount'] = $this->resolveItemDiscount(cartItem: $cartItem);
        }

        return $cartItems;
    }

    public function updateItemsCheckedStatus(int|string $ownerId, int $isGuest, array $ids, bool $isChecked): int
    {
        return $this->query()
            ->where(['customer_id' => $ownerId, 'is_guest' => $isGuest])
            ->whereIn('id', $ids)
            ->update(['is_checked' => $isChecked ? 1 : 0]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cartItems
     * @return array{total_tax_amount: float|int, modified_cart: SupportCollection}
     */
    public function calculatePosCartTax(
        array $cartItems,
        iterable $products,
        mixed $couponDiscountAmount,
        mixed $extraDiscount,
        ?string $extraDiscountType,
    ): array {
        $products = collect($products);
        $taxConfig = self::getTaxSystemType(businessModule: $this->businessModule());
        $modifiedCart = [];

        foreach ($cartItems as $cartItem) {
            $product = $products->where('id', $cartItem['id'])->first();
            $pricing = $this->resolvePosItemPricing(cartItem: $cartItem, product: $product);

            $modifiedCart[] = [
                'id' => $cartItem['id'],
                'price' => $pricing['price'],
                'discounted_price' => $pricing['price'] - $pricing['discount'],
                'discount' => $pricing['discount'],
                'discount_type' => $product['discount_type'],
                'quantity' => $cartItem['quantity'],
                'variant' => $cartItem['variant'] ?? null,
                'variant_key' => $cartItem['variant_key'] ?? null,
                'digital_variation_price' => $cartItem['digital_variation_price'] ?? null,
                'variation' => $cartItem['variation'] ?? null,
                'taxVats' => $product['taxVats'],
                'category' => $product['category'],
            ];
        }

        $totalDiscountedPrice = 0;

        foreach ($modifiedCart as $cartItem) {
            $totalDiscountedPrice += $cartItem['discounted_price'] * $cartItem['quantity'];
        }

        $modifiedCart = collect($modifiedCart)->map(function (array $cartItem) use ($taxConfig, $totalDiscountedPrice, $couponDiscountAmount, $extraDiscount, $extraDiscountType): array {
            $productDiscountedPrice = $cartItem['discounted_price'] * $cartItem['quantity'];
            $couponDiscount = $totalDiscountedPrice == 0 ? 0 : ($couponDiscountAmount * $productDiscountedPrice) / $totalDiscountedPrice;

            if ($extraDiscountType == self::POS_EXTRA_DISCOUNT_PERCENT) {
                $appliedExtraDiscount = (($productDiscountedPrice - $couponDiscount) * $extraDiscount) / 100;
            } else {
                $appliedExtraDiscount = $totalDiscountedPrice == 0 ? 0 : ($extraDiscount * $productDiscountedPrice) / $totalDiscountedPrice;
            }

            $cartItem['coupon_discount'] = $couponDiscount;
            $cartItem['extra_discount'] = $appliedExtraDiscount;
            $cartItem['applied_tax_amount'] = $this->getAppliedTaxAmount(
                product: $cartItem,
                taxConfig: $taxConfig,
                totalDiscountedPrice: $productDiscountedPrice,
                appliedDiscountedAmount: $couponDiscount + $appliedExtraDiscount,
            );

            return $cartItem;
        });

        return [
            'total_tax_amount' => $modifiedCart->sum('applied_tax_amount'),
            'modified_cart' => $modifiedCart,
        ];
    }

    /** @return array{price: mixed, discount: float|int|string} */
    private function resolvePosItemPricing(array $cartItem, Model $product): array
    {
        $price = $product['unit_price'];
        $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $product['unit_price'], from: 'panel');

        if (($cartItem['variant'] ?? null) != null) {
            foreach (json_decode($product['variation'] ?? '', true) ?: [] as $variation) {
                if ($cartItem['variant'] == $variation['type']) {
                    $price = $variation['price'];
                    $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $variation['price'], from: 'panel');
                }
            }
        }

        if ($product['product_type'] == self::PRODUCT_TYPE_DIGITAL && ! empty($cartItem['variant_key'] ?? null)) {
            foreach ($product['digitalVariation'] ?? [] as $digitalVariation) {
                if ($digitalVariation['variant_key'] == $cartItem['variant_key']) {
                    $price = $digitalVariation['price'];
                    $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $digitalVariation['price'], from: 'panel');
                }
            }
        }

        return ['price' => $price, 'discount' => $discount];
    }

    public function getAppliedTaxIds(mixed $product = null, array $taxConfig = []): array
    {
        if (! isset($taxConfig['is_included']) || $taxConfig['is_included']) {
            return [];
        }

        $taxType = $taxConfig['SystemTaxVatType'] ?? self::TAX_TYPE_ORDER_WISE;

        if ($taxType == self::TAX_TYPE_PRODUCT_WISE) {
            $taxIds = $product['taxVats']?->pluck('tax')->toArray() ?? [];
        } elseif ($taxType == self::TAX_TYPE_CATEGORY_WISE) {
            $taxIds = $product?->category?->taxVats?->pluck('tax')->toArray() ?? [];
        } else {
            $taxIds = collect($taxConfig['taxVats'])->whereIn('id', $taxConfig['SystemTaxVat']['tax_ids'])->toArray();
        }

        return $taxIds ?? [];
    }

    public function getAppliedShippingTaxIds(array $taxConfig = []): array
    {
        if (! isset($taxConfig['is_included']) || $taxConfig['is_included']) {
            return [];
        }

        return collect($taxConfig['SystemTaxVat']['additionalData'])->toArray();
    }

    public function getAppliedTaxAmount(mixed $product = null, array $taxConfig = [], float|int $totalDiscountedPrice = 0, float|int $appliedDiscountedAmount = 0): float|int
    {
        if (! isset($taxConfig['is_included']) || $taxConfig['is_included']) {
            return 0;
        }

        $taxPercent = 0;
        $taxType = $taxConfig['SystemTaxVatType'] ?? self::TAX_TYPE_ORDER_WISE;

        if ($taxType == self::TAX_TYPE_PRODUCT_WISE) {
            $taxPercent = $this->sumActiveTaxRates(taxVats: $product['taxVats'] ?? null);
        } elseif ($taxType == self::TAX_TYPE_CATEGORY_WISE) {
            $taxPercent = $this->sumActiveTaxRates(taxVats: $product['category']['taxVats'] ?? null);
        } else {
            $taxPercent = collect($taxConfig['taxVats'])->whereIn('id', $taxConfig['SystemTaxVat']['tax_ids'])->sum('tax_rate');
        }

        return (($totalDiscountedPrice - $appliedDiscountedAmount) * $taxPercent) / 100;
    }

    public function getAppliedShippingTaxAmount(array $taxConfig = [], float|int|string $totalShippingCost = 0, float|int $totalDiscountedPrice = 0, float|int $applicableShippingAmount = 0): float|int
    {
        if (! isset($taxConfig['is_included']) || $taxConfig['is_included']) {
            return 0;
        }

        $allTaxIds = collect($taxConfig['SystemTaxVat']['additionalData'])->pluck('tax_ids')->flatten()->toArray();
        $taxRate = collect($taxConfig['taxVats'])->whereIn('id', $allTaxIds)->sum('tax_rate');

        if ($totalShippingCost <= 0 || $applicableShippingAmount <= 0) {
            return 0;
        }

        return ((($totalShippingCost * $totalDiscountedPrice) / $applicableShippingAmount) * $taxRate) / 100;
    }

    private function sumActiveTaxRates(mixed $taxVats): float|int
    {
        $taxPercent = 0;

        if (isset($taxVats) && count($taxVats) > 0) {
            foreach ($taxVats as $taxVat) {
                if ($taxVat?->tax?->tax_rate) {
                    $taxPercent += $taxVat?->tax?->tax_rate;
                }
            }
        }

        return $taxPercent;
    }

    private function applyVendorWiseCartTax(array $vendorWiseCart, array $taxConfig): array
    {
        if ($vendorWiseCart['is_shipping_free'] == 1) {
            $totalDiscountOnProduct = $vendorWiseCart['coupon_discount'] + $vendorWiseCart['refer_and_earn_discount'];
        } elseif ($vendorWiseCart['coupon_type'] == self::COUPON_TYPE_FREE_DELIVERY) {
            $totalDiscountOnProduct = $vendorWiseCart['refer_and_earn_discount'];
        } else {
            $totalDiscountOnProduct = $vendorWiseCart['coupon_discount'] + $vendorWiseCart['free_delivery_discount'] + $vendorWiseCart['refer_and_earn_discount'];
        }

        $totalDiscountProductPrice = 0;
        $totalApplicableShippingAmount = 0;

        foreach ($vendorWiseCart['cart_list'] as $cartItem) {
            if ($cartItem->product) {
                $currentProductDiscountedPrice = ($cartItem->price - $cartItem->discount) * $cartItem->quantity;
                $totalDiscountProductPrice += $currentProductDiscountedPrice;
                $totalApplicableShippingAmount += $cartItem?->product?->product_type == self::PRODUCT_TYPE_DIGITAL ? 0 : $currentProductDiscountedPrice;
            }
        }

        $vendorWiseCartAppliedTax = 0;
        $vendorWiseCart['applied_tax_cart_list'] = [];

        foreach ($vendorWiseCart['cart_list'] as $cartItem) {
            if (! $cartItem->product) {
                continue;
            }

            $isDigital = $cartItem?->product?->product_type == self::PRODUCT_TYPE_DIGITAL;
            $totalDiscountPrice = ($cartItem->price - $cartItem->discount) * $cartItem->quantity;
            $appliedDiscountAmount = $totalDiscountOnProduct > 0 ? ($totalDiscountOnProduct * $totalDiscountPrice) / $totalDiscountProductPrice : 0;
            $appliedTaxAmount = $this->getAppliedTaxAmount(
                product: $cartItem->product,
                taxConfig: $taxConfig,
                totalDiscountedPrice: $totalDiscountPrice,
                appliedDiscountedAmount: $appliedDiscountAmount,
            );
            $appliedTaxIds = $this->getAppliedTaxIds(product: $cartItem->product, taxConfig: $taxConfig);
            $appliedShippingTaxIds = $this->getAppliedShippingTaxIds(taxConfig: $taxConfig);
            $appliedShippingTaxAmount = $this->getAppliedShippingTaxAmount(
                taxConfig: $taxConfig,
                totalShippingCost: $this->taxableShippingCost(vendorWiseCart: $vendorWiseCart),
                totalDiscountedPrice: $totalDiscountPrice,
                applicableShippingAmount: $totalApplicableShippingAmount,
            );

            $vendorWiseCartAppliedTax += $appliedTaxAmount;
            $vendorWiseCartAppliedTax += $isDigital ? 0 : $appliedShippingTaxAmount;
            $vendorWiseCart['applied_tax_cart_list'][] = [
                'cart_id' => $cartItem->id,
                'cart_group_id' => $cartItem->cart_group_id,
                'product_id' => $cartItem->product->id,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
                'discount' => $cartItem->discount,
                'seller_id' => $cartItem->seller_id,
                'seller_is' => $cartItem->seller_is,
                'discounted_price' => ($cartItem->price - $cartItem->discount),
                'total_discounted_price' => $totalDiscountPrice,
                'applied_discounted_amount' => $appliedDiscountAmount,
                'applied_tax_amount' => $appliedTaxAmount,
                'applied_shipping_cost_tax' => $isDigital ? 0 : $appliedShippingTaxAmount,
                'applied_tax_ids' => $appliedTaxIds,
                'applied_shipping_cost_tax_ids' => $isDigital ? [] : $appliedShippingTaxIds,
            ];
        }

        $vendorWiseCart['total_tax_amount'] = $vendorWiseCartAppliedTax;
        $vendorWiseCart['order_amount_with_tax'] = $vendorWiseCart['order_amount'] + $vendorWiseCartAppliedTax;

        return $vendorWiseCart;
    }

    private function taxableShippingCost(array $vendorWiseCart): float|int|string
    {
        if ($vendorWiseCart['is_shipping_free'] == 1) {
            return $vendorWiseCart['shipping_cost'] - $vendorWiseCart['free_delivery_discount'];
        }

        return $vendorWiseCart['coupon_type'] == self::COUPON_TYPE_FREE_DELIVERY
            ? $vendorWiseCart['shipping_cost'] - $vendorWiseCart['coupon_discount']
            : $vendorWiseCart['shipping_cost'];
    }

    private function getRefreshedGroupShippingCost(?int $customerId, int|string|null $requestGuestId, string $cartGroupId): float|int|string
    {
        $this->refreshShippingCosts(customerId: $customerId, guestId: $requestGuestId, cartGroupId: $cartGroupId, type: self::TYPE_CHECKED);

        return $this->getShippingCost(cartGroupId: $cartGroupId, type: self::TYPE_CHECKED);
    }

    private function buildGroupFreeDeliveryOrderAmount(?string $cartGroupId, ?Model $freeDeliveryCartItem): array
    {
        return $this->getFreeDeliveryOrderAmount(
            cartGroupId: $cartGroupId,
            freeDeliveryCartItem: $freeDeliveryCartItem,
            seller: $freeDeliveryCartItem !== null && $freeDeliveryCartItem->seller_is != self::SELLER_IS_ADMIN
                ? $freeDeliveryCartItem->seller
                : null,
        );
    }

    private function isCartVendorUnavailable(Model $cartItem): bool
    {
        $isVendor = $cartItem->seller_is == self::SELLER_IS_SELLER;
        $vendorType = $isVendor ? 'vendor' : 'inhouse';
        $vendor = $isVendor ? $cartItem->shop : null;

        $isTemporarilyClosed = checkVendorAbility(type: $vendorType, status: 'temporary_close', vendor: $vendor, businessModule: $this->businessModule());
        $isOnVacation = checkVendorAbility(type: $vendorType, status: 'vacation_status', vendor: $vendor, businessModule: $this->businessModule());

        return $isTemporarilyClosed || $isOnVacation;
    }

    private function addShippingBlockers(
        array &$response,
        array &$blockers,
        bool &$isInHouseShippingMethodChecked,
        Model $cartItem,
        ?string $shippingMethod,
        array $activeShippingMethodCounts,
    ): void {
        $isInHouseShipping = $shippingMethod == self::SHIPPING_METHOD_INHOUSE;
        $isInHouseSeller = $cartItem->seller_is == self::SELLER_IS_ADMIN;

        $shippingType = $this->shippingTypeService->resolveTypeForSeller(
            $isInHouseShipping || $isInHouseSeller ? self::ADMIN_SHIPPING_SELLER_ID : $cartItem->seller_id,
        );

        if ($shippingType != ShippingTypeService::TYPE_ORDER_WISE) {
            return;
        }

        $shippingMethodCount = match (true) {
            $isInHouseShipping, $isInHouseSeller => $activeShippingMethodCounts[ShippingMethodService::CREATOR_ADMIN] ?? 0,
            $cartItem->seller_is == self::SELLER_IS_SELLER => $activeShippingMethodCounts[ShippingMethodService::CREATOR_SELLER][(int) $cartItem->seller_id] ?? 0,
            default => null,
        };

        if ($shippingMethodCount !== null && $shippingMethodCount <= 0 && isset($cartItem->seller->shop)) {
            $this->addCheckoutBlocker($response, $blockers, [
                'reason' => self::REASON_SHIPPING_NOT_AVAILABLE_FOR_SHOP,
                'shop_name' => match (true) {
                    $isInHouseShipping => getWebConfig(name: self::COMPANY_NAME_SETTING),
                    $isInHouseSeller => getInHouseShopConfig(key: 'name'),
                    default => $cartItem->seller->shop->name,
                },
            ], self::REDIRECT_SHOP_CART);
        }

        $shippingMethodCount = $shippingMethodCount ?? 0;

        if ($shippingMethodCount > 0 && $isInHouseShipping && ! $isInHouseShippingMethodChecked) {
            if (! $this->cartShippingService->exists(params: ['cart_group_id' => $cartItem->cart_group_id])) {
                $this->addCheckoutBlocker($response, $blockers, ['reason' => self::REASON_SHIPPING_METHOD_REQUIRED], self::REDIRECT_SHOP_CART, self::ERROR_TYPE_EMPTY_SHIPPING);
            }

            $isInHouseShippingMethodChecked = true;
        } elseif ($shippingMethodCount > 0 && ! $isInHouseShipping) {
            if (! $this->cartShippingService->exists(params: ['cart_group_id' => $cartItem->cart_group_id])) {
                $this->addCheckoutBlocker($response, $blockers, [
                    'reason' => self::REASON_SHOP_SHIPPING_METHOD_REQUIRED,
                    'shop_name' => $isInHouseSeller ? getInHouseShopConfig(key: 'name') : $cartItem->seller?->shop?->name,
                ], self::REDIRECT_SHOP_CART, self::ERROR_TYPE_EMPTY_SHIPPING);
            }
        }
    }

    private function addCheckoutBlocker(array &$response, array &$blockers, array $blocker, string $redirect, ?string $errorType = null): void
    {
        $response['status'] = self::STATUS_FAILED;

        if ($errorType !== null) {
            $response['errorType'] = $errorType;
        }

        $response['redirect'] = $redirect;
        $blockers[] = $blocker;
    }

    private function ownerGuestId(int|string|null $sessionGuestId, int|string|null $requestGuestId): int|string
    {
        return $sessionGuestId ?? $requestGuestId ?? 0;
    }
}
