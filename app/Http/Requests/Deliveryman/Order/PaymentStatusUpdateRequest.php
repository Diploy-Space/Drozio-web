<?php

namespace App\Http\Requests\Deliveryman\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderService;
use Illuminate\Validation\Rule;

class PaymentStatusUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'payment_status' => ['required', Rule::in([OrderService::PAYMENT_STATUS_PAID])],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_status.required' => translate('Payment status is required'),
            'payment_status.in' => translate('Please select a valid payment status'),
        ];
    }
}
