<?php

namespace App\Http\Requests\Admin\Report\Earning;

use App\Http\Requests\BaseRequest;
use App\Services\New\EarningReportService;
use Illuminate\Validation\Rule;

class FilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'date_type' => ['nullable', Rule::in(EarningReportService::DATE_TYPES)],
            'from' => ['nullable', 'required_if:date_type,'.EarningReportService::DATE_TYPE_CUSTOM, 'date'],
            'to' => ['nullable', 'required_if:date_type,'.EarningReportService::DATE_TYPE_CUSTOM, 'date', 'after_or_equal:from'],
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
        ];
    }

    public function dateType(): string
    {
        return $this->validated('date_type') ?? EarningReportService::DATE_TYPE_THIS_YEAR;
    }
}
