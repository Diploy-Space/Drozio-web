<?php

namespace App\Http\Requests\Shared\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderService;
use Illuminate\Validation\Rule;

class PaymentStatusUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'payment_status' => ['required', Rule::in([OrderService::PAYMENT_STATUS_PAID, OrderService::PAYMENT_STATUS_UNPAID])],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_status.required' => translate('Choose a payment status'),
            'payment_status.in' => translate('Payment status must be paid or unpaid'),
        ];
    }
}
