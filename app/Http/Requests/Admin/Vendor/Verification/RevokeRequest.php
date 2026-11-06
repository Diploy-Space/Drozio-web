<?php

namespace App\Http\Requests\Admin\Vendor\Verification;

use App\Http\Requests\BaseRequest;

class RevokeRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'vendor_id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.required' => translate('Select a vendor to remove the verified badge'),
            'vendor_id.integer' => translate('The selected vendor is not valid'),
        ];
    }
}
