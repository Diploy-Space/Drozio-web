<?php

namespace App\Http\Requests\Admin\Auth;

use App\Http\Requests\BaseRequest;

class ForgotPasswordRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => translate('Email is required'),
            'email.email' => translate('Enter a valid email address'),
            'email.max' => translate('Email must not be longer than 100 characters'),
        ];
    }
}
