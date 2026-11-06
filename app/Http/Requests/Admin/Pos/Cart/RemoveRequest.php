<?php

namespace App\Http\Requests\Admin\Pos\Cart;

use App\Http\Requests\BaseRequest;

class RemoveRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'variant' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate(':field is required', ['field' => translate('Product')]),
            'id.integer' => translate(':field must be a valid product', ['field' => translate('Product')]),
            'variant.string' => translate(':field must be text', ['field' => translate('Variant')]),
        ];
    }
}
