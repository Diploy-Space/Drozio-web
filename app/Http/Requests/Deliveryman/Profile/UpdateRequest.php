<?php

namespace App\Http\Requests\Deliveryman\Profile;

use App\Http\Requests\BaseRequest;

class UpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'f_name' => 'required|string|max:100',
            'l_name' => 'required|string|max:100',
            'address' => 'nullable|string|max:1000',
            'password' => 'nullable|string|min:8|same:confirm_password',
            'confirm_password' => 'nullable|string|min:8',
            'image' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'f_name.required' => translate(':field is required', ['field' => translate('First name')]),
            'f_name.max' => translate('First name must not be longer than 100 characters'),
            'l_name.required' => translate(':field is required', ['field' => translate('Last name')]),
            'l_name.max' => translate('Last name must not be longer than 100 characters'),
            'address.max' => translate('Address must not be longer than 1000 characters'),
            'password.min' => translate('Password must be at least 8 characters'),
            'password.same' => translate('Password and confirm password do not match'),
            'image.mimes' => translate('The image type must be').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: true),
            'image.max' => translate('Image size exceeds maximum size').' '.getFileUploadMaxSize().'MB',
        ];
    }
}
