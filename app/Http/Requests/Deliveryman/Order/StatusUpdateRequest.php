<?php

namespace App\Http\Requests\Deliveryman\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderService;
use Illuminate\Validation\Rule;

class StatusUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(OrderService::DELIVERYMAN_UPDATABLE_STATUSES)],
            'cause' => 'nullable|string|max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => translate('Choose an order status'),
            'status.string' => translate('Choose a valid order status'),
            'status.in' => translate('Choose a valid order status'),
            'cause.string' => translate('The cause must be text'),
            'cause.max' => translate('The cause must not be longer than 191 characters'),
        ];
    }
}
