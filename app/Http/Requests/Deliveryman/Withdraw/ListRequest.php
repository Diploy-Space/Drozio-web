<?php

namespace App\Http\Requests\Deliveryman\Withdraw;

use App\Http\Requests\BaseRequest;
use App\Services\New\WithdrawRequestService;
use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in([WithdrawRequestService::STATUS_PENDING, WithdrawRequestService::STATUS_APPROVED, WithdrawRequestService::STATUS_DENIED])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => translate('The withdraw status must be pending, approved or denied'),
            'start_date.date' => translate('Enter a valid start date'),
            'end_date.date' => translate('Enter a valid end date'),
            'end_date.after_or_equal' => translate('The end date must not be before the start date'),
        ];
    }
}
