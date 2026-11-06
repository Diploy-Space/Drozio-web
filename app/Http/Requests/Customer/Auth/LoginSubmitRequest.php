<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class LoginSubmitRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required',
            'password' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => translate('The email or phone field is required'),
            'password.required' => translate('The password field is required'),
        ];
    }
}
