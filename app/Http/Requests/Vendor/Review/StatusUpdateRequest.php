<?php

namespace App\Http\Requests\Vendor\Review;

use App\Http\Requests\BaseRequest;

class StatusUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => translate('Choose a review status'),
            'status.in' => translate('Choose a valid review status'),
        ];
    }
}
