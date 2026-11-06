<?php

namespace App\Http\Requests\Customer\Shipping;

use App\Http\Requests\BaseRequest;

class ChooseForOrderRequest extends BaseRequest
{
    public const ALL_CART_GROUPS = 'all_cart_group';

    public function rules(): array
    {
        return [
            'cart_group_id' => 'required|string',
            'id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'cart_group_id.required' => translate('Choose the cart group to ship'),
            'cart_group_id.string' => translate('The selected cart group could not be identified'),
            'id.required' => translate('Select shipping method'),
            'id.integer' => translate('The selected shipping method could not be identified'),
        ];
    }

    public function isForAllCartGroups(): bool
    {
        return $this->input('cart_group_id') === self::ALL_CART_GROUPS;
    }
}
