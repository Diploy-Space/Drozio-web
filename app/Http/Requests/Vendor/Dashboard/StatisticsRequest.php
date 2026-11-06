<?php

namespace App\Http\Requests\Vendor\Dashboard;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderTransactionService;
use Illuminate\Validation\Rule;

class StatisticsRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(OrderTransactionService::STATISTICS_TYPES)],
            'statistics_type' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.string' => translate('Choose a valid earning statistics period'),
            'type.in' => translate('Choose a valid earning statistics period'),
            'statistics_type.string' => translate('Choose a valid order statistics period'),
        ];
    }
}
