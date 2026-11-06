<?php

namespace App\Http\Requests\Vendor\Profile;

use App\Http\Requests\BaseRequest;

class FcmTokenRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'cm_firebase_token' => ['required', 'string', 'max:512'],
        ];
    }

    public function messages(): array
    {
        return [
            'cm_firebase_token.required' => translate('The device token is required'),
            'cm_firebase_token.string' => translate('The device token must be text'),
            'cm_firebase_token.max' => translate('The device token must not be longer than 512 characters'),
        ];
    }
}
