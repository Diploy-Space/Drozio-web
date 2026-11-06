<?php

namespace App\Http\Requests\Customer\Wishlist;

use App\Enums\BusinessModule;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{
    public function rules(): array
    {
        $rules = [
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'business_module' => ['nullable', Rule::in([BusinessModule::RETAIL, BusinessModule::WHOLESALE])],
        ];

        if ($this->isApiRequest()) {
            $rules['limit'] = ['nullable', 'integer', 'min:1'];
            $rules['offset'] = ['nullable', 'integer', 'min:1'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'business_module.in' => translate('Please select a valid wishlist tab'),
            'search.string' => translate('Please enter a valid search keyword'),
            'search.max' => translate('The search keyword may not be greater than 255 characters'),
            'page.integer' => translate('Please select a valid page'),
            'page.min' => translate('Please select a valid page'),
            'limit.integer' => translate('The limit must be a whole number'),
            'limit.min' => translate('The limit must be at least 1'),
            'offset.integer' => translate('The page number must be a whole number'),
            'offset.min' => translate('The page number must be at least 1'),
        ];
    }
}
