<?php

namespace App\Services;

use App\Enums\SessionKey;
use App\Traits\CalculatorTrait;
use App\Utils\CartManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class CartService
{
    use CalculatorTrait;
    use VatTaxManagement;

    public function getVariantData(object $request, object $product, ?string $colorName = null): array
    {
        $variations = json_decode($product->retailDetail?->variation ?? '[]', true) ?? [];
        $hasVariations = ! empty($variations);
        $variation = $this->makeVariation(
            request: $request,
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

        $requestQuantity = (int) $request['quantity'];

        $inCartStatus = 0;
        $cartData = $this->getCurrentCartItems();
        $inCartData = null;

        if ($product['product_type'] == 'digital' && $request->has('variant_key')) {
            $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $product['unit_price'], from: 'panel');
            $quantity = $product['current_stock'];
            foreach ($product['digitalVariation'] as $variant) {
                if ($variant['variant_key'] == $request->variant_key) {
                    $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $variant['price'], from: 'panel');
                    $price = $variant['price'] - $discount;
                    $unitPrice = $variant['price'];
                    $variation = $variant['variant_key'];
                }
            }
        }

        foreach ($cartData as $cart) {
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
                $requestQuantity = (int) ($request['quantity_in_cart'] ?? $cart['quantity']);
            }
        }
        $discountType = getProductPriceByType(product: $product, type: 'discount_type', result: 'string');
        $allVariationsOutOfStock = $hasVariations ? empty(array_filter($variations, fn ($v) => ($v['qty'] ?? 0) > 0)) : ($quantity < 1);

        return [
            'price' => setCurrencySymbol(amount: usdToDefaultCurrency(amount: $price * $requestQuantity)),
            'discount' => usdToDefaultCurrency($discount),
            'discount_amount' => $discount,
            'discount_type' => $discountType,
            'discount_text' => $discountType == 'flat' ? translate('Save').' '.setCurrencySymbol(amount: usdToDefaultCurrency(amount: $discount), currencyCode: getCurrencyCode()) : getProductPriceByType(product: $product, type: 'discount', result: 'value').'% '.translate('OFF'),
            'quantity' => $product['product_type'] == 'physical' ? $quantity : 100,
            'inCartStatus' => $inCartStatus,
            'inCartData' => $inCartData,
            'requestQuantity' => $requestQuantity,
            'total_unit_price' => setCurrencySymbol(amount: usdToDefaultCurrency(amount: $unitPrice)),
            'discounted_unit_price' => setCurrencySymbol(amount: usdToDefaultCurrency(amount: $unitPrice - $discount)),
            'allVariationsOutOfStock' => $allVariationsOutOfStock,
        ];
    }

    public function makeVariation(object $request, ?string $colorName, array $choiceOptions): string
    {
        $variation = '';
        if ($colorName) {
            $variation = $colorName;
        }
        foreach ($choiceOptions as $choice) {
            if ($variation != null) {
                $variation .= '-'.str_replace(' ', '', $request[$choice->name]);
            } else {
                $variation .= str_replace(' ', '', $request[$choice->name]);
            }
        }

        return $variation;
    }

    public function getCurrentCartId(): string
    {
        if (! session()->has(SessionKey::CURRENT_USER)) {
            $this->getNewCartId();
        }

        return (string) session(SessionKey::CURRENT_USER);
    }

    public function getCurrentCartItems(): array
    {
        $cart = session($this->getCurrentCartId());

        return is_array($cart) ? $cart : [];
    }

    public function getUserId(): int
    {
        $userId = 0;
        if (Str::contains($this->getCurrentCartId(), 'saved-customer')) {
            $userId = explode('-', $this->getCurrentCartId())[2];
        }

        return $userId;
    }

    public function getUserType(): string
    {
        $userType = 'walk-in-customer';
        if (Str::contains($this->getCurrentCartId(), 'saved-customer')) {
            $userType = 'saved-customer';
        }

        return $userType;
    }

    public function getNewCartSession(string|int $cartId): void
    {
        if (! session()->has(SessionKey::CURRENT_USER)) {
            session()->put(SessionKey::CURRENT_USER, $cartId);
        }
        if (! session()->has(SessionKey::CART_NAME)) {
            if (! in_array($cartId, session(SessionKey::CART_NAME) ?? [])) {
                session()->push(SessionKey::CART_NAME, $cartId);
            }
        }
    }

    public function getCartKeeper(): void
    {
        $cartKeeper = [];
        foreach ($this->getCurrentCartItems() as $cartItem) {
            $cartKeeper[] = $cartItem;
        }
        session()->put($this->getCurrentCartId(), $cartKeeper);
    }

    public function findVariationRow(array $variations, string $variant): ?array
    {
        $row = $variant === '' ? null : collect($variations)->firstWhere('type', $variant);

        return $row === null ? null : (array) $row;
    }

    public function getVariationPrice(array $variation, string $variant, float $productPrice): float
    {
        return (float) ($this->findVariationRow(variations: $variation, variant: $variant)['price'] ?? $productPrice);
    }

    public function addCartDataOnSession(object $product, int $quantity, float $price, float $discount, string $variant, array $variations): array
    {
        $cartId = $this->getCurrentCartId();
        $sessionData = [
            'id' => $product['id'],
            'customerId' => $this->getUserId(),
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
        if (session()->has($cartId)) {
            $keeper = [];
            foreach (session($cartId) as $item) {
                $keeper[] = $item;
            }
            $keeper[] = $sessionData;

            if (! isset(session()->get($cartId)['add_to_cart_time'])) {
                $keeper += ['add_to_cart_time' => Carbon::now()];
            }
            session()->put($cartId, $keeper);
        } else {
            session()->put($cartId, [$sessionData] + ['add_to_cart_time' => Carbon::now()]);
        }

        return $sessionData;
    }

    public function getProductCounts(): array
    {
        $cartData = $this->getCurrentCartItems();
        $productCounts = [];

        if (! empty($cartData)) {
            foreach ($cartData as $key => $item) {
                if ($key === 'add_to_cart_time' || ! is_array($item) || ! isset($item['id'])) {
                    continue;
                }
                $productId = $item['id'];
                $quantity = $item['quantity'] ?? 0;
                if (isset($productCounts[$productId])) {
                    $productCounts[$productId] += $quantity;
                } else {
                    $productCounts[$productId] = $quantity;
                }
            }
        }

        return $productCounts;
    }

    public function getQuantityAndUpdateTime(object $request, object $product): int
    {
        $quantity = 0;
        $cartId = $this->getCurrentCartId();
        $cart = $this->getCurrentCartItems();
        $keeper = [];

        foreach ($cart as $item) {
            if (is_array($item)) {
                $variantCheck = false;
                if (! empty($item['variant']) && ($item['variant'] == $request['variant']) && ($item['id'] == $request['key'])) {
                    $variantCheck = true;
                } elseif (empty($request['variant']) && $item['id'] == $request['key']) {
                    $variantCheck = true;
                }

                if ($variantCheck) {
                    $variant = '';
                    if ($item['variations']) {
                        foreach ($item['variations'] as $value) {
                            if ($variant != null) {
                                $variant .= '-'.str_replace(' ', '', $value);
                            } else {
                                $variant .= str_replace(' ', '', $value);
                            }
                        }
                    }

                    $variationRow = $this->findVariationRow(
                        variations: json_decode($product->retailDetail?->variation ?? '[]', true) ?? [],
                        variant: $variant,
                    );
                    $productQuantity = $variationRow === null ? $product['current_stock'] : (int) $variationRow['qty'];

                    $quantity = $productQuantity - $request['quantity'];
                    if ($product['product_type'] == 'physical' && $quantity < 0) {
                        return $quantity;
                    }
                    $item['quantity'] = $request['quantity'];
                }
                $keeper[] = $item;
            }
        }
        $keeper += ['add_to_cart_time' => Carbon::now()];
        session()->put($cartId, $keeper);

        return $quantity;
    }

    public function getNewCartId(): void
    {
        $cartId = 'walk-in-customer-'.rand(10, 1000);
        session()->put(SessionKey::CURRENT_USER, $cartId);
        if (! in_array($cartId, session(SessionKey::CART_NAME) ?? [])) {
            session()->push(SessionKey::CART_NAME, $cartId);
        }
    }

    public function getCartSubtotalCalculation(object|array $product, array $cartItem, int|float $totalDiscountedPrice, string $cartName): array
    {
        $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $cartItem['price'], from: 'panel');
        $productSubtotal = (($cartItem['price'] - $discount) * $cartItem['quantity']);

        $couponDiscount = 0;
        if (isset(session()->get($cartName)['coupon_discount'])) {
            $couponDiscount = session()->get($cartName)['coupon_discount'];
        }
        $productCouponDiscount = ($couponDiscount * $productSubtotal) / $totalDiscountedPrice;

        $extraDiscount = session()->get($cartName)['ext_discount'] ?? 0;
        $extraDiscountType = session()->get($cartName)['ext_discount_type'] ?? 'amount';
        if ($extraDiscountType == 'percent' && $extraDiscount > 0) {
            $extraDiscount = (($totalDiscountedPrice - $couponDiscount) * $extraDiscount) / 100;
        }
        $productExtraDiscount = ($extraDiscount * $productSubtotal) / $totalDiscountedPrice;

        $taxConfig = self::getTaxSystemType();
        $appliedTaxAmount = CartManager::getAppliedTaxAmount(
            product: $product,
            taxConfig: $taxConfig,
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

    public function getTotalCalculation(array $subTotalCalculation, string $cartName): array
    {
        $couponDiscount = 0;
        if (isset(session()->get($cartName)['coupon_discount'])) {
            $couponDiscount = session()->get($cartName)['coupon_discount'];
        }

        $total = $subTotalCalculation['subtotal'];
        $extraDiscount = session()->get($cartName)['ext_discount'] ?? 0;
        $extraDiscountType = session()->get($cartName)['ext_discount_type'] ?? 'amount';
        if ($extraDiscountType == 'percent' && $extraDiscount > 0) {
            $extraDiscount = (($subTotalCalculation['subtotal'] - $couponDiscount) * $extraDiscount) / 100;
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

    public function customerOnHoldStatus($status): void
    {
        $cartKeeper = [];
        foreach ($this->getCurrentCartItems() as $cartItem) {
            if (is_array($cartItem)) {
                $cartItem['customerOnHold'] = $status;
            }
            $cartKeeper[] = $cartItem;
        }
        session()->put($this->getCurrentCartId(), $cartKeeper);
    }

    public function getCartTotalDiscountPrice($cartList, $products, $customerID): float|int
    {
        $totalDiscountedPrice = 0;
        if ($cartList) {
            foreach ($cartList as $cartItem) {
                if (is_array($cartItem)) {
                    $product = $products->firstWhere('id', $cartItem['id']);
                    if ($product) {
                        if ($cartItem['customerId'] == $customerID) {
                            $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $cartItem['price'], from: 'panel');
                            $totalDiscountedPrice += (($cartItem['price'] - $discount) * $cartItem['quantity']);
                        }
                    }
                }
            }
        }

        return $totalDiscountedPrice;
    }

    public function checkCurrentStock(string $variant, array $variation, int $productQty, int $quantity): int
    {
        $row = $this->findVariationRow(variations: $variation, variant: $variant);

        return ($row === null ? $productQty : (int) $row['qty']) - $quantity;
    }

    public function checkProductTypeDigital(string|int $cartId): bool
    {
        $cart = session($cartId);
        $isDigitalProduct = false;
        foreach ($cart as $item) {
            if (is_array($item) && $item['productType'] == 'digital') {
                $isDigitalProduct = true;
            }
        }

        return $isDigitalProduct;
    }

    public function getCustomerInfo(?object $currentCustomerData, int $customerId): array
    {
        if ($currentCustomerData) {
            $customerName = $currentCustomerData['f_name'].' '.$currentCustomerData['l_name'];
            $customerPhone = $currentCustomerData['phone'];
        } else {
            $customerName = '';
            $customerPhone = '';
            session()->forget(session($customerId));
            $this->getNewCartId();
        }

        return [
            'customerName' => $customerName,
            'customerPhone' => $customerPhone,
        ];
    }

    public function filterSessionCartList($cart): Collection
    {
        $cartListVariations = [];

        return collect($cart)->filter(function ($cart, $key) {
            return is_array($cart);
        })->filter(function ($cartItem, $key) use (&$cartListVariations) {
            $variations = $cartItem['id'].'-'.$cartItem['productType'].'-Q'.$cartItem['quantity'].'-'.($cartItem['variant'] ? $cartItem['variant'] : 'single');
            $variationExist = in_array($variations, $cartListVariations);
            $cartListVariations[] = $variations;

            return ! $variationExist;
        });
    }
}
