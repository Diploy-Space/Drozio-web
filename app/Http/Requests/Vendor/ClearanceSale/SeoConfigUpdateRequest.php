<?php

namespace App\Http\Requests\Vendor\ClearanceSale;

use App\Http\Requests\BaseRequest;

class SeoConfigUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = false;

    public function rules(): array
    {
        return [
            'meta_title' => 'string|nullable',
            'meta_description' => 'string|nullable',
            'meta_image' => getRulesStringForImageValidation(
                rules: ['nullable', 'image'],
                skipMimes: ['.svg'],
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'meta_title.string' => translate('The meta title must be a text'),
            'meta_description.string' => translate('The meta description must be a text'),
            'meta_image.mimes' => translate('Meta image must be jpg jpeg png'),
            'meta_image.max' => translate('Meta image must not exceed 2mb'),
        ];
    }
}
