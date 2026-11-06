<?php

namespace App\Http\Requests\Vendor\Pos;

use App\Http\Requests\BaseRequest;

class OrderPlaceRequest extends BaseRequest
{
    public const WALKING_CUSTOMER_ID = 0;

    protected function prepareForValidation(): void
    {
        $modifiedCart = $this->input('modified_cart');

        if (is_array($modifiedCart)) {
            $this->merge(['modified_cart' => array_values(array_filter($modifiedCart, 'is_array'))]);
        }
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|integer|min:0',
            'payment_method' => 'nullable|string|max:255',
            'paid_amount' => 'nullable|numeric',
            'extra_discount' => 'nullable|numeric',
            'extra_discount_type' => 'nullable|string|max:255',
            'coupon_discount_amount' => 'nullable|numeric',
            'coupon_code' => 'nullable|string|max:255',
            'total_tax_amount' => 'nullable|numeric',
            'cart' => 'nullable|array',
            'modified_cart' => 'nullable|array',
            'modified_cart.*.id' => 'required|integer',
            'modified_cart.*.quantity' => 'required|numeric',
            'modified_cart.*.price' => 'required|numeric',
            'modified_cart.*.discounted_price' => 'required|numeric',
            'modified_cart.*.discount' => 'required|numeric',
            'modified_cart.*.applied_tax_amount' => 'required|numeric',
            'modified_cart.*.coupon_discount' => 'nullable|numeric',
            'modified_cart.*.extra_discount' => 'nullable|numeric',
            'modified_cart.*.variant' => 'nullable|string',
            'modified_cart.*.variant_key' => 'nullable|string',
            'modified_cart.*.variation' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.integer' => translate('Select a valid customer'),
            'customer_id.min' => translate('Select a valid customer'),
            'payment_method.string' => translate('Payment method must be text'),
            'payment_method.max' => translate('Payment method is too long'),
            'paid_amount.numeric' => translate('Paid amount must be a number'),
            'extra_discount.numeric' => translate('Extra discount must be a number'),
            'extra_discount_type.string' => translate('Extra discount type must be text'),
            'extra_discount_type.max' => translate('Extra discount type is too long'),
            'coupon_discount_amount.numeric' => translate('Coupon discount amount must be a number'),
            'coupon_code.string' => translate('Coupon code must be text'),
            'coupon_code.max' => translate('Coupon code is too long'),
            'total_tax_amount.numeric' => translate('Total tax amount must be a number'),
            'cart.array' => translate('The cart must be a list of items'),
            'modified_cart.array' => translate('The calculated cart must be a list of items'),
            'modified_cart.*.id.required' => translate('Each cart item needs a product'),
            'modified_cart.*.id.integer' => translate('Each cart item product must be a valid product'),
            'modified_cart.*.quantity.required' => translate('Each cart item needs a quantity'),
            'modified_cart.*.quantity.numeric' => translate('Each cart item quantity must be a number'),
            'modified_cart.*.price.required' => translate('Each cart item needs a price'),
            'modified_cart.*.price.numeric' => translate('Each cart item price must be a number'),
            'modified_cart.*.discounted_price.required' => translate('Each cart item needs a discounted price'),
            'modified_cart.*.discounted_price.numeric' => translate('Each cart item discounted price must be a number'),
            'modified_cart.*.discount.required' => translate('Each cart item needs a discount'),
            'modified_cart.*.discount.numeric' => translate('Each cart item discount must be a number'),
            'modified_cart.*.applied_tax_amount.required' => translate('Each cart item needs an applied tax amount'),
            'modified_cart.*.applied_tax_amount.numeric' => translate('Each cart item applied tax amount must be a number'),
            'modified_cart.*.coupon_discount.numeric' => translate('Each cart item coupon discount must be a number'),
            'modified_cart.*.extra_discount.numeric' => translate('Each cart item extra discount must be a number'),
            'modified_cart.*.variant.string' => translate('Each cart item variant must be text'),
            'modified_cart.*.variant_key.string' => translate('Each cart item variant key must be text'),
        ];
    }

    public function customerId(): int
    {
        return (int) ($this->validated('customer_id') ?? self::WALKING_CUSTOMER_ID);
    }

    public function isWalkingCustomer(): bool
    {
        return $this->customerId() === self::WALKING_CUSTOMER_ID;
    }

    public function modifiedCartItems(): array
    {
        return array_map(
            static fn (array $cartItem): array => $cartItem + ['variant' => null, 'variant_key' => null, 'variation' => null],
            array_values((array) ($this->validated('modified_cart') ?? [])),
        );
    }

    public function lastCartProductId(): ?int
    {
        $lastCartProductId = null;

        foreach ((array) ($this->input('cart') ?? []) as $cartItem) {
            if (is_array($cartItem) && isset($cartItem['id']) && is_numeric($cartItem['id'])) {
                $lastCartProductId = (int) $cartItem['id'];
            }
        }

        return $lastCartProductId;
    }

    public function orderInput(): array
    {
        return [
            'customer_id' => $this->customerId(),
            'payment_method' => $this->validated('payment_method'),
            'extra_discount' => $this->validated('extra_discount'),
            'extra_discount_type' => $this->validated('extra_discount_type'),
            'total_tax_amount' => $this->validated('total_tax_amount'),
            'coupon_discount_amount' => $this->validated('coupon_discount_amount'),
            'coupon_code' => $this->validated('coupon_code'),
            'cart' => $this->input('cart'),
        ];
    }
}
