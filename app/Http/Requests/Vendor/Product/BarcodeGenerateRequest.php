<?php

namespace App\Http\Requests\Vendor\Product;

use App\Http\Requests\BaseRequest;
use App\Services\New\ProductService;
use Illuminate\Validation\Rule;

class BarcodeGenerateRequest extends BaseRequest
{
    public function rules(): array
    {
        if (! $this->isApiRequest()) {
            return [
                'id' => 'required',
                'quantity' => 'required',
            ];
        }

        return [
            'quantity' => 'required|integer|min:1|max:'.ProductService::BARCODE_MAX_QUANTITY,
            'layout' => ['nullable', 'string', Rule::in(ProductService::BARCODE_LAYOUTS)],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Product ID is required'),
            'quantity.required' => translate('Barcode quantity is required'),
            'quantity.integer' => translate('Barcode quantity must be a whole number'),
            'quantity.min' => translate('Generate at least one barcode'),
            'quantity.max' => translate('You cannot generate more than 270 barcode'),
            'layout.string' => translate('Choose a valid barcode layout'),
            'layout.in' => translate('Choose a valid barcode layout'),
        ];
    }
}
