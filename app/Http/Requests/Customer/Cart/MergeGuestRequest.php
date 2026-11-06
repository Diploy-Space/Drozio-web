<?php

namespace App\Http\Requests\Customer\Cart;

use App\Http\Requests\BaseRequest;

class MergeGuestRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'cart_guest_id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'cart_guest_id.required' => translate('The guest cart could not be identified'),
            'cart_guest_id.integer' => translate('The guest cart could not be identified'),
        ];
    }
}
