<?php

namespace App\Http\Requests\Shared\Auth;

use App\Http\Requests\BaseRequest;

class FirebaseSessionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'identity' => ['required', 'string', 'max:100'],
            'session_info' => ['required', 'string', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'identity.required' => translate('Enter the phone number or email address'),
            'identity.max' => translate('The phone number or email address is too long'),
            'session_info.required' => translate('The verification session is required'),
            'session_info.max' => translate('The verification session is too long'),
        ];
    }
}
