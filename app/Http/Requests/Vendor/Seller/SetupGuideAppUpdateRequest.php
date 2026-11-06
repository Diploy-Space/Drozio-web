<?php

namespace App\Http\Requests\Vendor\Seller;

use App\Http\Requests\BaseRequest;

class SetupGuideAppUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'key' => 'required|string',
            'value' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => translate('Choose a setup guide step'),
            'key.string' => translate('Choose a valid setup guide step'),
            'value.required' => translate('Enter the setup guide step status'),
        ];
    }
}
