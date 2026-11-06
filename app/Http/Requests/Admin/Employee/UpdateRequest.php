<?php

namespace App\Http\Requests\Admin\Employee;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = false;

    public function rules(): array
    {
        $rules = [
            'name' => 'required',
            'role_id' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('admins', 'email')->ignore($this->route('id')),
            ],
            'image' => getRulesStringForImageValidation(
                rules: ['sometimes', 'image'],
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
        ];
        if ($this['password']) {
            $rules['password'] = 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!.*\s).{8,}$/|same:confirm_password';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => translate(':field is required', ['field' => translate('Name')]),
            'role_id.required' => translate(':field is required', ['field' => translate('Role')]),
            'email.required' => translate(':field is required', ['field' => translate('Email')]),
            'email.email' => translate('Email must be valid'),
            'email.unique' => translate('Email has already been taken'),
            'identity_image.*.mimes' => translate('Each identity image must be a file of type').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: true),
            'identity_image.*.max' => translate('Each identity image may not be greater than').getFileUploadMaxSize().'MB',
            'image.mimes' => translate('The image type must be').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: 'true'),
            'image.max' => translate('The image may not be greater than').getFileUploadMaxSize().'MB',
            'password.regex' => translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.',
        ];
    }
}
