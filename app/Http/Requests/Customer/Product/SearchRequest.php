<?php

namespace App\Http\Requests\Customer\Product;

use App\Http\Requests\BaseRequest;

class SearchRequest extends BaseRequest
{
    public function rules(): array
    {
        if (! $this->isApiRequest()) {
            return [
                'name' => 'required',
            ];
        }

        return [
            'name' => ['required', 'string'],
            'limit' => ['nullable', 'integer', 'min:0'],
            'offset' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate(':field is required', ['field' => translate('Product Name')]).'!',
            'name.string' => translate('Please enter a valid search keyword'),
            'limit.integer' => translate('The number of products per page must be a whole number'),
            'limit.min' => translate('The number of products per page cannot be negative'),
            'offset.integer' => translate('The page number must be a whole number'),
        ];
    }

    public function dataLimit(): int
    {
        return (int) $this->input('limit') ?: DEFAULT_DATA_LIMIT;
    }

    public function page(): int
    {
        return max(1, (int) $this->input('offset', 1));
    }
}
