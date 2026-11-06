<?php

namespace App\Http\Requests\Shared\Report\ExpenseTransaction;

use App\Http\Requests\BaseRequest;
use App\Services\New\ExpenseTransactionReportService;
use Illuminate\Validation\Rule;

class FilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'date_type' => ['nullable', Rule::in(ExpenseTransactionReportService::DATE_TYPES)],
            'from' => ['nullable', 'required_if:date_type,'.ExpenseTransactionReportService::DATE_TYPE_CUSTOM, 'date'],
            'to' => ['nullable', 'required_if:date_type,'.ExpenseTransactionReportService::DATE_TYPE_CUSTOM, 'date', 'after_or_equal:from'],
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
            'search.string' => translate('Enter a valid search term'),
            'search.max' => translate('The search term is too long'),
            'page.integer' => translate('Choose a valid page'),
            'page.min' => translate('Choose a valid page'),
        ];
    }

    public function dateType(): string
    {
        return $this->validated('date_type') ?? ExpenseTransactionReportService::DATE_TYPE_THIS_YEAR;
    }
}
