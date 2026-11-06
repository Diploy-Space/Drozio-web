<?php

namespace App\Http\Requests\Customer\DeliveryMan;

use App\Http\Requests\BaseRequest;

class ReviewUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_id' => 'required|integer',
            'comment' => 'required',
            'rating' => 'required|integer|min:1|max:5',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order ID is required.'),
            'order_id.integer' => translate('Please select a valid order'),
            'comment.required' => translate('Please write a comment'),
            'rating.required' => translate('Please select rating'),
            'rating.integer' => translate('Please rate the quality between 1 and 5 stars'),
            'rating.min' => translate('Rating must be at least 1.'),
            'rating.max' => translate('Rating may not be greater than 5.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('orderId') !== null) {
            $this->merge(['order_id' => $this->route('orderId')]);
        }
    }
}
