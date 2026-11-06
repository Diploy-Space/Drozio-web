<?php

namespace App\Http\Requests\Customer\Product;

use App\Http\Requests\BaseRequest;

class SectionListRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:0'],
            'offset' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => translate('The number of products per page must be a whole number'),
            'limit.min' => translate('The number of products per page cannot be negative'),
            'offset.integer' => translate('The page number must be a whole number'),
        ];
    }

    public function dataLimit(): int
    {
        return (int) $this->query('limit') ?: DEFAULT_DATA_LIMIT;
    }

    public function page(): int
    {
        return max(1, (int) $this->query('offset', 1));
    }
}
