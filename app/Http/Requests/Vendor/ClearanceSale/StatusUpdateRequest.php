<?php

namespace App\Http\Requests\Vendor\ClearanceSale;

use App\Http\Requests\BaseRequest;

class StatusUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => translate(':field is required', ['field' => translate('Status')]),
            'status.boolean' => translate('The status must be either true or false'),
        ];
    }
}
