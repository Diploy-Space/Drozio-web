<?php

namespace App\Http\Requests\Customer\Address;

use App\Http\Requests\BaseRequest;

class DeleteRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'address_id' => 'required',
            ];
        }

        return [];
    }

    public function messages(): array
    {
        return [
            'address_id.required' => translate(':field is required', ['field' => translate('Address')]),
        ];
    }
}
