<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class UpdatePhoneRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'temporary_token' => 'required|string|max:191',
            'phone' => 'required|string|min:11|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'temporary_token.required' => translate('This sign-in session has expired. Please sign in again'),
            'phone.required' => translate('Phone number is required'),
            'phone.min' => translate('Enter a valid phone number'),
            'phone.max' => translate('Phone number must not be longer than 20 characters'),
        ];
    }
}
