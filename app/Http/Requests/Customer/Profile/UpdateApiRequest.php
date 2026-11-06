<?php

namespace App\Http\Requests\Customer\Profile;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateApiRequest extends BaseRequest
{
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'f_name' => ['required', 'string', 'max:'.UpdateRequest::NAME_MAX_LENGTH],
            'l_name' => ['required', 'string', 'max:'.UpdateRequest::NAME_MAX_LENGTH],
            'phone' => [
                'required',
                'string',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'image' => getRulesStringForImageValidation(
                rules: ['nullable', 'image'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'f_name.required' => translate(':field is required', ['field' => translate('First Name')]),
            'f_name.max' => translate('First name may not be greater than :max characters', ['max' => UpdateRequest::NAME_MAX_LENGTH]),
            'l_name.required' => translate(':field is required', ['field' => translate('Last Name')]),
            'l_name.max' => translate('Last name may not be greater than :max characters', ['max' => UpdateRequest::NAME_MAX_LENGTH]),
            'phone.required' => translate(':field is required', ['field' => translate('Phone')]),
            'phone.unique' => translate('Phone number has already been taken'),
            'email.email' => translate('Invalid email format'),
            'email.unique' => translate('Email has already been taken'),
            'password.min' => translate('Password must be at least 6 characters'),
            'image.image' => translate('Invalid image file'),
            'image.max' => translate('Image size must not exceed').' '.getFileUploadMaxSize().' MB.',
            'image.mimes' => translate('Invalid image format type must be').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: true),
        ];
    }
}
