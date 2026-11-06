<?php

namespace App\Http\Requests\Admin\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\RefundRequestService;

class RefundStatusUpdateRequest extends BaseRequest
{
    public function __construct(
        private readonly RefundRequestService $refundRequestService,
    ) {}

    public function rules(): array
    {
        $status = $this->input('refund_status');

        $rules = [
            'id' => 'required',
            'refund_status' => 'required|in:pending,approved,rejected,refunded',
            'rejected_note' => $status == RefundRequestService::STATUS_REJECTED ? 'required' : '',
            'payment_method' => $status == RefundRequestService::STATUS_REFUNDED ? 'required' : '',
            'payment_info' => 'required_with:payment_method',
        ];

        if (
            in_array($status, [RefundRequestService::STATUS_APPROVED, RefundRequestService::STATUS_REFUNDED], true)
            && $this->refundRequestService->hasEmptyApprovedNote(id: $this->input('id'))
        ) {
            $rules['approved_note'] = 'required';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('The id field is required.'),
            'refund_status.required' => translate('The refund status field is required.'),
            'refund_status.in' => translate('The selected refund status is invalid.'),
            'approved_note.required' => translate('The approved note field is required'),
            'rejected_note.required' => translate('The rejected note field is required'),
            'payment_method.required' => translate('The payment method field is required'),
            'payment_info.required_with' => translate('The payment info field is required'),
        ];
    }
}
