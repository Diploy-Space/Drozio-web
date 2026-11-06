<?php

namespace App\Http\Requests\Vendor\Auth;

use App\Http\Requests\BaseRequest;

class LoginRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'email' => 'required',
                'password' => 'required|min:6',
            ];
        }

        return [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ];
    }

    /**
     * @return array
     * Get the validation error message
     */
    public function messages(): array
    {
        $messages = [
            'email.required' => translate('The email field is required'),
            'password.required' => translate('The password field is required'),
            'password.min' => translate('The password must be at least 8 characters'),
        ];

        // The API accepts a 6-character password, so the 8-character text would be wrong there.
        if ($this->isApiRequest()) {
            unset($messages['password.min']);
        }

        return $messages;
    }
}
