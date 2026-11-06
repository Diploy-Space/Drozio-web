<?php

namespace App\Http\Requests\Admin\Setting\Website;

use App\Http\Requests\BaseRequest;

class PageAddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = false;

    public function rules(): array
    {
        return [
            'title' => 'required|string|unique:business_pages,title',
            'description' => 'required|string',
            'slug' => 'string|unique:business_pages,slug',
            'banner' => getRulesStringForImageValidation(
                rules: ['nullable', 'image'],
                skipMimes: ['.svg', '.webp'],
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => translate(':field is required', ['field' => translate('Title')]),
            'description.required' => translate(':field is required', ['field' => translate('Description')]),
            'slug.unique' => translate('Slug must be unique'),
            'banner.mimes' => translate('Banner image type must be').' jpg, jpeg, png, gif',
            'banner.max' => translate('Banner image max size is 2 MB'),
        ];
    }
}
