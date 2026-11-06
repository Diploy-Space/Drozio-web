<?php

namespace App\Http\Requests\Deliveryman\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{
    private const DATE_TYPES = [
        OrderService::DATE_TYPE_TODAY,
        OrderService::DATE_TYPE_THIS_WEEK,
        OrderService::DATE_TYPE_THIS_MONTH,
        OrderService::DATE_TYPE_THIS_YEAR,
        OrderService::DATE_TYPE_CUSTOM,
    ];

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'is_pause' => ['nullable', Rule::in(['0', '1'])],
            'date_type' => ['nullable', Rule::in(self::DATE_TYPES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => translate('The number of orders per page must be a whole number'),
            'limit.min' => translate('The number of orders per page must be at least one'),
            'offset.integer' => translate('The page number must be a whole number'),
            'offset.min' => translate('The page number must be at least one'),
            'search.string' => translate('Enter the search text as plain text'),
            'search.max' => translate('The search text is too long'),
            'status.string' => translate('Choose a valid order status'),
            'status.max' => translate('Choose a valid order status'),
            'is_pause.in' => translate('The pause filter must be 0 or 1'),
            'date_type.in' => translate('Choose a valid date range'),
            'start_date.date' => translate('Enter a valid start date'),
            'end_date.date' => translate('Enter a valid end date'),
        ];
    }

    public function isPause(): ?int
    {
        $isPause = $this->validated('is_pause');

        return $isPause === null ? null : (int) $isPause;
    }

    public function dateRange(): array
    {
        $startDate = $this->validated('start_date');
        $endDate = $this->validated('end_date');

        if (empty($startDate) || empty($endDate)) {
            return [];
        }

        return [Carbon::parse($startDate)->format('Y-m-d'), Carbon::parse($endDate)->format('Y-m-d')];
    }
}
