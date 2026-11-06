<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderService;
use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{
    public function rules(): array
    {
        if (! $this->isApiRequest()) {
            return [
                'status' => ['nullable', Rule::in([OrderService::STATUS_GROUP_ALL, ...OrderService::CUSTOMER_LIST_STATUSES])],
                'page' => ['nullable', 'integer', 'min:1'],
            ];
        }

        return [
            'status' => ['nullable', Rule::in([OrderService::STATUS_GROUP_ALL, ...array_keys(OrderService::CUSTOMER_APP_STATUS_GROUPS)])],
            'type' => 'nullable|string',
            'limit' => 'nullable|integer|min:1',
            'offset' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => translate('Choose a valid order status'),
            'type.string' => translate('Choose a valid order type'),
            'limit.integer' => translate('The limit must be a whole number'),
            'limit.min' => translate('The limit must be at least 1'),
            'offset.integer' => translate('The page number must be a whole number'),
            'offset.min' => translate('The page number must be at least 1'),
            'page.integer' => translate('Please select a valid page'),
            'page.min' => translate('Please select a valid page'),
        ];
    }
}
