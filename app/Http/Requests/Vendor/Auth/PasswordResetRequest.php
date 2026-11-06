<?php

namespace App\Http\Requests\Vendor\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordResetRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'identity' => 'required',
        ];

        if ($this->isApiRequest()) {
            $rules['identity'] = 'required|min:6';
        }

        return $rules;
    }

    public function messages(): array
    {
        $verificationBy = getWebConfig('vendor_forgot_password_method') ?? 'phone';

        return [
            'identity.required' => $verificationBy == 'email' ? translate('Please enter email address') : translate('Enter phone number'),
        ];
    }
}
