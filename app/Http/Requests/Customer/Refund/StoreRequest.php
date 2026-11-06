<?php

namespace App\Http\Requests\Customer\Refund;

use App\Http\Requests\BaseRequest;

class StoreRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_details_id' => 'required|integer',
            'amount' => 'required|numeric|min:0',
            'refund_reason' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'order_details_id.required' => translate('Order item is required'),
            'order_details_id.integer' => translate('Please select a valid order item'),
            'amount.required' => translate('Refund amount is required'),
            'amount.numeric' => translate('Please enter a valid refund amount'),
            'amount.min' => translate('Please enter a valid refund amount'),
            'refund_reason.required' => translate('Refund reason is required'),
            'refund_reason.string' => translate('Refund reason is required'),
            'images.array' => translate('Please upload a valid image'),
            'images.*.mimes' => translate('Image type must be').': '.getFileUploadFormats(skip: ['.svg'], asMessage: true),
            'images.*.max' => translate('Image size must not exceed').' '.getFileUploadMaxSize().'MB',
        ];
    }
}
