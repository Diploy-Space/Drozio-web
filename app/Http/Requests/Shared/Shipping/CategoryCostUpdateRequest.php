<?php

namespace App\Http\Requests\Shared\Shipping;

use App\Http\Requests\BaseRequest;

class CategoryCostUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
            'cost' => ['required', 'array', 'size:'.count((array) $this['ids'])],
            'cost.*' => ['required', 'numeric', 'min:0'],
            'multiply_qty' => ['required', 'array', 'size:'.count((array) $this['ids'])],
            'multiply_qty.*' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => translate('Choose at least one category'),
            'ids.array' => translate('Choose at least one category'),
            'ids.min' => translate('Choose at least one category'),
            'ids.*.required' => translate('Choose a valid category'),
            'ids.*.integer' => translate('Choose a valid category'),
            'cost.required' => translate('The shipping cost is required'),
            'cost.array' => translate('The shipping cost is required'),
            'cost.size' => translate('Send one shipping cost for each category'),
            'cost.*.required' => translate('The shipping cost is required'),
            'cost.*.numeric' => translate('The shipping cost must be a number'),
            'cost.*.min' => translate('The shipping cost cannot be negative'),
            'multiply_qty.required' => translate('Send one quantity setting for each category'),
            'multiply_qty.array' => translate('Send one quantity setting for each category'),
            'multiply_qty.size' => translate('Send one quantity setting for each category'),
            'multiply_qty.*.required' => translate('Send one quantity setting for each category'),
            'multiply_qty.*.boolean' => translate('This setting can only be turned on or off'),
        ];
    }
}
