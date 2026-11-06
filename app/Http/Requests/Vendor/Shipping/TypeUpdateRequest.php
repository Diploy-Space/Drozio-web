<?php

namespace App\Http\Requests\Vendor\Shipping;

use App\Http\Requests\BaseRequest;
use App\Services\New\ShippingTypeService;
use Illuminate\Validation\Rule;

class TypeUpdateRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('shippingType') && $this->has('shipping_type')) {
            $this->merge(['shippingType' => $this['shipping_type']]);
        }
    }

    public function rules(): array
    {
        return [
            'shippingType' => ['required', Rule::in(ShippingTypeService::SHIPPING_TYPES)],
        ];
    }

    public function messages(): array
    {
        return [
            'shippingType.required' => translate('Choose a shipping type'),
            'shippingType.in' => translate('This shipping type is not available'),
        ];
    }
}
