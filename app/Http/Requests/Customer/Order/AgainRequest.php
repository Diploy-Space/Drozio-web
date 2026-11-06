<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;

class AgainRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [];
        }

        return [
            'order_id' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate(':field is required', ['field' => translate('Order')]),
        ];
    }
}
