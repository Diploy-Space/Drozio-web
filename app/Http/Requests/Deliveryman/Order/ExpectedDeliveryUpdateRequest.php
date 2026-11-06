<?php

namespace App\Http\Requests\Deliveryman\Order;

use App\Http\Requests\BaseRequest;

class ExpectedDeliveryUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'expected_delivery_date' => 'required|date',
            'cause' => 'nullable|string|max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'expected_delivery_date.required' => translate('Expected delivery date is required'),
            'expected_delivery_date.date' => translate('Expected delivery date must be a valid date'),
            'cause.string' => translate('The cause must be text'),
            'cause.max' => translate('The cause must not be longer than 191 characters'),
        ];
    }
}
