<?php

namespace App\Http\Requests\Customer\Review;

use App\Http\Requests\BaseRequest;

class DeliverymanAddRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'order_id' => 'required|integer',
                'comment' => 'required|string',
                'rating' => 'required|numeric|min:1|max:5',
            ];
        }

        return [
            'order_id' => 'required|integer',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order ID is required.'),
            'order_id.integer' => translate('Please select a valid order'),
            'rating.required' => translate('Please select rating'),
            'rating.integer' => translate('Please select rating'),
            'rating.numeric' => translate('Please select rating'),
            'rating.between' => translate('Please select rating'),
            'rating.min' => translate('Rating must be at least 1.'),
            'rating.max' => translate('Rating may not be greater than 5.'),
            'comment.required' => translate('Please write a comment'),
            'comment.string' => translate('Please write a valid comment'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->isApiRequest() && $this->route('orderId') !== null) {
            $this->merge(['order_id' => $this->route('orderId')]);
        }
    }
}
