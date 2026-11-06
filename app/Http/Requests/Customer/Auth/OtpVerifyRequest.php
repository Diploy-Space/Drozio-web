<?php

namespace App\Http\Requests\Customer\Auth;

use App\Enums\OtpPurpose;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class OtpVerifyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'identity' => 'required|string|max:100',
            'otp' => 'required|string|max:10',
            'purpose' => ['nullable', Rule::in(OtpPurpose::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'identity.required' => translate('Phone number or email is required'),
            'identity.max' => translate('Phone number or email must not be longer than 100 characters'),
            'otp.required' => translate('OTP is required'),
            'otp.max' => translate('OTP must not be longer than 10 characters'),
            'purpose.in' => translate('The OTP purpose must be login, verification or registration'),
        ];
    }
}
