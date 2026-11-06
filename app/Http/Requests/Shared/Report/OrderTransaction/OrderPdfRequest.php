<?php

namespace App\Http\Requests\Shared\Report\OrderTransaction;

use App\Http\Requests\BaseRequest;

class OrderPdfRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Choose an order to download'),
            'order_id.integer' => translate('Choose a valid order'),
            'order_id.min' => translate('Choose a valid order'),
        ];
    }
}
