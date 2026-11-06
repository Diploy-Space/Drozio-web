<?php

namespace App\Services\New;

use App\Models\Order;
use App\Services\BaseService;
use Carbon\Carbon;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class OrderEditService extends BaseService
{
    use VatTaxManagement;

    public const OWNER_ADMIN = 'ADMIN';

    public const OWNER_SELLER = 'SELLER';

    public const OWNER_DEFAULT = 'DEFAULT';

    public const TYPE_ADMIN = 'admin';

    public const TYPE_VENDOR = 'vendor';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_ERROR = 'error';

    private const SESSION_PREFIX = '_SESSION_FOR_EDIT_ORDER_';

    private const SINGLE_VARIANT = 'single-variant';

    private const EDITABLE_ORDER_STATUSES = ['pending', 'confirmed'];

    private const ORDER_TYPE_DEFAULT = 'default_type';

    private const PRODUCT_TYPE_PHYSICAL = 'physical';

    private const PAYMENT_METHOD_OFFLINE = 'offline_payment';

    private const PAYMENT_STATUS_UNPAID = 'unpaid';

    private const ADDED_BY_IN_HOUSE = 'in_house';

    private const DEFAULT_MAXIMUM_EDIT_QUANTITY = 100;

    private const HIDDEN_PRODUCT_ATTRIBUTES = ['order_details', 'order_delivered', 'seo_info', 'reviews'];

    public const REASON_OUT_OF_STOCK = 'out_of_stock';

    public const REASON_PRODUCT_NOT_FOUND = 'product_not_found';

    public const EDITOR_SELLER = 'seller';

    public const REQUEST_FROM_APP = 'app';

    private const COUPON_TYPE_FIRST_ORDER = 'first_order';

    private const COUPON_TYPE_DISCOUNT_ON_PURCHASE = 'discount_on_purchase';

    private const COUPON_TYPE_FREE_DELIVERY = 'free_delivery';

    private const DISCOUNT_TYPE_PERCENTAGE = 'percentage';

    private const DISCOUNT_TYPE_COUPON = 'coupon_discount';

    private const EXTRA_DISCOUNT_FREE_SHIPPING = 'free_shipping_over_order_amount';

    private const SHIPPING_METHOD_INHOUSE = 'inhouse_shipping';

    private const SHIPPING_METHOD_SELLERWISE = 'sellerwise_shipping';

    private const SHIPPING_TYPE_ORDER_WISE = 'order_wise';

    private const ADMIN_SHIPPING_SELLER_ID = 0;

    private const SELLER_IS_ADMIN = 'admin';

    private const SELLER_IS_SELLER = 'seller';

    private const FREE_DELIVERY_BY_ADMIN = 'admin';

    private const FREE_DELIVERY_BY_SELLER = 'seller';

    private const PAYMENT_METHOD_CASH_ON_DELIVERY = 'cash_on_delivery';

    private const PRODUCT_TYPE_DIGITAL = 'digital';

    private const DELIVERY_STATUS_PENDING = 'pending';

    private const RETURN_PAYMENT_STATUS_PENDING = 'pending';

    private const TAX_MODEL_INCLUDE = 'include';

    private const TAX_MODEL_EXCLUDE = 'exclude';

    /** @var array<int|string, mixed> */
    private const EDIT_ORDER_PRODUCT_BASE_RELATIONS = ['retailDetail', 'category', 'brand', 'reviews', 'rating', 'orderDetails', 'orderDelivered', 'digitalVariation', 'seoInfo'];

    public function __construct(Order $order)
    {
        parent::__construct($order);
    }

    public function getOrderEditSessionKey(string $owner, mixed $orderId): string
    {
        return $owner.self::SESSION_PREFIX.$orderId;
    }

    /** @return array<int|string, mixed> */
    public function getEditableProductRelations(): array
    {
        $taxConfig = self::getTaxSystemType();
        $isProductWiseTax = $taxConfig['productWiseTax'] && ! $taxConfig['is_included'];

        return [
            ...($isProductWiseTax ? ['taxVats' => fn ($query) => $query->with('tax')->whereHas('tax', fn ($tax) => $tax->where('is_active', 1))] : []),
            'digitalVariation',
            'clearanceSale' => fn ($query) => $query->active(),
        ];
    }

    /** @return array<string, mixed> */
    public function addOrderDetailsInSession(object|array $order): array
    {
        $activeProducts = collect($order?->details?->pluck('productAllStatus'));
        $products = [];

        foreach ($order?->details as $details) {
            $product = json_decode($details->product_details, true);

            if (! $product) {
                continue;
            }

            $activeProduct = $activeProducts?->firstWhere('id', $details['product_id']) ?? $product;
            $currentStock = $activeProduct ? max(0, $activeProduct['current_stock']) : $product['current_stock'];
            $variations = is_array($activeProduct['variation'])
                ? $activeProduct['variation']
                : (json_decode($activeProduct['variation'] ?? '', true) ?? []);

            $matchedVariation = collect($variations)->first(
                static fn ($variation) => $variation['type'] == $details['variant'],
            );

            $unitPrice = $activeProduct ? $activeProduct['unit_price'] : $details['price'];

            if ($details['variant'] && $matchedVariation) {
                $currentStock = $matchedVariation['qty'] ?? 0;
                $unitPrice = $matchedVariation['price'] ?? 0;
            }

            $currentStock += $details['qty'] ?? 1;

            $priceSource = $activeProduct ?: $product;
            $discount = getProductPriceByType(product: $priceSource, type: 'discounted_amount', result: 'value', price: $details['price']) ?? 0;
            $discountedPrice = $activeProduct
                ? getProductPriceByType(product: $activeProduct, type: 'discounted_unit_price', result: 'value', price: $details['price'])
                : (getProductPriceByType(product: $product, type: 'discount', result: 'value', price: $details['price']) ?? 0);

            $products[$this->generateProductIndex(id: $details['product_id'], variant: $details['variant'] ?? '')] = [
                'existing_product' => true,
                'existing_product_modified' => true,
                'product_type' => $product['product_type'] ?? self::PRODUCT_TYPE_PHYSICAL,
                'name' => $product['name'],
                'discounted_price' => $discountedPrice,
                'thumbnail_full_url' => $details?->product?->thumbnail_full_url ?? [],
                'is_quantity_editable' => $this->isQuantityEditable(
                    activeProduct: $activeProducts?->firstWhere('id', $details['product_id']),
                    storedProduct: $product,
                    variant: $details['variant'],
                ),
                'order_id' => $details['order_id'],
                'product_id' => $details['product_id'],
                'seller_id' => $details['seller_id'],
                'seller_is' => $order['seller_is'],
                'minimum_order_qty' => $product['minimum_order_qty'] ?? 1,
                'digital_file_after_sell' => $details['digital_file_after_sell'],
                'product_details' => $details['product_details'],
                'active_product' => $activeProduct ?? $product,
                'qty' => $details['qty'],
                'price' => $unitPrice,
                'tax' => $details['tax'],
                'discount' => $discount,
                'current_stock' => $currentStock,
                'tax_model' => $details['tax_model'],
                'delivery_status' => $details['delivery_status'],
                'payment_status' => $details['payment_status'],
                'shipping_method_id' => $details['shipping_method_id'],
                'variant' => $details['variant'],
                'variation' => $details['variation'],
                'discount_type' => $activeProduct ? $activeProduct['discount_type'] : ($product['discount_type'] ?? ''),
                'is_stock_decreased' => $details['is_stock_decreased'],
                'refund_request' => $details['refund_request'],
                'refund_started_at' => now(),
                'created_at' => $details['created_at'],
            ];
        }

        return $products;
    }

    private function isQuantityEditable(mixed $activeProduct, array $storedProduct, ?string $variant): bool
    {
        if (! $activeProduct) {
            return false;
        }

        $storedVariations = json_decode($storedProduct['variation'] ?? '', true) ?? [];
        $activeVariations = json_decode($activeProduct['variation'] ?? '', true) ?? [];

        $isEditable = true;

        if (count($activeVariations) > 0) {
            $isEditable = collect($activeVariations)
                ->filter(static fn ($item) => $item['type'] == $variant)
                ->isNotEmpty();
        }

        if (count($activeVariations) !== count($storedVariations) && (count($activeVariations) === 0 || count($storedVariations) === 0)) {
            return false;
        }

        if ($variant && count($activeVariations) === 0) {
            return false;
        }

        return $isEditable;
    }

    private function resolveRequestedVariant(array $productVariations, ?string $colorName, array $choiceOptions, array $choiceValues, ?string $variant): string
    {
        if ($productVariations === []) {
            return '';
        }

        $selectedVariant = $this->getVariationFromChoices(colorName: $colorName, choiceOptions: $choiceOptions, choiceValues: $choiceValues);

        return $selectedVariant !== '' ? $selectedVariant : (string) $variant;
    }

    private function getVariationFromChoices(?string $colorName, array $choiceOptions, array $choiceValues): string
    {
        $variation = $colorName ?: '';

        foreach ($choiceOptions as $choice) {
            $variation .= ($variation !== '' ? '-' : '').str_replace(' ', '', (string) ($choiceValues[$choice->name] ?? ''));
        }

        return $variation;
    }

    /**
     * @param  array<string, mixed>  $productList
     * @return array<string, mixed>
     */
    public function getProductCurrentStockInfo(
        object|array $order,
        object|array $product,
        array $productList = [],
        ?string $colorName = '',
        array $choiceValues = [],
    ): array {
        $variations = json_decode($product->variation ?? '', true) ?? [];
        $hasVariations = count($variations) > 0;
        $currentQuantity = $product['minimum_order_qty'] ?? 1;
        $allVariationsOutOfStock = true;
        $firstVariantType = '';

        if ($hasVariations) {
            $generatedVariation = $this->getVariationFromChoices(
                colorName: $colorName,
                choiceOptions: json_decode($product['choice_options']),
                choiceValues: $choiceValues,
            );

            $matchedVariation = collect($variations)->first(
                static fn ($variation) => $variation['type'] == $generatedVariation,
            ) ?? $variations[0];

            $firstVariantType = $matchedVariation['type'] ?? '';
            $currentStock = $matchedVariation['qty'] ?? 0;
            $currentPrice = $matchedVariation['price'] ?? 0;

            foreach ($variations as $variation) {
                if (! empty($variation['qty']) && $variation['qty'] > 0) {
                    $allVariationsOutOfStock = false;
                    break;
                }
            }
        } else {
            $currentStock = max(0, $product->current_stock);
            $currentPrice = $product->unit_price;
        }

        $orderedDetails = collect($order?->details)?->where('product_id', $product['id'])->firstWhere('variant', $firstVariantType);

        if ($orderedDetails) {
            $currentStock += $orderedDetails['qty'] ?? 1;
        }

        $alreadyInCart = false;

        foreach ($productList as $item) {
            if ($item['product_id'] == $product['id'] && ($item['variant'] ?? '') === $firstVariantType) {
                $alreadyInCart = true;
                $currentQuantity = $item['qty'] ?? 1;
                break;
            }
        }

        return [
            'variant' => $firstVariantType,
            'has_variations' => $hasVariations,
            'current_stock' => $currentStock,
            'current_price' => $currentPrice,
            'current_quantity' => $currentQuantity,
            'stock_out_status' => $allVariationsOutOfStock,
            'discount' => getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $currentPrice),
            'discounted_price' => getProductPriceByType(product: $product, type: 'discounted_unit_price', result: 'value', price: $currentPrice),
            'already_in_cart' => $alreadyInCart,
        ];
    }

    /**
     * @param  array<string, mixed>  $productList
     * @return array<string, mixed>
     */
    public function addOrUpdateProductInOrderSession(
        object|array $order,
        object|array $product,
        array $productList,
        object|array|null $color,
        int|string $orderId,
        ?string $variant,
        mixed $quantity,
        array $choiceValues,
    ): array {
        $currentStock = $product['current_stock'];
        $unitPrice = $product['unit_price'] ?? 0;
        $productVariations = json_decode($product->variation ?? '', true) ?? [];
        $choiceOptions = json_decode($product->choice_options) ?? [];
        $requestedVariant = $this->resolveRequestedVariant(
            productVariations: $productVariations,
            colorName: $color?->name,
            choiceOptions: $choiceOptions,
            choiceValues: $choiceValues,
            variant: $variant,
        );
        $variations = [];

        if ($color) {
            $variations['color'] = $color?->name;
        }

        foreach ($choiceOptions as $choice) {
            $variations[$choice->title] = $choiceValues[$choice->name] ?? null;
        }

        if ($requestedVariant !== '') {
            $matchedVariation = collect($productVariations)->firstWhere('type', $requestedVariant);

            if ($matchedVariation === null) {
                return [
                    'status' => self::STATUS_ERROR,
                    'message' => translate('Choose a valid variant'),
                    'product_list' => $productList,
                ];
            }

            $unitPrice = $matchedVariation['price'] ?? 0;
            $currentStock = $matchedVariation['qty'] ?? 0;
        }

        $orderedDetails = collect($order?->details)->first(
            static fn ($item) => $item['product_id'] == $product['id'] && ($item['variant'] ?? '') === $requestedVariant,
        );

        if ($orderedDetails) {
            $currentStock += $orderedDetails['qty'] ?? 0;
        }

        if ($currentStock <= 0 || $currentStock < $product['minimum_order_qty']) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => translate('Out of Stock'),
                'product_list' => $productList,
            ];
        }

        $existingIndex = null;
        $existingItem = null;

        foreach ($productList as $key => $item) {
            if ($item['product_id'] == $product['id'] && ($item['variant'] ?? '') === $requestedVariant) {
                $existingItem = $item;
                $existingIndex = $key;
                break;
            }
        }

        $taxConfig = self::getTaxSystemType();

        $productData = [
            'current_stock' => $currentStock,
            'existing_product' => (bool) $orderedDetails,
            'existing_product_modified' => (bool) $orderedDetails,
            'product_type' => $product['product_type'],
            'name' => $product['name'],
            'discounted_price' => getProductPriceByType(product: $product, type: 'discounted_unit_price', result: 'value', price: $unitPrice),
            'thumbnail_full_url' => $product['thumbnail_full_url'] ?? [],
            'order_id' => $orderId,
            'product_id' => $product['id'],
            'seller_id' => $order['seller_id'],
            'seller_is' => $order['seller_is'],
            'minimum_order_qty' => $product['minimum_order_qty'] ?? 1,
            'digital_file_after_sell' => $orderedDetails['digital_file_after_sell'] ?? null,
            'product_details' => $product->makeHidden(self::HIDDEN_PRODUCT_ATTRIBUTES),
            'active_product' => $product,
            'qty' => (int) ($quantity ?? 1),
            'price' => $unitPrice,
            'tax' => 0,
            'discount' => getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $unitPrice),
            'tax_model' => $taxConfig['is_included'] ? 'include' : 'exclude',
            'delivery_status' => 'pending',
            'payment_status' => self::PAYMENT_STATUS_UNPAID,
            'shipping_method_id' => null,
            'variant' => $requestedVariant,
            'variation' => $orderedDetails ? $orderedDetails['variation'] : $variations,
            'discount_type' => $product['discount_type'],
            'refund_request' => 0,
            'refund_started_at' => now(),
            'created_at' => $orderedDetails ? $orderedDetails['created_at'] : Carbon::now(),
        ];

        if ($existingIndex !== null) {
            $productData['existing_product_modified'] = $existingItem['existing_product_modified'] ?? false;
            $productList[$existingIndex] = $productData;
            $message = translate(':item updated successfully', ['item' => translate('Product')]);
        } else {
            $productList[$this->generateProductIndex(id: $product['id'], variant: $requestedVariant)] = $productData;
            $message = translate(':item added successfully', ['item' => translate('Product')]);
        }

        return [
            'status' => self::STATUS_SUCCESS,
            'message' => $message,
            'product_list' => $productList,
        ];
    }

    /**
     * @param  array<string, mixed>  $productList
     * @return array<string, mixed>
     */
    public function removeProductInOrderSession(array $productList, int|string $productId, ?string $variant): array
    {
        if (count($productList) <= 1) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => translate('Cannot remove all products from the list'),
                'product_list' => $productList,
            ];
        }

        $remaining = [];

        foreach ($productList as $key => $item) {
            $isRemoved = $item['product_id'] == $productId
                && $item['variant'] == ($variant ?? '');

            if (! $isRemoved) {
                $remaining[$key] = $item;
            }
        }

        return [
            'status' => self::STATUS_SUCCESS,
            'message' => translate('Product removed successfully'),
            'product_list' => $remaining,
        ];
    }

    /**
     * @param  array<string, mixed>  $productList
     * @return array<string, mixed>
     */
    public function updateProductListInOrderSession(array $productList, array $requestedProducts): array
    {
        $updated = [];

        foreach ($productList as $key => $item) {
            if (! array_key_exists($key, $requestedProducts)) {
                continue;
            }

            $item['qty'] = $requestedProducts[$key]['qty'];
            $updated[$key] = $item;
        }

        return [
            'status' => self::STATUS_SUCCESS,
            'message' => translate(':item updated successfully', ['item' => translate('Product')]),
            'product_list' => $updated,
        ];
    }

    /** @return array{added_by: string, seller_id: mixed} */
    public function getEditableProductFilters(object|array $order): array
    {
        $isInHouseOrder = $order['seller_is'] == self::SELLER_IS_ADMIN;

        return [
            'added_by' => $isInHouseOrder ? self::ADDED_BY_IN_HOUSE : $order['seller_is'],
            'seller_id' => $isInHouseOrder ? '' : $order['seller_id'],
        ];
    }

    public function addOrderedQuantityToStock(object|array $order, iterable $products): iterable
    {
        foreach ($products as $product) {
            $orderedQuantity = collect($order?->details)->where('product_id', $product['id'])->sum('qty');
            $product->current_stock = max(0, $product->current_stock) + ($orderedQuantity > 0 ? $orderedQuantity : 0);
        }

        return $products;
    }

    /** @return array{min: mixed, max: mixed, value: mixed} */
    public function getQuantityBounds(mixed $minimum, mixed $maximum, mixed $requested): array
    {
        $requested ??= $minimum;

        $value = match (true) {
            $requested < $minimum => $minimum,
            $requested > $maximum => $maximum < $minimum ? $minimum : $maximum,
            default => $requested,
        };

        return ['min' => $minimum, 'max' => $maximum, 'value' => $value];
    }

    /**
     * @param  array<string, array<string, mixed>>  $productList
     * @return array<string, array{min: mixed, max: mixed, value: mixed}>
     */
    public function getProductListQuantityBounds(array $productList): array
    {
        $quantityBounds = [];

        foreach ($productList as $key => $item) {
            $quantityBounds[$key] = $this->getQuantityBounds(
                minimum: $item['minimum_order_qty'] ?? 1,
                maximum: $item['current_stock'] ?? self::DEFAULT_MAXIMUM_EDIT_QUANTITY,
                requested: $item['qty'] ?? null,
            );
        }

        return $quantityBounds;
    }

    /** @return array<string, mixed> */
    public function checkIsOrderEditable(object|array $order, string $type = self::TYPE_ADMIN): array
    {
        if ($order->order_type != self::ORDER_TYPE_DEFAULT || ! in_array($order->order_status, self::EDITABLE_ORDER_STATUSES)) {
            return [
                'status' => false,
                'message' => translate('Orders can only be edited while Pending or Confirmed.'),
            ];
        }

        if (! $this->hasPhysicalProduct($order)) {
            return [
                'status' => false,
                'message' => translate('Orders containing only digital products cannot be edited.'),
            ];
        }

        if ($type === self::TYPE_VENDOR && getWebConfig('vendor_can_edit_order') != 1) {
            return [
                'status' => false,
                'message' => translate('Vendors are not allowed to edit orders now.'),
            ];
        }

        if ($order['payment_method'] == self::PAYMENT_METHOD_OFFLINE && $order['payment_status'] == self::PAYMENT_STATUS_UNPAID) {
            return [
                'status' => false,
                'message' => translate('Please confirm the offline payment information before editing this order.'),
            ];
        }

        $latestEdit = $order?->latestEditHistory;

        if ($order->edited_status == 1
            && $latestEdit?->order_due_payment_method == self::PAYMENT_METHOD_OFFLINE
            && $latestEdit?->order_due_payment_status == self::PAYMENT_STATUS_UNPAID) {
            return [
                'status' => false,
                'message' => translate('Confirm the offline payment for the due amount before editing.'),
            ];
        }

        return [
            'status' => true,
            'message' => translate('Order is editable'),
        ];
    }

    /** @return array<int|string, mixed> */
    public function getEditOrderProductRelations(): array
    {
        $taxConfig = self::getTaxSystemType();
        $isProductWiseTax = $taxConfig['productWiseTax'] && ! $taxConfig['is_included'];

        return [
            ...($isProductWiseTax ? ['taxVats' => fn ($query) => $query->with(['tax'])->whereHas('tax', fn ($tax) => $tax->where('is_active', 1))] : []),
            ...self::EDIT_ORDER_PRODUCT_BASE_RELATIONS,
            'clearanceSale' => fn ($query) => $query->active(),
        ];
    }

    /** @return array{status: string, reason: ?string, product_list: array<string, array<string, mixed>>} */
    public function buildEditedProductList(array|string|null $requestedProducts, object|array $order, iterable $products, iterable $colors): array
    {
        $requestedProducts = is_array($requestedProducts) ? $requestedProducts : (json_decode($requestedProducts ?? '', true) ?? []);
        $products = collect($products);
        $colors = collect($colors);
        $taxConfig = self::getTaxSystemType();
        $productList = [];

        foreach ($requestedProducts as $productItem) {
            $product = $products->firstWhere('id', $productItem['id'] ?? null);

            if ($product === null) {
                return ['status' => self::STATUS_ERROR, 'reason' => self::REASON_PRODUCT_NOT_FOUND, 'product_list' => $productList];
            }

            $requestedVariant = $productItem['variant'] ?? null;
            $unitPrice = $product['unit_price'] ?? 0;
            $currentStock = $product['current_stock'] ?? 0;
            $variations = [];

            if ($productItem['color'] ?? null) {
                $variations['color'] = $colors->firstWhere('code', $productItem['color'])?->name ?? null;
            }

            foreach (json_decode($product->choice_options) ?? [] as $choice) {
                foreach ($productItem['choice'] ?? [] as $choiceItem) {
                    if ($choice?->name == $choiceItem['key']) {
                        $variations[$choice->title] = $choiceItem['value'];
                    }
                }
            }

            if ($requestedVariant != null) {
                foreach (json_decode($product->retailDetail?->variation ?? $product->variation ?? '') ?? [] as $variation) {
                    if ($variation->type == $requestedVariant) {
                        $unitPrice = $variation->price;
                        $currentStock = $variation->qty;
                    }
                }
            } else {
                $unitPrice = $product['unit_price'];
            }

            $orderedDetail = collect($order?->details)->where('product_id', $product['id'])->firstWhere('variant', $requestedVariant);
            if ($orderedDetail) {
                $currentStock += $orderedDetail['qty'] ?? 1;
            }

            if ($currentStock <= 0 || $currentStock < $product['minimum_order_qty']) {
                return ['status' => self::STATUS_ERROR, 'reason' => self::REASON_OUT_OF_STOCK, 'product_list' => $productList];
            }

            $existingDetail = collect($order?->details)->first(
                static fn ($item) => $item['product_id'] == $product['id'] && ($item['variant'] ?? '') == ($requestedVariant ?? ''),
            );

            $productList[$this->generateProductIndex(id: $product['id'], variant: $requestedVariant)] = [
                'current_stock' => $currentStock,
                'existing_product' => (bool) $existingDetail,
                'existing_product_modified' => (bool) $existingDetail,
                'product_type' => $product['product_type'],
                'name' => $product['name'],
                'discounted_price' => getProductPriceByType(product: $product, type: 'discounted_unit_price', result: 'value', price: $unitPrice),
                'thumbnail_full_url' => $product['thumbnail_full_url'] ?? [],
                'order_id' => $order['order_id'],
                'product_id' => $product['id'],
                'seller_id' => $order['seller_id'],
                'seller_is' => $order['seller_is'],
                'digital_file_after_sell' => $existingDetail['digital_file_after_sell'] ?? null,
                'product_details' => $product->makeHidden(self::HIDDEN_PRODUCT_ATTRIBUTES),
                'active_product' => $product,
                'qty' => $productItem['quantity'] ?? 1,
                'price' => $unitPrice,
                'tax' => 0,
                'discount' => getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $unitPrice),
                'tax_model' => $taxConfig['is_included'] ? self::TAX_MODEL_INCLUDE : self::TAX_MODEL_EXCLUDE,
                'delivery_status' => self::DELIVERY_STATUS_PENDING,
                'payment_status' => self::PAYMENT_STATUS_UNPAID,
                'shipping_method_id' => null,
                'variant' => $requestedVariant ?? '',
                'variation' => $existingDetail ? $existingDetail['variation'] : $variations,
                'discount_type' => $product['discount_type'],
                'refund_request' => 0,
                'refund_started_at' => now(),
                'created_at' => $existingDetail ? $existingDetail['created_at'] : Carbon::now(),
            ];
        }

        return ['status' => self::STATUS_SUCCESS, 'reason' => null, 'product_list' => $productList];
    }

    /** @param  array<string, array<string, mixed>>  $editedProducts */
    public function hasOutOfStockEditedProduct(object|array $order, array $editedProducts, iterable $products): bool
    {
        $products = collect($products);

        foreach ($editedProducts as $editedProduct) {
            $storedProduct = json_decode($editedProduct['product_details'] ?? '', true);
            $activeProduct = $products->firstWhere('id', $editedProduct['product_id']) ?? $storedProduct;
            $currentStock = max(0, $activeProduct['current_stock']);
            $storedVariation = is_array($activeProduct) ? ($activeProduct['variation'] ?? '[]') : ($activeProduct->retailDetail?->variation ?? '[]');
            $variations = is_array($storedVariation) ? $storedVariation : json_decode($storedVariation, true);
            $matchedVariation = collect($variations)->first(
                static fn ($variation) => $variation['type'] == $editedProduct['variant'],
            );

            if ($editedProduct['variant'] && $matchedVariation) {
                $currentStock = $matchedVariation['qty'] ?? 0;
            }

            $orderedDetail = collect($order?->details)->where('product_id', $activeProduct['id'])->firstWhere('variant', $editedProduct['variant']);
            if ($orderedDetail) {
                $currentStock += $orderedDetail['qty'] ?? 1;
            }

            if ($currentStock <= 0) {
                return true;
            }
        }

        return false;
    }

    public function getEditShippingTypeSellerId(object|array $order): ?int
    {
        return match (getWebConfig(name: 'shipping_method')) {
            self::SHIPPING_METHOD_INHOUSE => self::ADMIN_SHIPPING_SELLER_ID,
            self::SHIPPING_METHOD_SELLERWISE => $order['seller_is'] == self::SELLER_IS_ADMIN ? self::ADMIN_SHIPPING_SELLER_ID : (int) $order['seller_id'],
            default => null,
        };
    }

    /** @param  array<string, float|int>  $productShippingCosts */
    public function calculateEditShippingCost(?string $shippingType, mixed $orderWiseMethodCost, object|array $order, array $productShippingCosts): float|int
    {
        if ($shippingType === self::SHIPPING_TYPE_ORDER_WISE) {
            return $orderWiseMethodCost ?? $order['shipping_cost'];
        }

        $shippingCost = 0;
        foreach ($productShippingCosts as $productShippingCost) {
            $shippingCost += $productShippingCost;
        }

        return $shippingCost;
    }

    /** @param  array<string, array<string, mixed>>  $editedProducts */
    public function calculateEditItemsTotal(array $editedProducts): float|int
    {
        $total = 0;
        foreach ($editedProducts as $editedProduct) {
            $total += ($editedProduct['price'] - $editedProduct['discount']) * $editedProduct['qty'];
        }

        return $total;
    }

    /**
     * @param  array<string, array<string, mixed>>  $editedProducts
     * @return array{status: bool, discount: float|int, coupon_type: ?string, coupon_code: ?string, coupon_bearer: ?string, total_cart_amount: float|int}
     */
    public function calculateEditCouponSummary(object|array $order, array $editedProducts, ?object $coupon, float|int $shippingCost): array
    {
        $productTotal = $this->calculateEditItemsTotal(editedProducts: $editedProducts);
        $emptySummary = [
            'status' => false,
            'discount' => 0,
            'coupon_type' => null,
            'coupon_code' => null,
            'coupon_bearer' => null,
            'total_cart_amount' => $productTotal,
        ];

        if (! $coupon || $order['is_guest']) {
            return $emptySummary;
        }

        $discount = 0;
        $couponDiscount = $coupon?->discount ?? 0;

        if ($coupon?->coupon_type == self::COUPON_TYPE_FIRST_ORDER || $coupon?->coupon_type == self::COUPON_TYPE_DISCOUNT_ON_PURCHASE) {
            if (! ($productTotal <= 0 || $couponDiscount > $productTotal || $coupon['min_purchase'] > $productTotal)) {
                $discount = $coupon->discount_type == self::DISCOUNT_TYPE_PERCENTAGE
                    ? min(($productTotal * $couponDiscount) / 100, $coupon['max_discount'])
                    : $couponDiscount;
            }
        } elseif ($coupon?->coupon_type == self::COUPON_TYPE_FREE_DELIVERY) {
            $discount = $shippingCost;
        }

        if ($discount > 0) {
            return [
                'status' => true,
                'discount' => $discount,
                'coupon_type' => $coupon?->coupon_type,
                'coupon_code' => $coupon?->code,
                'coupon_bearer' => $coupon?->coupon_bearer,
                'total_cart_amount' => $productTotal,
            ];
        }

        return $emptySummary;
    }

    /**
     * @param  array<string, array<string, mixed>>  $editedProducts
     * @return array{amount: mixed, percentage: mixed, amount_need: mixed, shipping_cost_saved: mixed, status: int, responsibility: string}
     */
    public function calculateEditFreeDelivery(object|array $order, array $editedProducts, float|int $shippingCost, ?object $seller, mixed $inHouseFreeDeliveryStatus): array
    {
        $freeDelivery = [
            'amount' => 0,
            'percentage' => 0,
            'amount_need' => 0,
            'shipping_cost_saved' => 0,
        ];

        $freeDelivery['status'] = (int) (getWebConfig(name: 'free_delivery_status') ?? 0);
        $freeDelivery['responsibility'] = (string) getWebConfig(name: 'free_delivery_responsibility');
        $inHouseOverAmount = (float) getWebConfig(name: 'free_delivery_over_amount');
        $sellerOverAmount = (float) getWebConfig(name: 'free_delivery_over_amount_seller');
        $isInHouseFreeDeliveryActive = $inHouseFreeDeliveryStatus === null ? $inHouseOverAmount > 0 : (bool) $inHouseFreeDeliveryStatus;

        if (! $freeDelivery['status'] || empty($editedProducts)) {
            return $freeDelivery;
        }

        if ($order['seller_is'] == self::SELLER_IS_ADMIN) {
            $freeDelivery['amount'] = $inHouseOverAmount;
            $freeDelivery['status'] = $isInHouseFreeDeliveryActive && $inHouseOverAmount > 0 ? 1 : 0;
        } else {
            $freeDelivery['status'] = $seller?->free_delivery_status ?? 0;

            if ($freeDelivery['responsibility'] == self::FREE_DELIVERY_BY_ADMIN) {
                $freeDelivery['amount'] = $sellerOverAmount;
                $freeDelivery['status'] = $sellerOverAmount > 0 ? 1 : 0;
            }

            if ($freeDelivery['responsibility'] == self::FREE_DELIVERY_BY_SELLER && $freeDelivery['status'] == 1) {
                $freeDelivery['amount'] = $seller?->free_delivery_over_amount;
                $freeDelivery['status'] = $seller?->free_delivery_over_amount > 0 ? 1 : 0;
            }
        }

        $itemsTotal = $this->calculateEditItemsTotal(editedProducts: $editedProducts);
        $freeDelivery['amount_need'] = $freeDelivery['amount'] - $itemsTotal;
        $freeDelivery['percentage'] = ($freeDelivery['amount'] > 0) && $itemsTotal > 0 && ($freeDelivery['amount'] >= $itemsTotal)
            ? number_format(($itemsTotal / $freeDelivery['amount']) * 100)
            : 100;

        if ($freeDelivery['status'] == 1 && $freeDelivery['percentage'] == 100) {
            $freeDelivery['shipping_cost_saved'] = $shippingCost;
        }

        return $freeDelivery;
    }

    /** @return array{shipping_cost: float|int, order_amount: float|int, is_shipping_free: bool, coupon_discount: mixed, refer_and_earn_discount: mixed, free_delivery_discount: float|int} */
    public function calculateEditAmountData(object|array $order, array $couponSummary, array $freeDelivery, float|int $shippingCost): array
    {
        $orderAmount = $couponSummary['total_cart_amount'] - $couponSummary['discount'] + $shippingCost;
        $isFreeShippingOverAmount = $this->isFreeShippingOverAmount(couponSummary: $couponSummary, freeDelivery: $freeDelivery);

        if ($isFreeShippingOverAmount) {
            $orderAmount = $orderAmount - $freeDelivery['shipping_cost_saved'];
        }

        $isFreeDeliveryCoupon = $couponSummary['coupon_type'] == self::COUPON_TYPE_FREE_DELIVERY;

        return [
            'shipping_cost' => $shippingCost,
            'order_amount' => $orderAmount,
            'is_shipping_free' => $isFreeShippingOverAmount || $isFreeDeliveryCoupon,
            'coupon_discount' => $couponSummary['discount'] ?? 0,
            'refer_and_earn_discount' => $order['refer_and_earn_discount'] ?? 0,
            'free_delivery_discount' => $isFreeShippingOverAmount || $isFreeDeliveryCoupon ? $shippingCost : 0,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $editedProducts
     * @return array<string, array{total_discounted_price: float|int, applied_discounted_amount: float|int, total_shipping_cost: mixed, applicable_shipping_amount: float|int}>
     */
    public function getEditTaxLineBases(array $editedProducts, array $amountData): array
    {
        $totalDiscount = $amountData['is_shipping_free'] == 1
            ? $amountData['coupon_discount'] + $amountData['refer_and_earn_discount']
            : $amountData['coupon_discount'] + $amountData['free_delivery_discount'] + $amountData['refer_and_earn_discount'];

        $totalDiscountedProductPrice = 0;
        $totalApplicableShippingAmount = 0;
        foreach ($editedProducts as $editedProduct) {
            $discountedLinePrice = ($editedProduct['price'] - $editedProduct['discount']) * $editedProduct['qty'];
            $totalDiscountedProductPrice += $discountedLinePrice;
            $totalApplicableShippingAmount += $editedProduct['product_type'] == self::PRODUCT_TYPE_DIGITAL ? 0 : $discountedLinePrice;
        }

        $taxLineBases = [];
        foreach ($editedProducts as $index => $editedProduct) {
            if (! $editedProduct['active_product']) {
                continue;
            }

            $discountedLinePrice = ($editedProduct['price'] - $editedProduct['discount']) * $editedProduct['qty'];
            $taxLineBases[$index] = [
                'total_discounted_price' => $discountedLinePrice,
                'applied_discounted_amount' => $totalDiscount > 0 ? ($totalDiscount * $discountedLinePrice) / $totalDiscountedProductPrice : 0,
                'total_shipping_cost' => $amountData['is_shipping_free'] == 1 ? $amountData['shipping_cost'] - $amountData['free_delivery_discount'] : $amountData['shipping_cost'],
                'applicable_shipping_amount' => $totalApplicableShippingAmount,
            ];
        }

        return $taxLineBases;
    }

    /**
     * @param  array<string, array<string, mixed>>  $editedProducts
     * @param  array<string, array{total_discounted_price: float|int, applied_discounted_amount: float|int}>  $taxLineBases
     * @param  array<string, array{applied_tax_amount: float|int, applied_tax_ids: array, applied_shipping_tax_amount: float|int, applied_shipping_tax_ids: array}>  $lineTaxes
     * @return array<string, mixed>
     */
    public function buildEditTaxSummary(array $editedProducts, array $summaryData, array $taxLineBases, array $lineTaxes): array
    {
        $appliedTaxTotal = 0;
        $summaryData['applied_tax_cart_list'] = [];

        foreach ($editedProducts as $index => $editedProduct) {
            if (! $editedProduct['active_product']) {
                continue;
            }

            $isDigital = $editedProduct['product_type'] == self::PRODUCT_TYPE_DIGITAL;
            $lineTax = $lineTaxes[$index];

            $appliedTaxTotal += $lineTax['applied_tax_amount'];
            $appliedTaxTotal += $isDigital ? 0 : $lineTax['applied_shipping_tax_amount'];

            $summaryData['applied_tax_cart_list'][] = [
                'product_id' => $editedProduct['product_id'],
                'variant' => $editedProduct['variant'] ?? '',
                'quantity' => $editedProduct['qty'],
                'price' => $editedProduct['price'],
                'discount' => $editedProduct['discount'],
                'seller_id' => $editedProduct['seller_id'],
                'seller_is' => $editedProduct['seller_is'],
                'discounted_price' => ($editedProduct['price'] - $editedProduct['discount']),
                'total_discounted_price' => $taxLineBases[$index]['total_discounted_price'],
                'applied_discounted_amount' => $taxLineBases[$index]['applied_discounted_amount'],
                'applied_tax_amount' => $lineTax['applied_tax_amount'],
                'applied_shipping_cost_tax' => $isDigital ? 0 : $lineTax['applied_shipping_tax_amount'],
                'applied_tax_ids' => $lineTax['applied_tax_ids'],
                'applied_shipping_cost_tax_ids' => $isDigital ? [] : $lineTax['applied_shipping_tax_ids'],
            ];
        }

        $summaryData['total_tax_amount'] = $appliedTaxTotal;
        $summaryData['order_amount_with_tax'] = $summaryData['order_amount'] + $appliedTaxTotal;

        return $summaryData;
    }

    public function calculateEditAdminCommission(object|array $order, ?object $seller, float|int $totalCartAmount): float
    {
        if ($order['seller_is'] != self::SELLER_IS_SELLER) {
            return 0.0;
        }

        $commissionPercentage = (isset($seller) && $seller['sales_commission_percentage'] !== null)
            ? $seller['sales_commission_percentage']
            : getWebConfig(name: 'sales_commission');

        return (float) str_replace(',', '', number_format(($totalCartAmount / 100) * $commissionPercentage, 2));
    }

    /**
     * @param  array<string, array<string, mixed>>  $editedProducts
     * @return array{status: string, order_data: array<string, mixed>, order_amount: float|int, edit_due_amount: float|int, order_return_amount: float|int, tax_summary: array<string, mixed>, coupon_summary: array<string, mixed>}
     */
    public function buildEditOrderSummary(object|array $order, array $editedProducts, array $couponSummary, array $freeDelivery, array $taxSummary, float $adminCommission): array
    {
        $isFreeShippingOverAmount = $this->isFreeShippingOverAmount(couponSummary: $couponSummary, freeDelivery: $freeDelivery);
        $orderAmount = $taxSummary['order_amount'] + $taxSummary['total_tax_amount'];
        $initialOrderAmount = $order['order_amount'];

        $orderDueAmount = $initialOrderAmount < $orderAmount ? $orderAmount - $initialOrderAmount : 0;
        $orderReturnAmount = $initialOrderAmount > $orderAmount ? $initialOrderAmount - $orderAmount : 0;

        $taxConfig = self::getTaxSystemType();

        $orderData = [
            'discount_amount' => $couponSummary['discount'] ?? 0,
            'discount_type' => ($couponSummary['discount'] ?? 0) <= 0 ? null : self::DISCOUNT_TYPE_COUPON,
            'coupon_code' => $couponSummary['coupon_code'],
            'coupon_discount_bearer' => $couponSummary['coupon_bearer'],
            'tax_type' => $taxConfig['SystemTaxVatType'],
            'tax_model' => $taxConfig['is_included'] ? self::TAX_MODEL_INCLUDE : self::TAX_MODEL_EXCLUDE,
            'admin_commission' => $adminCommission,
            'edit_due_amount' => $orderDueAmount,
            'edit_return_amount' => $orderReturnAmount,
            'total_tax_amount' => $taxSummary['total_tax_amount'],
            'shipping_cost' => $taxSummary['shipping_cost'],
            'extra_discount' => $isFreeShippingOverAmount ? $freeDelivery['shipping_cost_saved'] : 0,
            'extra_discount_type' => $isFreeShippingOverAmount ? self::EXTRA_DISCOUNT_FREE_SHIPPING : null,
            'free_delivery_bearer' => $isFreeShippingOverAmount ? $freeDelivery['responsibility'] : null,
            'is_shipping_free' => $isFreeShippingOverAmount ? 1 : 0,
            'edited_status' => 1,
            'updated_at' => Carbon::now(),
        ];

        if ($order['payment_method'] == self::PAYMENT_METHOD_CASH_ON_DELIVERY) {
            $orderDueAmount = 0;
            $orderReturnAmount = 0;

            $orderData['order_amount'] = $orderAmount;
            $orderData['init_order_amount'] = $orderAmount;
            $orderData['edit_due_amount'] = $orderDueAmount;
            $orderData['edit_return_amount'] = $orderReturnAmount;
        }

        if (collect($editedProducts)->where('product_type', self::PRODUCT_TYPE_PHYSICAL)->count() <= 0) {
            $orderData['delivery_man_id'] = null;
            $orderData['deliveryman_assigned_at'] = null;
            $orderData['deliveryman_charge'] = 0;
        }

        return [
            'status' => self::STATUS_SUCCESS,
            'order_data' => $orderData,
            'order_amount' => $orderAmount,
            'edit_due_amount' => $orderDueAmount,
            'order_return_amount' => $orderReturnAmount,
            'tax_summary' => $taxSummary,
            'coupon_summary' => $couponSummary,
        ];
    }

    /** @return array{minimum_order_amount: mixed, amount: float|int, status: string, shop_name: ?string} */
    public function checkEditMinimumOrderAmount(object|array $order, float|int|null $totalCartAmount, ?object $seller): array
    {
        $status = self::STATUS_SUCCESS;
        $amount = 0;
        $minimumOrderAmount = 0;
        $shopName = null;

        if (getWebConfig(name: 'minimum_order_amount_status') ?? 0) {
            if ($order['seller_is'] == self::SELLER_IS_ADMIN) {
                $minimumOrderAmount = getWebConfig(name: 'minimum_order_amount');
                $shopName = getInHouseShopConfig(key: 'name');
            } else {
                $shopName = $seller?->shop?->name ?? '';
                $minimumOrderAmount = (getWebConfig(name: 'minimum_order_amount_by_seller') ?? 0) ? ($seller?->minimum_order_amount ?? 0) : 0;
            }

            $status = $minimumOrderAmount > $totalCartAmount ? self::STATUS_ERROR : self::STATUS_SUCCESS;
            $amount = $amount + $totalCartAmount;
        }

        return [
            'minimum_order_amount' => $minimumOrderAmount ?? 0,
            'amount' => $amount ? floatval($amount) : 0,
            'status' => $status,
            'shop_name' => $shopName,
        ];
    }

    public function hasRedeemedLoyaltyPointsBeyondBalance(object|array $order, ?object $customer, float|int $deliveredLoyaltyPoints): bool
    {
        return ! $order['is_guest'] && $customer && ($customer['loyalty_point'] < $deliveredLoyaltyPoints);
    }

    public function calculateEditedLineAmount(array $editedProduct): float|int
    {
        return (($editedProduct['price'] ?? 0) - ($editedProduct['discount'] ?? 0)) * ($editedProduct['qty'] ?? 0);
    }

    /** @return array<string, mixed> */
    public function buildEditHistoryData(object|array $order, array $editSummary, array $editor): array
    {
        return [
            'order_id' => $order['id'],
            'edit_by' => $editor['edit_by'],
            'edited_user_id' => $editor['edited_user_id'],
            'edited_user_name' => $editor['edited_user_name'],
            'order_amount' => $editSummary['order_amount'],
            'order_due_amount' => $editSummary['edit_due_amount'],
            'order_due_payment_status' => $editSummary['edit_due_amount'] > 0 ? self::PAYMENT_STATUS_UNPAID : '',
            'order_return_amount' => $editSummary['order_return_amount'],
            'order_return_payment_status' => $editSummary['order_return_amount'] > 0 ? self::RETURN_PAYMENT_STATUS_PENDING : '',
        ];
    }

    private function isFreeShippingOverAmount(array $couponSummary, array $freeDelivery): bool
    {
        return $freeDelivery['status']
            && $freeDelivery['shipping_cost_saved'] > 0
            && $couponSummary['coupon_type'] != self::COUPON_TYPE_FREE_DELIVERY;
    }

    private function hasPhysicalProduct(object|array $order): bool
    {
        foreach ($order?->details?->pluck('product_details') as $productDetail) {
            $product = json_decode($productDetail ?? '', true);

            if ($product && (! isset($product['product_type']) || $product['product_type'] == self::PRODUCT_TYPE_PHYSICAL)) {
                return true;
            }
        }

        return false;
    }

    private function generateProductIndex(mixed $id, ?string $variant = null): string
    {
        return $id.'-'.(! empty($variant) ? $variant : self::SINGLE_VARIANT);
    }
}
