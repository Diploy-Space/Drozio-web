<?php

namespace App\Http\Requests\Admin\Employee;

use App\Http\Requests\BaseRequest;

class AddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = false;

    public function rules(): array
    {
        return [
            'name' => 'required',
            'role_id' => 'required',
            'image' => getRulesStringForImageValidation(
                rules: ['required', 'image'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'identity_image.*' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'email' => 'required|email|unique:admins',
            'password' => 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!.*\s).{8,}$/|same:confirm_password',
            'phone' => 'required|min:4|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate(':field is required', ['field' => translate('Name')]),
            'role_id.required' => translate(':field is required', ['field' => translate('Role')]),
            'image.required' => translate(':field is required', ['field' => translate('Image')]),
            'image.mimes' => translate('The image type must be').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: 'true'),
            'image.max' => translate('The image may not be greater than').getFileUploadMaxSize().'MB',
            'identity_image.*.mimes' => translate('Each identity image must be a file of type').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: true),
            'identity_image.*.max' => translate('Each identity image may not be greater than').getFileUploadMaxSize().'MB',
            'email.required' => translate(':field is required', ['field' => translate('Email')]),
            'email.email' => translate('Email must be valid'),
            'email.unique' => translate('Email has already been taken'),
            'password.required' => translate(':field is required', ['field' => translate('Password')]),
            'password.regex' => translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.',
            'phone.required' => translate(':field is required', ['field' => translate('Phone')]),
            'phone.max' => translate('Enter a valid phone number of up to 20 characters.'),
            'phone.min' => translate('Phone number with a minimum length requirement of 4 characters'),
        ];
    }
}
