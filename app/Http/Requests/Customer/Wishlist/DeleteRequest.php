<?php

namespace App\Http\Requests\Customer\Wishlist;

use App\Enums\BusinessModule;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class DeleteRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('productId')]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'business_module' => ['nullable', Rule::in([BusinessModule::RETAIL, BusinessModule::WHOLESALE])],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Please select a product'),
            'id.integer' => translate('The selected product is not valid'),
            'business_module.in' => translate('Please select a valid wishlist tab'),
        ];
    }
}
