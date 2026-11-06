<?php

namespace App\Http\Requests\Deliveryman\Order;

use App\Http\Requests\BaseRequest;

class LastLocationRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['order_id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('The order id is required'),
            'order_id.integer' => translate('The order id must be a whole number'),
        ];
    }
}
