<?php

namespace App\Http\Requests\Shared\Report\ExpenseTransaction;

use App\Http\Requests\BaseRequest;

class OrderPdfRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Choose an order to download'),
            'id.integer' => translate('Choose a valid order'),
            'id.min' => translate('Choose a valid order'),
        ];
    }
}
