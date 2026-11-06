<?php

namespace App\Http\Requests\Vendor\Seller;

use App\Http\Requests\BaseRequest;

class RegistrationRequest extends BaseRequest
{
    public function rules(): array
    {
        $maxFileSize = getFileUploadMaxSize(unit: 'kb');

        return [
            'email' => 'required|email|unique:sellers,email',
            'shop_address' => 'required',
            'f_name' => 'required',
            'l_name' => 'required',
            'shop_name' => 'required',
            'phone' => 'required|unique:sellers,phone',
            'password' => 'required|min:8',
            'image' => getRulesStringForImageValidation(
                rules: ['required'],
                skipMimes: ['.svg', '.gif'],
                maxSize: $maxFileSize,
                isDisallowed: true),
            'logo' => getRulesStringForImageValidation(
                rules: ['required'],
                skipMimes: ['.svg', '.gif'],
                maxSize: $maxFileSize,
                isDisallowed: true
            ),
            'banner' => getRulesStringForImageValidation(
                rules: ['required'],
                skipMimes: ['.svg'],
                maxSize: $maxFileSize,
                isDisallowed: true
            ),
            'bottom_banner' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg'],
                maxSize: $maxFileSize,
                isDisallowed: true
            ),
            'tin_certificate' => 'nullable|mimes:pdf,doc,docx,jpg|max:'.$maxFileSize,
            'tax_identification_number' => 'nullable|string',
            'tin_expire_date' => 'nullable|date|after_or_equal:today',

        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => translate('Email is required.'),
            'email.unique' => translate('Email has already been taken'),
            'phone.unique' => translate('Phone number has already been taken'),
            'password.min' => translate('Password must be at least 8 characters.'),

            'image.required' => translate('Profile image is required.'),
            'image.mimes' => translate('The profile image must be a file of type:').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: true),
            'image.max' => translate('The profile image may not be greater than').getFileUploadMaxSize().' MB.',

            'logo.required' => translate('Shop logo is required.'),
            'logo.mimes' => translate('The logo must be a file of type:').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: true),
            'logo.max' => translate('The logo may not be greater than').getFileUploadMaxSize().' MB.',

            'banner.required' => translate('Shop banner is required.'),
            'banner.mimes' => translate('The banner must be a file of type:').getFileUploadFormats(skip: ['.svg'], asMessage: true),
            'banner.max' => translate('The banner may not be greater than').getFileUploadMaxSize().' MB.',

            'bottom_banner.mimes' => translate('The bottom banner must be a file of type:').getFileUploadFormats(skip: ['.svg'], asMessage: true),
            'bottom_banner.max' => translate('The bottom banner may not be greater than').getFileUploadMaxSize().' MB.',

            'tin_expire_date.after_or_equal' => translate('TIN expiry date must be today or later.'),
            'tin_certificate.mimes' => translate('The tin certificate must be a file of type:').getFileUploadFormats(skip: ['.png', '.gif', '.svg'], asMessage: true).getFileUploadFormats(type: 'file', skip: ['.txt'], asMessage: 'true'),
            'tin_certificate.max' => translate('The tin certificate may not be greater than').getFileUploadMaxSize().' MB.',
        ];
    }
}
