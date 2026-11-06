<?php

namespace App\Http\Requests\Admin\Vendor\Verification;

use App\Http\Requests\BaseRequest;

class GrantRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'vendor_ids' => 'required|array|min:1',
            'vendor_ids.*' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_ids.required' => translate('Select at least one vendor to give the verified badge'),
            'vendor_ids.array' => translate('Select at least one vendor to give the verified badge'),
            'vendor_ids.min' => translate('Select at least one vendor to give the verified badge'),
            'vendor_ids.*.required' => translate('The selected vendor is not valid'),
            'vendor_ids.*.integer' => translate('The selected vendor is not valid'),
        ];
    }
}
