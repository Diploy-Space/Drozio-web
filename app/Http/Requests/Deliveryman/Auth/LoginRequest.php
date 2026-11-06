<?php

namespace App\Http\Requests\Deliveryman\Auth;

use App\Http\Requests\BaseRequest;

class LoginRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => translate('Phone number is required'),
            'phone.max' => translate('Phone number must not be longer than 20 characters'),
            'password.required' => translate('Password is required'),
            'password.min' => translate('Password must be at least 8 characters'),
        ];
    }
}
