<?php

namespace App\Http\Requests\Admin\Pos\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\Pos\PosCartService;
use Illuminate\Validation\Rule;

class PlaceRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([PosCartService::PAYMENT_TYPE_CASH, PosCartService::PAYMENT_TYPE_CARD, PosCartService::PAYMENT_TYPE_WALLET])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'paid_amount' => ['exclude_unless:type,'.PosCartService::PAYMENT_TYPE_CASH, 'required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => translate(':field is required', ['field' => translate('Payment type')]),
            'type.in' => translate('Payment type must be cash, card or wallet'),
            'amount.required' => translate(':field is required', ['field' => translate('Amount')]),
            'amount.numeric' => translate('Amount must be a number'),
            'amount.gt' => translate('Amount must be greater than 0'),
            'paid_amount.required' => translate(':field is required', ['field' => translate('Paid amount')]),
            'paid_amount.numeric' => translate('Paid amount must be a number'),
        ];
    }
}
