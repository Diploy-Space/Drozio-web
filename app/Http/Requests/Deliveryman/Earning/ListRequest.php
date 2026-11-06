<?php

namespace App\Http\Requests\Deliveryman\Earning;

use App\Http\Requests\BaseRequest;

class ListRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date' => translate('Enter a valid start date'),
            'end_date.date' => translate('Enter a valid end date'),
            'end_date.after_or_equal' => translate('The end date must not be before the start date'),
        ];
    }
}
