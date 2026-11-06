<?php

namespace App\Http\Requests\Shared\Dashboard;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderService;
use Illuminate\Validation\Rule;

class OrderStatusRequest extends BaseRequest
{
    public const STATISTICS_TYPE_OVERALL = 'overall';

    public const STATISTICS_TYPES = [
        self::STATISTICS_TYPE_OVERALL,
        OrderService::DATE_TYPE_TODAY,
        OrderService::DATE_TYPE_THIS_WEEK,
        OrderService::DATE_TYPE_THIS_MONTH,
        OrderService::DATE_TYPE_THIS_YEAR,
    ];

    protected function prepareForValidation(): void
    {
        if ($this->route('type') !== null) {
            $this->merge(['statistics_type' => $this->route('type')]);
        }
    }

    public function rules(): array
    {
        return [
            'statistics_type' => ['required', 'string', Rule::in(self::STATISTICS_TYPES)],
        ];
    }

    public function messages(): array
    {
        return [
            'statistics_type.required' => translate('Choose a valid order statistics period'),
            'statistics_type.string' => translate('Choose a valid order statistics period'),
            'statistics_type.in' => translate('Choose a valid order statistics period'),
        ];
    }
}
