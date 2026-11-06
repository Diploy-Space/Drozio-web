<?php

namespace App\Http\Requests\Admin\Attribute;

use App\Enums\BusinessModule;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'name' => 'required|array',
            'name.0' => [
                'required',
                Rule::unique('attributes', 'name')
                    ->where('business_module', BusinessModule::RETAIL),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('The name field is required!'),
            'name.array' => translate('Please enter the attribute name for each language.'),
            'name.0.required' => translate('Please enter an attribute name.'),
            'name.0.unique' => translate('A retail attribute with this name exists. Choose another name.'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (is_null($this['name'][array_search('en', $this['lang'])])) {
                    $validator->errors()->add(
                        'name', translate('Name field is required').'!'
                    );
                }
            },
        ];
    }
}
