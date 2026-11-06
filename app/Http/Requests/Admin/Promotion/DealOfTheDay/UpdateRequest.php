<?php

namespace App\Http\Requests\Admin\Promotion\DealOfTheDay;

use App\Http\Requests\BaseRequest;

class UpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.*' => ['nullable', 'string', 'max:100'],
            'lang' => ['required', 'array'],
            'product_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => translate('Title field is required'),
            'title.*.max' => translate('Title must not be longer than 100 characters'),
            'product_id.required' => translate('Please select a product'),
            'product_id.integer' => translate('The selected product is not valid'),
        ];
    }
}
