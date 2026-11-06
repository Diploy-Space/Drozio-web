<?php

namespace App\Http\Requests\Shared\Order;

use App\Http\Requests\BaseRequest;

class DeliveryManAssignRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->isApiRequest()) {
            $this->merge(['delivery_man_id' => $this->route('d_man_id')]);
        }
    }

    public function rules(): array
    {
        return [
            'delivery_man_id' => ['required', 'integer', $this->isApiRequest() ? 'min:1' : 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_man_id.required' => translate('Choose a delivery man for this order'),
            'delivery_man_id.integer' => translate('Choose a valid delivery man'),
            'delivery_man_id.min' => translate('Choose a valid delivery man'),
        ];
    }
}
