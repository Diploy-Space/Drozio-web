<?php

namespace App\Http\Requests\Customer\Shipping;

use App\Http\Requests\BaseRequest;

class SellerMethodListRequest extends BaseRequest
{
    public const SELLER_IS_ADMIN = 'admin';

    public const SELLER_IS_SELLER = 'seller';

    public function rules(): array
    {
        return [
            'seller_id' => 'required|integer',
            'seller_is' => 'required|in:'.self::SELLER_IS_ADMIN.','.self::SELLER_IS_SELLER,
        ];
    }

    public function messages(): array
    {
        return [
            'seller_id.required' => translate('Choose the shop to load shipping for'),
            'seller_id.integer' => translate('The selected shop could not be identified'),
            'seller_is.required' => translate('Choose whether the shop is in-house or a vendor shop'),
            'seller_is.in' => translate('Choose whether the shop is in-house or a vendor shop'),
        ];
    }

    public function isInHouse(): bool
    {
        return $this->input('seller_is') === self::SELLER_IS_ADMIN;
    }
}
