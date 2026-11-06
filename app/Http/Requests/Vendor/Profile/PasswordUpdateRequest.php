<?php

namespace App\Http\Requests\Vendor\Profile;

use App\Http\Requests\BaseRequest;

class PasswordUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'password' => 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!.*\s).{8,}$/|same:confirm_password',
            'confirm_password' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => translate('The password field is required'),
            'confirm_password.required' => translate('The confirm password field is required'),
            'password.same' => translate('The password and confirm password must match'),
            'password.regex' => translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.',
        ];
    }
}
