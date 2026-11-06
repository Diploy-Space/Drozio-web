<?php

namespace App\Http\Requests\Vendor\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\RefundRequestService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class RefundStatusUpdateRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'refund_status' => ['required', 'string', Rule::in([RefundRequestService::STATUS_APPROVED, RefundRequestService::STATUS_REJECTED])],
                'note' => ['nullable', 'required_if:refund_status,'.RefundRequestService::STATUS_REJECTED, 'string'],
            ];
        }

        return [
            'id' => 'required',
            'refund_status' => 'required|in:'.RefundRequestService::STATUS_APPROVED.','.RefundRequestService::STATUS_REJECTED,
            'approved_note' => $this->input('refund_status') == RefundRequestService::STATUS_APPROVED ? 'required' : '',
            'rejected_note' => $this->input('refund_status') == RefundRequestService::STATUS_REJECTED ? 'required' : '',
        ];
    }

    public function messages(): array
    {
        if ($this->isApiRequest()) {
            return [
                'refund_status.required' => translate('Choose a refund status'),
                'refund_status.string' => translate('Choose a valid refund status'),
                'refund_status.in' => translate('Choose a valid refund status'),
                'note.required_if' => translate('A note is required when rejecting a refund'),
                'note.string' => translate('Write the note as plain text'),
            ];
        }

        return [
            'id.required' => translate('Choose the refund request to update'),
            'refund_status.required' => translate('Choose a refund status'),
            'refund_status.in' => translate('Choose a valid refund status'),
            'approved_note.required' => translate('The approved note field is required'),
            'rejected_note.required' => translate('The rejected note field is required'),
        ];
    }
}
