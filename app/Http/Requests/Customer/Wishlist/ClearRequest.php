<?php

namespace App\Http\Requests\Customer\Wishlist;

use App\Enums\BusinessModule;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ClearRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'business_module' => ['nullable', Rule::in([BusinessModule::RETAIL, BusinessModule::WHOLESALE])],
        ];
    }

    public function messages(): array
    {
        return [
            'business_module.in' => translate('Please select a valid wishlist tab'),
        ];
    }
}
