<?php

namespace App\Services\New\Pos;

use App\Enums\SessionKey;
use App\Utils\CartManager;
use Carbon\Carbon;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class PosCartService
{
    use VatTaxManagement;

    public const ADJUSTMENT_COUPON_REMOVED_MIN_PURCHASE = 'coupon_removed_min_purchase';

    public const ADJUSTMENT_COUPON_REMOVED_EXCEEDS_TOTAL = 'coupon_removed_exceeds_total';

    public const ADJUSTMENT_EXTRA_DISCOUNT_REMOVED = 'extra_discount_removed';

    public const ADJUSTMENT_COUPON_REMOVED_CUSTOMER_CHANGED = 'coupon_removed_customer_changed';

    public const COUPON_APPLIED = 'success';

    public const COUPON_BELOW_MIN_PURCHASE = 'min_purchase';

    public const COUPON_EXCEEDS_TOTAL = 'amount_low';

    public const COUPON_KEYS = [
        'coupon_code',
        'coupon_discount',
        'coupon_title',
        'coupon_bearer',
        'coupon_min_purchase',
        'coupon_discount_type',
        'coupon_amount',
        'coupon_max_discount',
    ];

    public const EXTRA_DISCOUNT_KEYS = ['ext_discount', 'ext_discount_type'];

    public const CART_LEVEL_KEYS = [...self::COUPON_KEYS, ...self::EXTRA_DISCOUNT_KEYS, 'add_to_cart_time'];

    public const WALK_IN_CART_PREFIX = 'walk-in-customer-';

    public const SAVED_CUSTOMER_CART_PREFIX = 'saved-customer-';

    public const WALK_IN_CUSTOMER_NAME = 'Walk-In Customer';

    public const WALK_IN_CUSTOMER_ID = 0;

    public const EXTRA_DISCOUNT_TYPE_PERCENT = 'percent';

    public const EXTRA_DISCOUNT_TYPE_AMOUNT = 'amount';

    public const PRODUCT_TYPE_DIGITAL = 'digital';

    public const PRODUCT_TYPE_PHYSICAL = 'physical';

    public const DIGITAL_QUANTITY_LIMIT = 100;

    public const DIGITAL_CART_LINE_QUANTITY_LIMIT = 9999;

    public const PAYMENT_TYPE_CASH = 'cash';

    public const PAYMENT_TYPE_CARD = 'card';

    public const PAYMENT_TYPE_WALLET = 'wallet';

    public const PLACEMENT_REJECTED_CART_CHANGED = 'cart_changed';

    public const PLACEMENT_REJECTED_ZERO_AMOUNT = 'zero_amount';

    public const PLACEMENT_REJECTED_WALLET_WITHOUT_CUSTOMER = 'wallet_without_customer';

    public const PLACEMENT_REJECTED_PAID_AMOUNT_SHORT = 'paid_amount_short';

    public const PLACEMENT_REJECTED_WALLET_DISABLED = 'wallet_disabled';

    public const PLACEMENT_REJECTED_WALLET_BALANCE_SHORT = 'wallet_balance_short';

    public const PLACEMENT_REJECTED_WALLET_DEBIT_FAILED = 'wallet_debit_failed';

    private const WALK_IN_TOKEN_LENGTH = 10;

    private const COUPON_TYPE_PERCENTAGE = 'percentage';

    private const AMOUNT_TOLERANCE = 0.01;

    private const MONEY_DECIMAL_PLACES = 2;

    public function __construct(protected readonly Session $session) {}

    public function getVariantData(array $input, object $product, ?string $colorName = null): array
    {
        $variations = $this->getProductVariations(product: $product);
        $hasVariations = ! empty($variations);
        $variation = $this->makeVariation(
            input: $input,
            colorName: $colorName,
            choiceOptions: json_decode($product['choice_options'])
        );

        $selectedVariation = $this->findVariationRow(variations: $variations, variant: $variation);

        if ($selectedVariation !== null) {
            $unitPrice = $selectedVariation['price'];
            $quantity = $selectedVariation['qty'];
        } else {
            $unitPrice = $product['unit_price'];
            $quantity = $product['current_stock'];
        }

        $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $unitPrice, from: 'panel');
        $price = $unitPrice - $discount;

        $requestQuantity = (int) ($input['quantity'] ?? 0);

        $inCartStatus = 0;
        $inCartData = null;

        if ($product['product_type'] == self::PRODUCT_TYPE_DIGITAL && array_key_exists('variant_key', $input)) {
            $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $product['unit_price'], from: 'panel');
            $quantity = $product['current_stock'];
            foreach ($product['digitalVariation'] as $variant) {
                if ($variant['variant_key'] == $input['variant_key']) {
                    $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $variant['price'], from: 'panel');
                    $price = $variant['price'] - $discount;
                    $unitPrice = $variant['price'];
                    $variation = $variant['variant_key'];
                }
            }
        }

        foreach ($this->getCurrentCartItems() as $cart) {
            if (is_array($cart) && $cart['id'] == $product['id'] && $cart['variant'] == $variation) {
                $inCartStatus = 1;
                $cartDiscount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $cart['price'], from: 'panel');
                $price = ($cart['price'] - $cartDiscount);
                $inCartData = [
                    'price' => setCurrencySymbol(amount: usdToDefaultCurrency(amount: $price * $requestQuantity)),
                    'discount' => usdToDefaultCurrency($cartDiscount),
                    'quantity' => (int) $cart['quantity'],
                    'variant' => $cart['variant'],
                    'id' => $cart['id'],
                ];
                $requestQuantity = (int) ($input['quantity_in_cart'] ?? $cart['quantity']);
            }
        }
        if ($product['product_type'] == self::PRODUCT_TYPE_PHYSICAL) {
            $requestQuantity = max(1, min($requestQuantity, (int) $quantity));
        }
        $discountType = getProductPriceByType(product: $product, type: 'discount_type', result: 'string');
        $selectedStock = $this->getSelectedStock(product: $product, variant: $variation);
        $allVariationsOutOfStock = $hasVariations ? empty(array_filter($variations, fn ($v) => ($v['qty'] ?? 0) > 0)) : ($quantity < 1);

        return [
            'price' => setCurrencySymbol(amount: usdToDefaultCurrency(amount: $price * $requestQuantity)),
            'discount' => usdToDefaultCurrency($discount),
            'discount_amount' => $discount,
            'discount_type' => $discountType,
            'discount_text' => $discountType == 'flat' ? translate('Save').' '.setCurrencySymbol(amount: usdToDefaultCurrency(amount: $discount), currencyCode: getCurrencyCode()) : getProductPriceByType(product: $product, type: 'discount', result: 'value').'% '.translate('OFF'),
            'quantity' => $product['product_type'] == self::PRODUCT_TYPE_PHYSICAL ? $quantity : self::DIGITAL_QUANTITY_LIMIT,
            'inCartStatus' => $inCartStatus,
            'inCartData' => $inCartData,
            'requestQuantity' => $requestQuantity,
            'total_unit_price' => setCurrencySymbol(amount: usdToDefaultCurrency(amount: $unitPrice)),
            'discounted_unit_price' => setCurrencySymbol(amount: usdToDefaultCurrency(amount: $unitPrice - $discount)),
            'allVariationsOutOfStock' => $allVariationsOutOfStock,
            'selectedStock' => $selectedStock,
            'isSelectionAvailable' => $this->isSelectionAvailable(product: $product, selectedStock: $selectedStock),
        ];
    }

    public function getQuickViewSelection(object $product, array $colorNames): array
    {
        $combinations = $this->getSelectionCombinations(product: $product, colorNames: $colorNames);
        $cartLine = $this->findLatestCartLine(productId: (int) $product['id']);
        $isDigital = $product['product_type'] === self::PRODUCT_TYPE_DIGITAL;

        $inCartCombination = match (true) {
            $cartLine === null => null,
            $isDigital => $combinations[0],
            default => collect($combinations)->firstWhere('variant', (string) $cartLine['variant']),
        };
        $cartLine = $inCartCombination === null ? null : $cartLine;
        $selection = $inCartCombination ?? $this->findFirstAvailableCombination(product: $product, combinations: $combinations);
        $selectedStock = $this->getSelectedStock(product: $product, variant: $selection['variant']);

        return [
            'colorCode' => $selection['colorCode'],
            'choiceValues' => $selection['choiceValues'],
            'digitalVariantKey' => $isDigital ? (($cartLine['variant'] ?? null) ?: $this->getFirstDigitalVariantKey(product: $product)) : null,
            'isInCart' => $cartLine !== null,
            'inCartQuantity' => (int) ($cartLine['quantity'] ?? 1),
            'selectedStock' => $selectedStock,
            'isSelectionAvailable' => $this->isSelectionAvailable(product: $product, selectedStock: $selectedStock),
            'quantityMax' => $isDigital ? self::DIGITAL_QUANTITY_LIMIT : $selectedStock,
            'isQuantityIncreaseDisabled' => ! $isDigital && $selectedStock <= 1,
        ];
    }

    public function makeVariation(array $input, ?string $colorName, array $choiceOptions): string
    {
        return $this->buildVariantString(
            colorName: $colorName ?: null,
            choiceValues: array_map(fn ($choice) => $input[$choice->name] ?? null, $choiceOptions),
        );
    }

    public function isVariationMissing(object $product, string $variant): bool
    {
        $variations = $this->getProductVariations(product: $product);

        return $product['product_type'] !== self::PRODUCT_TYPE_DIGITAL
            && $variations !== []
            && $this->findVariationRow(variations: $variations, variant: $variant) === null;
    }

    public function getVariationPrice(array $variation, string $variant, float $productPrice): float
    {
        return (float) ($this->findVariationRow(variations: $variation, variant: $variant)['price'] ?? $productPrice);
    }

    public function getRemainingStock(string $variant, array $variation, int $productQty, int $quantity): int
    {
        $row = $this->findVariationRow(variations: $variation, variant: $variant);

        return ($row === null ? $productQty : (int) $row['qty']) - $quantity;
    }

    public function getCurrentCartId(): string
    {
        if (! $this->session->has(SessionKey::CURRENT_USER)) {
            $this->openNewWalkInCart();
        }

        return (string) $this->session->get(SessionKey::CURRENT_USER);
    }

    public function findCurrentCartId(): string
    {
        return (string) $this->session->get(SessionKey::CURRENT_USER);
    }

    public function getCurrentCartItems(): array
    {
        $cart = $this->session->get($this->getCurrentCartId());

        return is_array($cart) ? $cart : [];
    }

    public function getCart(string $cartId): mixed
    {
        return $this->session->get($cartId);
    }

    public function detachCart(string $cartId): void
    {
        $this->session->forget($cartId);
    }

    public function restoreCart(string $cartId, mixed $cart): void
    {
        $this->session->put($cartId, $cart);
    }

    public function getCurrentCustomerId(): int
    {
        return $this->getCartCustomerId(cartId: $this->getCurrentCartId()) ?? self::WALK_IN_CUSTOMER_ID;
    }

    public function getCartCustomerId(string $cartId): ?int
    {
        return Str::contains($cartId, self::SAVED_CUSTOMER_CART_PREFIX) ? (int) explode('-', $cartId)[2] : null;
    }

    public function getCustomerCartId(int $customerId): string
    {
        if ($customerId !== self::WALK_IN_CUSTOMER_ID) {
            return self::SAVED_CUSTOMER_CART_PREFIX.$customerId;
        }

        $currentCartId = $this->getCurrentCartId();

        return Str::startsWith($currentCartId, self::WALK_IN_CART_PREFIX) ? $currentCartId : $this->generateWalkInCartId();
    }

    public function generateWalkInCartId(): string
    {
        do {
            $cartId = self::WALK_IN_CART_PREFIX.Str::lower(Str::random(self::WALK_IN_TOKEN_LENGTH));
        } while (in_array($cartId, $this->session->get(SessionKey::CART_NAME) ?? [], true) || $this->session->has($cartId));

        return $cartId;
    }

    public function registerCart(string $cartId): void
    {
        if (! $this->session->has(SessionKey::CURRENT_USER)) {
            $this->session->put(SessionKey::CURRENT_USER, $cartId);
        }
        if (! $this->session->has(SessionKey::CART_NAME)) {
            $this->addCartName(cartId: $cartId);
        }
    }

    public function openNewWalkInCart(): void
    {
        $cartId = $this->generateWalkInCartId();
        $this->session->put(SessionKey::CURRENT_USER, $cartId);
        if (! in_array($cartId, $this->session->get(SessionKey::CART_NAME) ?? [])) {
            $this->addCartName(cartId: $cartId);
        }
    }

    public function normalizeCurrentCart(): void
    {
        $cart = $this->getCurrentCartItems();
        $this->session->put($this->getCurrentCartId(), $this->buildCartFromLines(lines: $cart, cart: $cart));
    }

    public function emptyCurrentCart(): string
    {
        $cartId = $this->getCurrentCartId();
        $this->session->forget($cartId);
        $this->registerCart(cartId: $cartId);

        return $cartId;
    }

    public function clearAllCarts(): bool
    {
        $cartId = $this->getCurrentCartId();
        if (empty($this->session->get($cartId, []))) {
            return false;
        }

        $this->session->forget(SessionKey::CART_NAME);
        $this->session->forget($cartId);
        $this->session->forget(SessionKey::CURRENT_USER);

        return true;
    }

    public function getLastOrderId(): mixed
    {
        return $this->session->get(SessionKey::LAST_ORDER);
    }

    public function rememberLastOrder(int $orderId): void
    {
        $this->session->put(SessionKey::LAST_ORDER, $orderId);
    }

    public function addLine(object $product, int $quantity, float $price, float $discount, string $variant, array $variations): array
    {
        $cartId = $this->getCurrentCartId();
        $line = $this->buildLine(product: $product, quantity: $quantity, price: $price, discount: $discount, variant: $variant, variations: $variations);
        $cart = $this->cartToArray(cart: $this->session->get($cartId));
        $this->session->put($cartId, $this->withAddedTime(cart: $this->buildCartFromLines(lines: [...array_filter($cart, 'is_array'), $line], cart: $cart)));

        return $line;
    }

    public function findCartLineKey(int $productId, string $variant): int|string|null
    {
        foreach ($this->cartToArray(cart: $this->session->get($this->getCurrentCartId())) as $key => $cartItem) {
            if (is_array($cartItem) && $cartItem['id'] == $productId && $cartItem['variant'] == $variant) {
                return $key;
            }
        }

        return null;
    }

    public function replaceLine(int|string $lineKey, object $product, int $quantity, float $price, float $discount, string $variant, array $variations): array
    {
        $cartId = $this->getCurrentCartId();
        $cart = $this->cartToArray(cart: $this->session->get($cartId));
        $line = $this->buildLine(product: $product, quantity: $quantity, price: $price, discount: $discount, variant: $variant, variations: $variations);
        $lines = $cart;
        unset($lines[$lineKey]);
        $this->session->put($cartId, $this->withAddedTime(cart: $this->buildCartFromLines(lines: [...$lines, $line], cart: $cart)));

        return $line;
    }

    public function removeLine(int $productId, ?string $variant): void
    {
        $cartId = $this->getCurrentCartId();
        $cart = $this->cartToArray(cart: $this->session->get($cartId));
        $remainingLines = [];
        foreach ($cart as $cartItem) {
            if (is_array($cartItem) && ($cartItem['id'] != $productId || $cartItem['variant'] != $variant)) {
                $remainingLines[] = $cartItem;
            }
        }
        $this->session->put($cartId, $this->buildCartFromLines(lines: $remainingLines, cart: $cart));
    }

    public function updateLineQuantity(int $productId, ?string $variant, int $quantity, object $product): int
    {
        $remainingStock = 0;
        $cartId = $this->getCurrentCartId();
        $cart = $this->getCurrentCartItems();
        $keeper = [];

        foreach ($cart as $item) {
            if (! is_array($item)) {
                continue;
            }

            $isTargetLine = (! empty($item['variant']) && $item['variant'] == $variant && $item['id'] == $productId)
                || (empty($variant) && $item['id'] == $productId);

            if ($isTargetLine) {
                $variationRow = $this->findVariationRow(
                    variations: $this->getProductVariations(product: $product),
                    variant: $this->buildVariantString(colorName: null, choiceValues: $item['variations'] ?: []),
                );
                $productQuantity = $variationRow === null ? $product['current_stock'] : (int) $variationRow['qty'];

                $remainingStock = $productQuantity - $quantity;
                if ($product['product_type'] == self::PRODUCT_TYPE_PHYSICAL && $remainingStock < 0) {
                    return $remainingStock;
                }
                $item['quantity'] = $quantity;
            }
            $keeper[] = $item;
        }
        $rebuiltCart = $this->buildCartFromLines(lines: $keeper, cart: $cart);
        $rebuiltCart['add_to_cart_time'] = Carbon::now();
        $this->session->put($cartId, $rebuiltCart);

        return $remainingStock;
    }

    public function getProductCounts(): array
    {
        $productCounts = [];
        foreach ($this->getCurrentCartItems() as $key => $item) {
            if ($key === 'add_to_cart_time' || ! is_array($item) || ! isset($item['id'])) {
                continue;
            }
            $productCounts[$item['id']] = ($productCounts[$item['id']] ?? 0) + ($item['quantity'] ?? 0);
        }

        return $productCounts;
    }

    public function hasCartLines(string $cartId): bool
    {
        return $this->getUniqueCartLines(cart: $this->session->get($cartId))->isNotEmpty();
    }

    public function hasDigitalProduct(string $cartId): bool
    {
        foreach ($this->cartToArray(cart: $this->session->get($cartId)) as $item) {
            if (is_array($item) && $item['productType'] == self::PRODUCT_TYPE_DIGITAL) {
                return true;
            }
        }

        return false;
    }

    public function getUniqueCartLines(mixed $cart): Collection
    {
        $lineSignatures = [];

        return collect($this->cartToArray(cart: $cart))
            ->filter(fn ($cartItem) => is_array($cartItem))
            ->filter(function (array $cartItem) use (&$lineSignatures): bool {
                $signature = $cartItem['id'].'-'.$cartItem['productType'].'-Q'.$cartItem['quantity'].'-'.($cartItem['variant'] ?: 'single');
                $isDuplicate = in_array($signature, $lineSignatures);
                $lineSignatures[] = $signature;

                return ! $isDuplicate;
            });
    }

    public function getCustomerCartLines(mixed $cart, int|string $customerId): Collection
    {
        return $this->getUniqueCartLines(cart: $cart)->filter(fn (array $cartItem): bool => $cartItem['customerId'] == $customerId)->values();
    }

    public function getCartProductIds(array $cartIds): array
    {
        $productIds = [];
        foreach ($cartIds as $cartId) {
            $productIds = [...$productIds, ...$this->getUniqueCartLines(cart: $this->session->get($cartId))->pluck('id')->all()];
        }

        return array_values(array_unique($productIds));
    }

    public function getCartCustomerIds(array $cartIds): array
    {
        $customerIds = array_map(fn (string $cartId): ?int => $this->getCartCustomerId(cartId: $cartId), $cartIds);

        return array_values(array_unique(array_filter($customerIds, fn (?int $customerId): bool => $customerId !== null)));
    }

    public function getCartLineQuantityLimit(object $product, array $cartItem): int
    {
        if ($product['product_type'] === self::PRODUCT_TYPE_DIGITAL) {
            return self::DIGITAL_CART_LINE_QUANTITY_LIMIT;
        }

        return $this->getSelectedStock(product: $product, variant: (string) ($cartItem['variant'] ?? ''));
    }

    public function removeExtraDiscount(string $cartId): void
    {
        $this->session->put($cartId, $this->withoutKeys(cart: $this->cartToArray(cart: $this->session->get($cartId)), keys: self::EXTRA_DISCOUNT_KEYS));
    }

    public function applyExtraDiscount(string $cartId, string $type, float $discount): ?float
    {
        $cart = $this->cartToArray(cart: $this->session->get($cartId));
        $couponDiscount = $cart['coupon_discount'] ?? 0;
        ['totalProductPrice' => $totalProductPrice, 'productDiscount' => $productDiscount] = $this->getLinePriceTotals(cart: $cart);

        $extraDiscount = $type === self::EXTRA_DISCOUNT_TYPE_PERCENT
            ? (($totalProductPrice - $productDiscount - $couponDiscount) / 100) * $discount
            : currencyConverter(amount: $discount);

        if ($totalProductPrice - $productDiscount - $couponDiscount - $extraDiscount < 0) {
            return null;
        }

        $cart['ext_discount'] = $type === self::EXTRA_DISCOUNT_TYPE_PERCENT ? $discount : currencyConverter(amount: $discount);
        $cart['ext_discount_type'] = $type;
        $this->session->put($cartId, $cart);

        return $extraDiscount;
    }

    public function applyCoupon(string $cartId, object $coupon, string $couponCode): string
    {
        $cart = $this->cartToArray(cart: $this->session->get($cartId));
        ['totalProductPrice' => $totalProductPrice, 'productDiscount' => $productDiscount] = $this->getLinePriceTotals(cart: $cart);

        if ($totalProductPrice < $coupon['min_purchase']) {
            return self::COUPON_BELOW_MIN_PURCHASE;
        }

        $couponDiscount = $this->calculateCouponDiscount(coupon: $coupon, totalProductPrice: $totalProductPrice, productDiscount: $productDiscount);

        $extraDiscount = 0;
        if (isset($cart['ext_discount_type']) && isset($cart['ext_discount'])) {
            $extraDiscount = $cart['ext_discount_type'] == self::EXTRA_DISCOUNT_TYPE_PERCENT
                ? (($totalProductPrice - $productDiscount - $couponDiscount) / 100) * $cart['ext_discount']
                : $cart['ext_discount'];
        }

        if ($totalProductPrice - $productDiscount - $couponDiscount - $extraDiscount < 0) {
            return self::COUPON_EXCEEDS_TOTAL;
        }

        $cart['coupon_code'] = $couponCode;
        $cart['coupon_discount'] = $couponDiscount;
        $cart['coupon_title'] = $coupon['title'];
        $cart['coupon_bearer'] = $coupon['coupon_bearer'];
        $cart['coupon_min_purchase'] = (float) $coupon['min_purchase'];
        $cart['coupon_discount_type'] = $coupon['discount_type'];
        $cart['coupon_amount'] = (float) $coupon['discount'];
        $cart['coupon_max_discount'] = (float) $coupon['max_discount'];
        $this->session->put($cartId, $cart);

        return self::COUPON_APPLIED;
    }

    public function refreshCartAdjustments(string $cartId): array
    {
        $cart = $this->cartToArray(cart: $this->session->get($cartId));

        if (array_filter($cart, 'is_array') === []) {
            $this->session->put($cartId, $this->withoutKeys(cart: $cart, keys: [...self::COUPON_KEYS, ...self::EXTRA_DISCOUNT_KEYS]));

            return [];
        }

        ['totalProductPrice' => $totalProductPrice, 'productDiscount' => $productDiscount] = $this->getLinePriceTotals(cart: $cart);
        $lineTotal = $totalProductPrice - $productDiscount;

        $adjustments = [];
        if (isset($cart['coupon_code'])) {
            $couponRemovalReason = null;
            if ($totalProductPrice < (float) ($cart['coupon_min_purchase'] ?? 0)) {
                $couponRemovalReason = self::ADJUSTMENT_COUPON_REMOVED_MIN_PURCHASE;
            } else {
                $cart['coupon_discount'] = $this->recomputeCouponDiscount(cart: $cart, lineTotal: $lineTotal);
                if ($cart['coupon_discount'] > $lineTotal) {
                    $couponRemovalReason = self::ADJUSTMENT_COUPON_REMOVED_EXCEEDS_TOTAL;
                }
            }
            if ($couponRemovalReason !== null) {
                $cart = $this->withoutKeys(cart: $cart, keys: self::COUPON_KEYS);
                $adjustments[] = $couponRemovalReason;
            }
        }

        if ($this->isAmountExtraDiscountAboveTotal(cart: $cart, remainingTotal: $lineTotal - ($cart['coupon_discount'] ?? 0))) {
            $cart = $this->withoutKeys(cart: $cart, keys: self::EXTRA_DISCOUNT_KEYS);
            $adjustments[] = self::ADJUSTMENT_EXTRA_DISCOUNT_REMOVED;
        }

        $this->session->put($cartId, $cart);

        return $adjustments;
    }

    public function getCurrentCustomerSummary(?object $customer): array
    {
        $cartId = $this->getCurrentCartId();
        $customerName = $this->isWalkInCart(cartId: $cartId)
            ? self::WALK_IN_CUSTOMER_NAME
            : $this->getCustomerContact(customer: $customer)['customerName'];

        return [
            'currentCustomer' => $customerName,
            'currentCustomerData' => $customer,
        ];
    }

    public function getCartSummary(string $cartId, Collection $products, ?object $customer): array
    {
        $customerData = $this->getCartCustomerData(cartId: $cartId, customer: $customer);

        $cartSummary = array_merge($customerData, $this->calculateCartItemsData(cartId: $cartId, customerId: $customerData['customerId'], products: $products));
        $cartSummary['payableAmount'] = $this->getPayableAmount(cartSummary: $cartSummary);

        return $cartSummary;
    }

    public function getPayableAmount(array $cartSummary): float
    {
        return round((float) usdToDefaultCurrency(amount: $cartSummary['total'] + $cartSummary['totalTax'] - $cartSummary['couponDiscount']), self::MONEY_DECIMAL_PLACES);
    }

    public function getCartDiscountedTotal(mixed $cart, Collection $products, int|string $customerId): float|int
    {
        $totalDiscountedPrice = 0;
        foreach ($this->cartToArray(cart: $cart) as $cartItem) {
            if (! is_array($cartItem)) {
                continue;
            }
            $product = $products->firstWhere('id', $cartItem['id']);
            if ($product && $cartItem['customerId'] == $customerId) {
                $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $cartItem['price'], from: 'panel');
                $totalDiscountedPrice += (($cartItem['price'] - $discount) * $cartItem['quantity']);
            }
        }

        return $totalDiscountedPrice;
    }

    public function calculateLineSubtotal(object|array $product, array $cartItem, int|float $totalDiscountedPrice, string $cartId): array
    {
        $cart = $this->cartToArray(cart: $this->session->get($cartId));
        $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $cartItem['price'], from: 'panel');
        $productSubtotal = (($cartItem['price'] - $discount) * $cartItem['quantity']);

        $couponDiscount = $cart['coupon_discount'] ?? 0;
        $productCouponDiscount = ($couponDiscount * $productSubtotal) / $totalDiscountedPrice;

        $extraDiscount = $cart['ext_discount'] ?? 0;
        $extraDiscountType = $cart['ext_discount_type'] ?? self::EXTRA_DISCOUNT_TYPE_AMOUNT;
        if ($extraDiscountType == self::EXTRA_DISCOUNT_TYPE_PERCENT && $extraDiscount > 0) {
            $extraDiscount = (($totalDiscountedPrice - $couponDiscount) * $extraDiscount) / 100;
        }
        $productExtraDiscount = ($extraDiscount * $productSubtotal) / $totalDiscountedPrice;

        $appliedTaxAmount = CartManager::getAppliedTaxAmount(
            product: $product,
            taxConfig: self::getTaxSystemType(),
            totalDiscountedPrice: $productSubtotal,
            appliedDiscountedAmount: $productCouponDiscount + $productExtraDiscount,
        );

        return [
            'countItem' => 1,
            'totalQuantity' => $cartItem['quantity'],
            'productSubtotal' => $productSubtotal,
            'subtotal' => $productSubtotal,
            'discountOnProduct' => $discount * $cartItem['quantity'],
            'couponDiscount' => $productCouponDiscount,
            'extraDiscount' => $productExtraDiscount,
            'appliedTaxAmount' => $appliedTaxAmount,
        ];
    }

    public function getOrderPlacementRejection(float $payableAmount, float $postedAmount, string $paymentType, float $paidAmount, int $customerId, bool $isWalletEnabled): ?string
    {
        if (abs($postedAmount - $payableAmount) > self::AMOUNT_TOLERANCE) {
            return self::PLACEMENT_REJECTED_CART_CHANGED;
        }

        if ($payableAmount <= 0) {
            return self::PLACEMENT_REJECTED_ZERO_AMOUNT;
        }

        if ($paymentType === self::PAYMENT_TYPE_WALLET && ! $isWalletEnabled) {
            return self::PLACEMENT_REJECTED_WALLET_DISABLED;
        }

        if ($paymentType === self::PAYMENT_TYPE_WALLET && $customerId === self::WALK_IN_CUSTOMER_ID) {
            return self::PLACEMENT_REJECTED_WALLET_WITHOUT_CUSTOMER;
        }

        if ($paymentType === self::PAYMENT_TYPE_CASH && round($paidAmount, self::MONEY_DECIMAL_PLACES) < round($payableAmount, self::MONEY_DECIMAL_PLACES)) {
            return self::PLACEMENT_REJECTED_PAID_AMOUNT_SHORT;
        }

        return null;
    }

    protected function addCartName(string $cartId): void
    {
        $cartNames = $this->session->get(SessionKey::CART_NAME, []);
        $cartNames[] = $cartId;
        $this->session->put(SessionKey::CART_NAME, $cartNames);
    }

    protected function buildCartFromLines(array $lines, mixed $cart): array
    {
        $cart = $this->cartToArray(cart: $cart);
        $rebuiltCart = array_values(array_filter($lines, 'is_array'));
        foreach (self::CART_LEVEL_KEYS as $key) {
            if (array_key_exists($key, $cart)) {
                $rebuiltCart[$key] = $cart[$key];
            }
        }

        return $rebuiltCart;
    }

    protected function cartToArray(mixed $cart): array
    {
        if ($cart instanceof Collection) {
            return $cart->all();
        }

        return is_array($cart) ? $cart : [];
    }

    protected function isWalkInCart(string $cartId): bool
    {
        return Str::contains($cartId, self::WALK_IN_CART_PREFIX);
    }

    private function buildLine(object $product, int $quantity, float $price, float $discount, string $variant, array $variations): array
    {
        return [
            'id' => $product['id'],
            'customerId' => $this->getCurrentCustomerId(),
            'customerOnHold' => false,
            'quantity' => $quantity,
            'price' => $price,
            'name' => $product['name'],
            'productType' => $product['product_type'],
            'image' => $product->thumbnail_full_url,
            'discount' => $discount,
            'variant' => $variant,
            'variations' => $variations,
        ];
    }

    private function withAddedTime(array $cart): array
    {
        $cart['add_to_cart_time'] ??= Carbon::now();

        return $cart;
    }

    private function withoutKeys(array $cart, array $keys): array
    {
        return array_diff_key($cart, array_flip($keys));
    }

    private function getCartCustomerData(string $cartId, ?object $customer): array
    {
        if ($this->isWalkInCart(cartId: $cartId)) {
            return [
                'customerName' => self::WALK_IN_CUSTOMER_NAME,
                'customerPhone' => '',
                'customerId' => self::WALK_IN_CUSTOMER_ID,
            ];
        }

        return [
            ...$this->getCustomerContact(customer: $customer),
            'customerId' => explode('-', $cartId)[2],
        ];
    }

    private function getCustomerContact(?object $customer): array
    {
        if ($customer) {
            return [
                'customerName' => $customer['f_name'].' '.$customer['l_name'],
                'customerPhone' => $customer['phone'],
            ];
        }

        return [
            'customerName' => '',
            'customerPhone' => '',
        ];
    }

    private function calculateCartItemsData(string $cartId, int|string $customerId, Collection $products): array
    {
        $cart = $this->session->get($cartId);
        $cartItemValue = [];
        $subTotalCalculation = [
            'countItem' => 0,
            'totalQuantity' => 0,
            'totalTax' => 0,
            'subtotal' => 0,
            'discountOnProduct' => 0,
            'productSubtotal' => 0,
        ];

        $cartLines = $this->getCustomerCartLines(cart: $cart, customerId: $customerId);
        if ($cartLines->isNotEmpty()) {
            $totalDiscountedPrice = $this->getCartDiscountedTotal(cart: $cart, products: $products, customerId: $customerId);

            foreach ($cartLines as $cartItem) {
                $product = $products->firstWhere('id', $cartItem['id']);
                if (! $product) {
                    continue;
                }
                $lineSubtotal = $this->calculateLineSubtotal(product: $product, cartItem: $cartItem, totalDiscountedPrice: $totalDiscountedPrice, cartId: $cartId);
                $cartItem['productSubtotal'] = $lineSubtotal['productSubtotal'];
                $cartItem['maxQuantity'] = $this->getCartLineQuantityLimit(product: $product, cartItem: $cartItem);
                $subTotalCalculation['customerOnHold'] = $cartItem['customerOnHold'];
                $cartItemValue[] = $cartItem;

                $subTotalCalculation['countItem'] += $lineSubtotal['countItem'];
                $subTotalCalculation['totalQuantity'] += $lineSubtotal['totalQuantity'];
                $subTotalCalculation['productSubtotal'] += $lineSubtotal['productSubtotal'];
                $subTotalCalculation['subtotal'] += $lineSubtotal['subtotal'];
                $subTotalCalculation['discountOnProduct'] += $lineSubtotal['discountOnProduct'];
                $subTotalCalculation['totalTax'] += $lineSubtotal['appliedTaxAmount'];
            }
        }
        $totalCalculation = $this->calculateCartTotal(subtotal: $subTotalCalculation['subtotal'], cartId: $cartId);

        return [
            ...$this->getExtraDiscountFormData(cartId: $cartId),
            'countItem' => $subTotalCalculation['countItem'],
            'total' => $totalCalculation['total'],
            'subtotal' => $subTotalCalculation['subtotal'],
            'discountOnProduct' => $subTotalCalculation['discountOnProduct'],
            'productSubtotal' => $subTotalCalculation['productSubtotal'],
            'totalTax' => round($subTotalCalculation['totalTax'], self::MONEY_DECIMAL_PLACES),
            'cartItemValue' => $cartItemValue,
            'couponDiscount' => $totalCalculation['couponDiscount'],
            'extraDiscount' => $totalCalculation['extraDiscount'],
            'customerOnHold' => $subTotalCalculation['customerOnHold'] ?? false,
        ];
    }

    private function calculateCartTotal(int|float $subtotal, string $cartId): array
    {
        $cart = $this->cartToArray(cart: $this->session->get($cartId));
        $couponDiscount = $cart['coupon_discount'] ?? 0;

        $total = $subtotal;
        $extraDiscount = $cart['ext_discount'] ?? 0;
        $extraDiscountType = $cart['ext_discount_type'] ?? self::EXTRA_DISCOUNT_TYPE_AMOUNT;
        if ($extraDiscountType == self::EXTRA_DISCOUNT_TYPE_PERCENT && $extraDiscount > 0) {
            $extraDiscount = (($subtotal - $couponDiscount) * $extraDiscount) / 100;
        }
        if ($extraDiscount) {
            $total -= $extraDiscount;
        }

        return [
            'total' => $total,
            'couponDiscount' => $couponDiscount,
            'extraDiscount' => $extraDiscount,
        ];
    }

    private function getExtraDiscountFormData(string $cartId): array
    {
        $cart = $this->cartToArray(cart: $this->session->get($cartId));
        $type = $cart['ext_discount_type'] ?? null;
        $value = (float) ($cart['ext_discount'] ?? 0);

        return [
            'extraDiscountType' => $type === self::EXTRA_DISCOUNT_TYPE_PERCENT ? self::EXTRA_DISCOUNT_TYPE_PERCENT : self::EXTRA_DISCOUNT_TYPE_AMOUNT,
            'extraDiscountValue' => $type === self::EXTRA_DISCOUNT_TYPE_PERCENT ? $value : round(usdToDefaultCurrency(amount: $value), 2),
        ];
    }

    private function getLinePriceTotals(array $cart): array
    {
        $totalProductPrice = 0;
        $productDiscount = 0;
        foreach ($cart as $line) {
            if (is_array($line)) {
                $totalProductPrice += $line['price'] * $line['quantity'];
                $productDiscount += $line['discount'] * $line['quantity'];
            }
        }

        return ['totalProductPrice' => $totalProductPrice, 'productDiscount' => $productDiscount];
    }

    private function calculateCouponDiscount(object $coupon, float $totalProductPrice, float $productDiscount): float
    {
        if ($coupon['discount_type'] === self::COUPON_TYPE_PERCENTAGE) {
            return min(((($totalProductPrice - $productDiscount) / 100) * $coupon['discount']), $coupon['max_discount']);
        }

        return (float) $coupon['discount'];
    }

    private function recomputeCouponDiscount(array $cart, float $lineTotal): float
    {
        if (! isset($cart['coupon_discount_type'])) {
            return (float) ($cart['coupon_discount'] ?? 0);
        }

        if ($cart['coupon_discount_type'] === self::COUPON_TYPE_PERCENTAGE) {
            return min(($lineTotal / 100) * (float) $cart['coupon_amount'], (float) $cart['coupon_max_discount']);
        }

        return (float) $cart['coupon_amount'];
    }

    private function isAmountExtraDiscountAboveTotal(array $cart, float $remainingTotal): bool
    {
        if (! isset($cart['ext_discount']) || ($cart['ext_discount_type'] ?? null) === self::EXTRA_DISCOUNT_TYPE_PERCENT) {
            return false;
        }

        return (float) $cart['ext_discount'] > $remainingTotal;
    }

    private function findVariationRow(array $variations, string $variant): ?array
    {
        $row = $variant === '' ? null : collect($variations)->firstWhere('type', $variant);

        return $row === null ? null : (array) $row;
    }

    private function getProductVariations(object $product): array
    {
        return json_decode($product->retailDetail?->variation ?? '[]', true) ?: [];
    }

    private function getSelectedStock(object $product, string $variant): int
    {
        $variations = $this->getProductVariations(product: $product);

        if ($product['product_type'] === self::PRODUCT_TYPE_DIGITAL || $variations === []) {
            return max(0, (int) $product['current_stock']);
        }

        return max(0, (int) ($this->findVariationRow(variations: $variations, variant: $variant)['qty'] ?? 0));
    }

    private function isSelectionAvailable(object $product, int $selectedStock): bool
    {
        return $product['product_type'] === self::PRODUCT_TYPE_DIGITAL || $selectedStock >= 1;
    }

    private function findLatestCartLine(int $productId): ?array
    {
        return collect($this->getCurrentCartItems())
            ->filter(fn ($line) => is_array($line) && (int) ($line['id'] ?? 0) === $productId)
            ->last();
    }

    private function getSelectionCombinations(object $product, array $colorNames): array
    {
        $colorCodes = json_decode((string) $product['colors'], true) ?: [];
        $combinations = $colorCodes === []
            ? [['colorCode' => null, 'choiceValues' => []]]
            : array_map(fn ($colorCode) => ['colorCode' => $colorCode, 'choiceValues' => []], $colorCodes);

        foreach (json_decode((string) $product['choice_options'], true) ?: [] as $choice) {
            if (empty($choice['options'])) {
                continue;
            }
            $combinations = collect($combinations)->flatMap(fn (array $combination) => array_map(
                fn ($option) => ['colorCode' => $combination['colorCode'], 'choiceValues' => [...$combination['choiceValues'], $choice['name'] => $option]],
                $choice['options'],
            ))->all();
        }

        return array_map(fn (array $combination) => [
            ...$combination,
            'variant' => $this->buildVariantString(
                colorName: $combination['colorCode'] === null ? null : ($colorNames[$combination['colorCode']] ?? null),
                choiceValues: array_values($combination['choiceValues']),
            ),
        ], $combinations);
    }

    private function findFirstAvailableCombination(object $product, array $combinations): array
    {
        foreach ($combinations as $combination) {
            if ($this->getSelectedStock(product: $product, variant: $combination['variant']) >= 1) {
                return $combination;
            }
        }

        return $combinations[0];
    }

    private function getFirstDigitalVariantKey(object $product): ?string
    {
        if (empty($product->retailDetail?->digital_product_file_types)) {
            return null;
        }

        foreach ($product->retailDetail?->digital_product_extensions ?? [] as $extensionKey => $extensions) {
            foreach ($extensions as $extension) {
                return $extensionKey.'-'.preg_replace('/\s+/', '-', $extension);
            }
        }

        return null;
    }

    private function buildVariantString(?string $colorName, array $choiceValues): string
    {
        $variant = $colorName ?? '';
        foreach ($choiceValues as $value) {
            $value = str_replace(' ', '', (string) $value);
            $variant .= $variant !== '' ? '-'.$value : $value;
        }

        return $variant;
    }
}
