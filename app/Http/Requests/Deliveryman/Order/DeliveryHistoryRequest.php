<?php

namespace App\Http\Requests\Deliveryman\Order;

use App\Http\Requests\BaseRequest;

class DeliveryHistoryRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['order_id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('The order id is required'),
            'order_id.integer' => translate('The order id must be a whole number'),
            'limit.integer' => translate('The number of records per page must be a whole number'),
            'limit.min' => translate('The number of records per page must be at least one'),
            'offset.integer' => translate('The page number must be a whole number'),
            'offset.min' => translate('The page number must be at least one'),
        ];
    }
}
