<?php

namespace App\Http\Requests\Vendor\Pos;

use App\Http\Requests\BaseRequest;

class TaxAmountRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'cart' => 'nullable|array',
            'cart.*' => 'array',
            'cart.*.id' => 'required|integer',
            'cart.*.quantity' => 'required|numeric',
            'cart.*.variant' => 'nullable|string',
            'cart.*.variant_key' => 'nullable|string',
            'cart.*.digital_variation_price' => 'nullable|numeric',
            'cart.*.variation' => 'nullable',
            'coupon_discount_amount' => 'nullable|numeric',
            'extra_discount' => 'nullable|numeric',
            'extra_discount_type' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'cart.array' => translate('The cart must be a list of items'),
            'cart.*.array' => translate('Each cart item must contain the product and its quantity'),
            'cart.*.id.required' => translate('Each cart item needs a product'),
            'cart.*.id.integer' => translate('Each cart item product must be a valid product'),
            'cart.*.quantity.required' => translate('Each cart item needs a quantity'),
            'cart.*.quantity.numeric' => translate('Each cart item quantity must be a number'),
            'cart.*.variant.string' => translate('Each cart item variant must be text'),
            'cart.*.variant_key.string' => translate('Each cart item variant key must be text'),
            'cart.*.digital_variation_price.numeric' => translate('Each cart item digital variation price must be a number'),
            'coupon_discount_amount.numeric' => translate('Coupon discount amount must be a number'),
            'extra_discount.numeric' => translate('Extra discount must be a number'),
            'extra_discount_type.string' => translate('Extra discount type must be text'),
        ];
    }

    public function cartItems(): array
    {
        return array_map(
            static fn (array $cartItem): array => $cartItem + ['variant' => null, 'variant_key' => null, 'digital_variation_price' => null, 'variation' => null],
            array_values((array) ($this->validated('cart') ?? [])),
        );
    }

    public function cartProductIds(): array
    {
        return array_values(array_unique(array_map(static fn (array $cartItem): int => (int) $cartItem['id'], $this->cartItems())));
    }
}
