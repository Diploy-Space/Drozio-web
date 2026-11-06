<?php

namespace App\Http\Requests\Admin\Product;

use App\Http\Requests\BaseRequest;

class SettingsUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'stock_limit' => 'sometimes|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'stock_limit.integer' => translate('The stock limit must be a whole number'),
            'stock_limit.min' => translate('The stock limit must be at least 0'),
        ];
    }
}
