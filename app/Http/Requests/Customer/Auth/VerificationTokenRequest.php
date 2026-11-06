<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class VerificationTokenRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'token' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => translate(':field is required', ['field' => translate('Token')]),
        ];
    }
}
