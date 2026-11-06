<?php

namespace App\Http\Requests\Customer\Newsletter;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SubscribeRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'subscription_email' => 'required|string|max:255|email:rfc,filter',
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_email.required' => translate(':field is required', ['field' => translate('Email')]),
            'subscription_email.string' => translate('Please provide a valid email address'),
            'subscription_email.max' => translate('Please provide a valid email address'),
            'subscription_email.email' => translate('Please provide a valid email address'),
        ];
    }
}
