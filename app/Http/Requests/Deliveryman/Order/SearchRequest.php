<?php

namespace App\Http\Requests\Deliveryman\Order;

use App\Http\Requests\BaseRequest;

class SearchRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => translate('The number of orders per page must be a whole number'),
            'limit.min' => translate('The number of orders per page must be at least one'),
            'offset.integer' => translate('The page number must be a whole number'),
            'offset.min' => translate('The page number must be at least one'),
            'search.string' => translate('Enter the search text as plain text'),
            'search.max' => translate('The search text is too long'),
        ];
    }
}
