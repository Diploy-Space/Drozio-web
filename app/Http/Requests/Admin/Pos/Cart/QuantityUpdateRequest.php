<?php

namespace App\Http\Requests\Admin\Pos\Cart;

use App\Http\Requests\BaseRequest;
use App\Services\New\ProductRetailService;
use Illuminate\Validation\Validator;

class QuantityUpdateRequest extends BaseRequest
{
    public function __construct(
        private readonly ProductRetailService $productService,
    ) {}

    public function rules(): array
    {
        return [
            'key' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
            'variant' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => translate(':field is required', ['field' => translate('Product')]),
            'key.integer' => translate(':field must be a valid product', ['field' => translate('Product')]),
            'quantity.required' => translate(':field is required', ['field' => translate('Quantity')]),
            'quantity.integer' => translate(':field must be a whole number', ['field' => translate('Quantity')]),
            'quantity.min' => translate('The :field must be at least :min', ['field' => translate('Quantity'), 'min' => 1]),
            'variant.string' => translate(':field must be text', ['field' => translate('Variant')]),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('key') || ! $this->filled('key')) {
                    return;
                }

                if ($this->productService->find(id: $this->input('key')) === null) {
                    $validator->errors()->add('key', translate('Selected product could not be found'));
                }
            },
        ];
    }
}
