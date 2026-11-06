<?php

namespace App\Http\Requests\Erp\Vendor;

use App\Http\Requests\BaseRequest;

class ListRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => translate('The limit must be a whole number'),
            'limit.min' => translate('The limit must be at least 1'),
            'offset.integer' => translate('The page number must be a whole number'),
            'offset.min' => translate('The page number must be at least 1'),
        ];
    }
}
