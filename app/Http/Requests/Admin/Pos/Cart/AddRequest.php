<?php

namespace App\Http\Requests\Admin\Pos\Cart;

use App\Http\Requests\BaseRequest;
use App\Services\New\ProductRetailService;
use Illuminate\Validation\Validator;

class AddRequest extends BaseRequest
{
    public function __construct(
        private readonly ProductRetailService $productService,
    ) {}

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
            'quantity_in_cart' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate(':field is required', ['field' => translate('Product')]),
            'id.integer' => translate(':field must be a valid product', ['field' => translate('Product')]),
            'quantity.required' => translate(':field is required', ['field' => translate('Quantity')]),
            'quantity.integer' => translate(':field must be a whole number', ['field' => translate('Quantity')]),
            'quantity.min' => translate('The :field must be at least :min', ['field' => translate('Quantity'), 'min' => 1]),
            'quantity_in_cart.integer' => translate(':field must be a whole number', ['field' => translate('Cart quantity')]),
            'quantity_in_cart.min' => translate('The :field must be at least :min', ['field' => translate('Cart quantity'), 'min' => 1]),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('id') || ! $this->filled('id')) {
                    return;
                }

                if ($this->productService->find(id: $this->input('id')) === null) {
                    $validator->errors()->add('id', translate('Selected product could not be found'));
                }
            },
        ];
    }
}
