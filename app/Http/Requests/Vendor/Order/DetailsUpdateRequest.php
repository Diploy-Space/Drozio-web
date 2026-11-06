<?php

namespace App\Http\Requests\Vendor\Order;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\Shared\Order\StatusUpdateRequest;
use App\Services\New\OrderService;
use Illuminate\Validation\Rule;

class DetailsUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        $paymentStatusRule = ['required', Rule::in([OrderService::PAYMENT_STATUS_PAID, OrderService::PAYMENT_STATUS_UNPAID])];

        if (! $this->isApiRequest()) {
            return [
                'order_id' => 'required',
                'payment_status' => $paymentStatusRule,
            ];
        }

        return [
            'payment_status' => $paymentStatusRule,
            'order_status' => ['nullable', 'string', Rule::in(StatusUpdateRequest::ORDER_STATUSES)],
            'delivery_type' => ['nullable', 'string', 'max:255'],
            'delivery_man_id' => ['nullable', 'integer', 'min:1'],
            'deliveryman_charge' => ['nullable', 'numeric', 'max:'.self::MAX_NUMERIC_VALUE],
            'expected_delivery_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order is required'),
            'payment_status.required' => translate('Choose a payment status'),
            'payment_status.in' => translate('Payment status must be paid or unpaid'),
            'order_status.string' => translate('Choose a valid order status'),
            'order_status.in' => translate('Choose a valid order status'),
            'delivery_type.string' => translate('Choose a valid delivery type'),
            'delivery_type.max' => translate('Choose a valid delivery type'),
            'delivery_man_id.integer' => translate('Choose a valid delivery man'),
            'delivery_man_id.min' => translate('Choose a valid delivery man'),
            'deliveryman_charge.numeric' => translate('The deliveryman charge must be a number'),
            'deliveryman_charge.max' => translate('The deliveryman charge is too large'),
            'expected_delivery_date.date_format' => translate('Enter the expected delivery date as year-month-day'),
        ];
    }
}
