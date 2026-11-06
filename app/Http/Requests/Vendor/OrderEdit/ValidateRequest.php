<?php

namespace App\Http\Requests\Vendor\OrderEdit;

use App\Http\Requests\BaseRequest;

class ValidateRequest extends BaseRequest
{
    private const JSON_LIST_FIELDS = ['products'];

    protected function prepareForValidation(): void
    {
        $this->decodeJsonEncodedArrays(fieldPatterns: self::JSON_LIST_FIELDS);
    }

    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1'],
            'products.*' => ['array'],
            'products.*.id' => ['required', 'integer', 'min:1'],
            'products.*.quantity' => ['nullable', 'integer', 'min:1'],
            'products.*.variant' => ['nullable', 'string', 'max:255'],
            'products.*.color' => ['nullable', 'string', 'max:255'],
            'products.*.choice' => ['nullable', 'array'],
            'products.*.choice.*.key' => ['required', 'string'],
            'products.*.choice.*.value' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'products.required' => translate('Add at least one product to the order'),
            'products.array' => translate('Send the products as a list'),
            'products.min' => translate('Add at least one product to the order'),
            'products.*.array' => translate('Each product must be sent with its id and quantity'),
            'products.*.id.required' => translate('Choose a product for every line'),
            'products.*.id.integer' => translate('Choose a valid product'),
            'products.*.id.min' => translate('Choose a valid product'),
            'products.*.quantity.integer' => translate('The quantity must be a whole number'),
            'products.*.quantity.min' => translate('The quantity must be at least one'),
            'products.*.variant.string' => translate('Choose a valid variant'),
            'products.*.variant.max' => translate('Choose a valid variant'),
            'products.*.color.string' => translate('Choose a valid color'),
            'products.*.color.max' => translate('Choose a valid color'),
            'products.*.choice.array' => translate('Send the product choices as a list'),
            'products.*.choice.*.key.required' => translate('Every product choice needs its option name'),
            'products.*.choice.*.key.string' => translate('Every product choice needs its option name'),
            'products.*.choice.*.value.required' => translate('Every product choice needs a selected value'),
        ];
    }
}
