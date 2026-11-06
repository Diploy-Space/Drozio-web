<?php

namespace App\Http\Requests\Deliveryman\Order;

use App\Http\Requests\BaseRequest;

class VerificationCodeRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'verification_code' => 'required|digits:6',
        ];
    }

    public function messages(): array
    {
        return [
            'verification_code.required' => translate('Please enter the verification code'),
            'verification_code.digits' => translate('The verification code must be 6 digits'),
        ];
    }
}
