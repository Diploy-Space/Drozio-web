<?php

namespace App\Http\Requests\Admin\Report\Tax;

use App\Services\New\TaxReportService;
use Illuminate\Validation\Rule;

class DetailsRequest extends FilterRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'source' => ['required', Rule::in(TaxReportService::SOURCES)],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'source.required' => translate('Choose an income source'),
            'source.in' => translate('Choose a valid income source'),
        ];
    }
}
