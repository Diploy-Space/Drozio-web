<?php

namespace App\Http\Requests\Vendor\Order;

use App\Http\Requests\BaseRequest;

class ListRequest extends BaseRequest
{
    private const JSON_LIST_FIELDS = ['order_current_status', 'payment_status', 'order_types', 'order_amount_settlement'];

    protected function prepareForValidation(): void
    {
        $this->decodeJsonEncodedArrays(fieldPatterns: self::JSON_LIST_FIELDS);
    }

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:1'],
            'search_value' => ['nullable', 'string'],
            'filter' => ['nullable', 'string'],
            'date_type' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'delivery_man_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'order_current_status' => ['nullable', 'array'],
            'order_current_status.*' => ['string'],
            'payment_status' => ['nullable', 'array'],
            'payment_status.*' => ['string'],
            'order_types' => ['nullable', 'array'],
            'order_types.*' => ['string'],
            'order_amount_settlement' => ['nullable', 'array'],
            'order_amount_settlement.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => translate('The number of orders per page must be a whole number'),
            'limit.min' => translate('The number of orders per page must be at least one'),
            'offset.integer' => translate('The page number must be a whole number'),
            'offset.min' => translate('The page number must be at least one'),
            'search_value.string' => translate('Enter the search text as plain text'),
            'filter.string' => translate('Choose a valid order source'),
            'date_type.string' => translate('Choose a valid date range'),
            'start_date.date' => translate('Enter a valid start date'),
            'end_date.date' => translate('Enter a valid end date'),
            'delivery_man_id.integer' => translate('Choose a valid delivery man'),
            'customer_id.integer' => translate('Choose a valid customer'),
            'order_current_status.array' => translate('Send the order statuses as a list'),
            'order_current_status.*.string' => translate('Each order status must be text'),
            'payment_status.array' => translate('Send the payment statuses as a list'),
            'payment_status.*.string' => translate('Each payment status must be text'),
            'order_types.array' => translate('Send the order types as a list'),
            'order_types.*.string' => translate('Each order type must be text'),
            'order_amount_settlement.array' => translate('Send the order amount settlements as a list'),
            'order_amount_settlement.*.string' => translate('Each order amount settlement must be text'),
        ];
    }
}
