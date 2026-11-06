<?php

namespace App\Http\Requests\Customer\Review;

use App\Http\Requests\BaseRequest;

class ListRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'limit' => ['nullable', 'integer', 'min:1'],
                'offset' => ['nullable', 'integer', 'min:0'],
            ];
        }

        return [
            'product_id' => ['required', 'integer'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => translate('Please select a product'),
            'product_id.integer' => translate('Please select a valid product'),
            'limit.integer' => translate('Please provide a valid limit'),
            'limit.min' => translate('Please provide a valid limit'),
            'offset.integer' => translate('Please provide a valid page number'),
            'offset.min' => translate('Please provide a valid page number'),
        ];
    }

    public function pageNumber(): int
    {
        return (int) $this['offset'] + 1;
    }
}
