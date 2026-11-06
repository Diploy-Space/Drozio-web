<?php

namespace App\Http\Requests\Shared\Report\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderReportService;
use Illuminate\Validation\Rule;

class FilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'date_type' => ['nullable', Rule::in(OrderReportService::DATE_TYPES)],
            'from' => ['nullable', 'required_if:date_type,'.OrderReportService::DATE_TYPE_CUSTOM, 'date'],
            'to' => ['nullable', 'required_if:date_type,'.OrderReportService::DATE_TYPE_CUSTOM, 'date', 'after_or_equal:from'],
            'seller_id' => ['nullable', Rule::when(! in_array($this['seller_id'], [OrderReportService::SELLER_ALL, OrderReportService::SELLER_IN_HOUSE], true), ['integer', 'min:1'])],
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
            'seller_id.integer' => translate('Choose a valid vendor'),
            'seller_id.min' => translate('Choose a valid vendor'),
            'search.string' => translate('Enter a valid search term'),
            'search.max' => translate('The search term is too long'),
            'page.integer' => translate('Choose a valid page'),
            'page.min' => translate('Choose a valid page'),
        ];
    }

    public function dateType(): string
    {
        return $this->validated('date_type') ?? OrderReportService::DATE_TYPE_THIS_YEAR;
    }

    public function sellerId(): int|string|null
    {
        $sellerId = $this->validated('seller_id');

        return match ($sellerId) {
            null, OrderReportService::SELLER_ALL => null,
            OrderReportService::SELLER_IN_HOUSE => OrderReportService::SELLER_IN_HOUSE,
            default => (int) $sellerId,
        };
    }
}
