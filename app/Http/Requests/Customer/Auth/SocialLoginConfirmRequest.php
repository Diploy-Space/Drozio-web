<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SocialLoginConfirmRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'identity' => 'required',
            'phone' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => translate('Please use a phone number'),
        ];
    }
}
