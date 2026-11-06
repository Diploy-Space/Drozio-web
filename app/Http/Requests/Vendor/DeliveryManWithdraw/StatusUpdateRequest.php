<?php

namespace App\Http\Requests\Vendor\DeliveryManWithdraw;

use App\Http\Requests\BaseRequest;
use App\Services\New\WithdrawRequestService;
use Illuminate\Validation\Rule;

class StatusUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'approved' => ['required', Rule::in([WithdrawRequestService::APPROVAL_APPROVED, WithdrawRequestService::APPROVAL_DENIED])],
            'transaction_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'approved.required' => translate('Choose whether the request is approved or denied'),
            'approved.in' => translate('Choose whether the request is approved or denied'),
            'transaction_note.max' => translate('The note cannot be longer than 500 characters'),
        ];
    }
}
