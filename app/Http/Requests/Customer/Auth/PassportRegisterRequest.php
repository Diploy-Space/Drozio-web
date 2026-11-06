<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;

class PassportRegisterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'required|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'f_name.required' => 'The first name field is required.',
            'l_name.required' => 'The last name field is required.',
        ];
    }
}
