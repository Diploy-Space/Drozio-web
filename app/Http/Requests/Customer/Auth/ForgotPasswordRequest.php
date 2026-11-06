<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ForgotPasswordRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'identity' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'identity.required' => translate('Identity is required'),
        ];
    }
}
