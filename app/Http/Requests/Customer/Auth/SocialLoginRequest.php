<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;
use App\Services\New\SocialProfileService;
use Illuminate\Validation\Rule;

class SocialLoginRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'unique_id' => 'required|string|max:191',
            'medium' => ['required', Rule::in(SocialProfileService::mediums())],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => translate('The sign-in token from the provider is missing'),
            'unique_id.required' => translate('The account identifier from the provider is missing'),
            'unique_id.max' => translate('The account identifier must not be longer than 191 characters'),
            'medium.required' => translate('Choose a sign-in provider'),
            'medium.in' => translate('Sign in is available with Google, Facebook or Apple only'),
        ];
    }
}
