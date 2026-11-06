<?php

namespace App\Http\Requests\Shared\Report\OrderTransaction;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderTransactionReportService;
use Illuminate\Validation\Rule;

class FilterRequest extends BaseRequest
{
    private const OPTION_ALL = 'all';

    public function rules(): array
    {
        return [
            'date_type' => ['nullable', Rule::in(OrderTransactionReportService::DATE_TYPES)],
            'from' => ['nullable', 'required_if:date_type,'.OrderTransactionReportService::DATE_TYPE_CUSTOM, 'date'],
            'to' => ['nullable', 'required_if:date_type,'.OrderTransactionReportService::DATE_TYPE_CUSTOM, 'date', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in([self::OPTION_ALL, ...OrderTransactionReportService::STATUSES])],
            'seller_id' => ['nullable', Rule::when(! in_array($this['seller_id'], [self::OPTION_ALL, OrderTransactionReportService::SELLER_IN_HOUSE], true), ['integer', 'min:1'])],
            'customer_id' => ['nullable', Rule::when($this['customer_id'] !== self::OPTION_ALL, ['integer', 'min:1'])],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_type.in' => translate('Choose a valid date range'),
            'from.required_if' => translate('Select a start date for the custom date range'),
            'from.date' => translate('Enter a valid start date'),
            'to.required_if' => translate('Select an end date for the custom date range'),
            'to.date' => translate('Enter a valid end date'),
            'to.after_or_equal' => translate('The end date must be on or after the start date'),
            'status.in' => translate('Choose a valid transaction status'),
            'seller_id.integer' => translate('Choose a valid vendor'),
            'seller_id.min' => translate('Choose a valid vendor'),
            'customer_id.integer' => translate('Choose a valid customer'),
            'customer_id.min' => translate('Choose a valid customer'),
            'search.string' => translate('Enter a valid search term'),
            'search.max' => translate('The search term is too long'),
            'page.integer' => translate('Choose a valid page'),
            'page.min' => translate('Choose a valid page'),
        ];
    }

    public function dateType(): string
    {
        return $this->validated('date_type') ?? OrderTransactionReportService::DATE_TYPE_THIS_YEAR;
    }

    public function reportFilters(): array
    {
        $status = $this->validated('status');
        $sellerId = $this->validated('seller_id');
        $customerId = $this->validated('customer_id');

        return array_filter([
            OrderTransactionReportService::FILTER_STATUS => $status === self::OPTION_ALL ? null : $status,
            OrderTransactionReportService::FILTER_SELLER_ID => match (true) {
                $sellerId === null, $sellerId === self::OPTION_ALL => null,
                $sellerId === OrderTransactionReportService::SELLER_IN_HOUSE => OrderTransactionReportService::SELLER_IN_HOUSE,
                default => (int) $sellerId,
            },
            OrderTransactionReportService::FILTER_CUSTOMER_ID => $customerId === null || $customerId === self::OPTION_ALL ? null : (int) $customerId,
        ], static fn ($value): bool => $value !== null);
    }
}
