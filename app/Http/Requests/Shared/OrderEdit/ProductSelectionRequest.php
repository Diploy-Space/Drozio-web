<?php

namespace App\Http\Requests\Shared\OrderEdit;

use App\Http\Requests\BaseRequest;

class ProductSelectionRequest extends BaseRequest
{
    private const CHOICE_FIELD_PATTERN = '/^choice_\d+$/';

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'product_id' => ['required', 'integer'],
            'variant' => ['nullable', 'string', 'max:255'],
            'variant_key' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            ...array_fill_keys(array_keys($this->getChoiceValues()), ['nullable', 'string', 'max:255']),
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order is required'),
            'order_id.integer' => translate('The selected order is invalid'),
            'product_id.required' => translate('Choose a product'),
            'product_id.integer' => translate('Choose a valid product'),
            'variant.string' => translate('Choose a valid variant'),
            'variant.max' => translate('Choose a valid variant'),
            'variant_key.string' => translate('Choose a valid variant'),
            'variant_key.max' => translate('Choose a valid variant'),
            'color.string' => translate('Choose a valid color'),
            'color.max' => translate('Choose a valid color'),
            'quantity.integer' => translate('The quantity must be a whole number'),
            'quantity.min' => translate('The quantity must be at least one'),
            'choice_*.string' => translate('Choose a valid option'),
            'choice_*.max' => translate('Choose a valid option'),
        ];
    }

    public function getChoiceValues(): array
    {
        return array_filter(
            $this->all(),
            static fn (string|int $field): bool => (bool) preg_match(self::CHOICE_FIELD_PATTERN, (string) $field),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
