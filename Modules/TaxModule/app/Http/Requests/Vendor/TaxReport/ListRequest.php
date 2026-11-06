<?php

namespace Modules\TaxModule\app\Http\Requests\Vendor\TaxReport;

use App\Http\Requests\BaseRequest;

class ListRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date' => translate('Enter a valid start date'),
            'end_date.date' => translate('Enter a valid end date'),
            'end_date.after_or_equal' => translate('The end date must be on or after the start date'),
            'limit.integer' => translate('The number of rows per page must be a whole number'),
            'limit.min' => translate('The number of rows per page must be at least one'),
            'offset.integer' => translate('The page number must be a whole number'),
            'offset.min' => translate('The page number must be at least one'),
        ];
    }
}
