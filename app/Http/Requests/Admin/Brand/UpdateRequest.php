<?php

namespace App\Http\Requests\Admin\Brand;

use App\Http\Requests\BaseRequest;
use App\Services\New\BrandService;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        $brandId = $this->route('id');
        // The module comes off the stored row, never the form — a brand is not
        // moved across modules by an update.
        $businessModule = app(BrandService::class)->getBusinessModule($brandId);

        return [
            'name' => 'required|array',
            'image' => getRulesStringForImageValidation(
                rules: ['nullable', 'image'],
                skipMimes: ['.svg', '.gif'],
            ),
            'name.0' => [
                'required',
                Rule::unique('brands', 'name')->where('business_module', $businessModule)->ignore($brandId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('The name field is required'),
            'name.0.required' => translate('The name field is required'),
            'name.0.unique' => translate('Name has been taken'),
            'image.mimes' => translate('Brand image must be jpg jpeg png'),
            'image.max' => translate('Brand image must not exceed 2 MB'),
        ];
    }
}
