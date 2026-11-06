<?php

namespace App\Http\Requests\Shared\Product;

use App\Http\Requests\BaseRequest;

class DeleteImageRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate(':field is required', ['field' => translate('Image name')]),
            'name.string' => translate(':field is invalid', ['field' => translate('Image name')]),
        ];
    }
}
