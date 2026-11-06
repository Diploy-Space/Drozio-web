<?php

namespace App\Http\Requests\Admin\Brand;

use App\Enums\BusinessModule;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class AddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'name' => 'required|array',
            'name.0' => [
                'required',
                Rule::unique('brands', 'name')->where('business_module', BusinessModule::RETAIL),
            ],
            'image' => getRulesStringForImageValidation(
                rules: ['required', 'image'],
                skipMimes: ['.svg', '.gif'],
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('The name field is required'),
            'name.0.required' => translate('The name field is required'),
            'name.0.unique' => translate('Name has been taken'),
            'image.required' => translate('The image is required'),
            'image.mimes' => translate('Brand image must be jpg jpeg png webp'),
            'image.max' => translate('Brand image must not exceed 2 MB'),
        ];
    }
}
