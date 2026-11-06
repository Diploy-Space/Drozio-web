<?php

namespace App\Http\Requests\Shared\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderService;
use Illuminate\Validation\Rule;

class StatusUpdateRequest extends BaseRequest
{
    public const ORDER_STATUSES = [
        OrderService::STATUS_PENDING,
        OrderService::STATUS_CONFIRMED,
        OrderService::STATUS_PROCESSING,
        OrderService::STATUS_OUT_FOR_DELIVERY,
        OrderService::STATUS_DELIVERED,
        OrderService::STATUS_RETURNED,
        OrderService::STATUS_FAILED,
        OrderService::STATUS_CANCELED,
    ];

    public function rules(): array
    {
        return [
            'order_status' => ['required', 'string', Rule::in(self::ORDER_STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'order_status.required' => translate('Choose an order status'),
            'order_status.string' => translate('Choose a valid order status'),
            'order_status.in' => translate('Choose a valid order status'),
        ];
    }
}
