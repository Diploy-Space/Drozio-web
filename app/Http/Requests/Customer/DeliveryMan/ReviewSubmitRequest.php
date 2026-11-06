<?php

namespace App\Http\Requests\Customer\DeliveryMan;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ReviewSubmitRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'comment' => 'required|string',
            'rating' => 'required|numeric|min:1|max:5',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate(':field is required', ['field' => translate('Order')]),
            'order_id.exists' => translate('Invalid order'),
            'comment.required' => translate(':field is required', ['field' => translate('Comment')]),
            'rating.required' => translate(':field is required', ['field' => translate('Rating')]),
            'rating.min' => translate('Rating must be at least 1.'),
            'rating.max' => translate('Rating may not be greater than 5.'),
        ];
    }
}
