<?php

namespace App\Http\Requests\Admin\Profile;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
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
            'password.required' => translate(':field is required', ['field' => translate('Password')]),
            'confirm_password.required' => translate(':field is required', ['field' => translate('Confirm password')]),
            'password.same' => translate('The password and confirm password must match'),
            'password.regex' => translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.',
        ];
    }
}
