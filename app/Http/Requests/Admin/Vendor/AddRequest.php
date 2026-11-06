<?php

namespace App\Http\Requests\Admin\Vendor;

use App\Http\Requests\BaseRequest;

class AddRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'f_name' => 'required',
            'l_name' => 'required',
            'phone' => 'required|unique:sellers|max:20|min:4',
            'email' => 'required|unique:sellers',
            'image' => getRulesStringForImageValidation(
                rules: ['required'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed : true
            ),
            'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!.*\s).{8,}$/|same:confirm_password',
            'shop_name' => 'required',
            'shop_address' => 'required',
            'logo' => getRulesStringForImageValidation(
                rules: ['required'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed : true
            ),
            'banner' => getRulesStringForImageValidation(
                rules: ['required'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed : true
            ),
            'bottom_banner' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed : true
            ),
            'tin' => 'nullable|array',
            'tin.*.tax_identification_number' => 'nullable|string',
            'tin.*.tin_expire_date' => 'nullable|date|after_or_equal:today',
            'tin.*.tin_certificate' => 'nullable|mimes:pdf,doc,docx,jpg,jpeg|max:'.getFileUploadMaxSize(type: 'file', unit: 'kb'),
        ];
    }

    public function messages(): array
    {
        return [
            'f_name.required' => translate('The first name field is required'),
            'l_name.required' => translate('The last name field is required'),
            'phone.required' => translate('The phone field is required'),
            'phone.unique' => translate('Phone number has already been taken'),
            'phone.max' => translate('Enter a valid phone number of up to 20 characters.'),
            'phone.min' => translate('Phone number with a minimum length requirement of 4 characters'),
            'email.required' => translate('The email field is required'),
            'email.unique' => translate('Email has already been taken'),
            'image.required' => translate('The image field is required'),
            'image.mimes' => translate('The image type must be').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: 'true'),
            'image.max' => translate('Vendor image may not be greater than').getFileUploadMaxSize().'MB',
            'password.required' => translate('The password field is required'),
            'password.min' => translate('The password must be at least 8 characters'),
            'password.same' => translate('The password and confirm password must match'),
            'password.regex' => translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.',
            'shop_name.required' => translate('The shop name field is required'),
            'shop_address.required' => translate('The shop address field is required'),
            'logo.required' => translate('The shop logo field is required'),
            'logo.mimes' => translate('The logo type must be').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: 'true'),
            'logo.max' => translate('Shop logo may not be greater than').getFileUploadMaxSize().'MB',
            'banner.required' => translate('The shop banner field is required'),
            'banner.mimes' => translate('The banner type must be').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: 'true'),
            'banner.max' => translate('Shop cover may not be greater than').getFileUploadMaxSize().'MB',
            'bottom_banner.mimes' => translate('The bottom banner type must be').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: 'true'),
            'bottom_banner.max' => translate('Bottom banner may not be greater than').getFileUploadMaxSize().'MB',
            'tin.array' => translate('Please enter a valid TIN'),
            'tin.*.tax_identification_number.string' => translate('Please enter a valid TIN'),
            'tin.*.tin_expire_date.date' => translate('The tin expiry date must be a valid date format'),
            'tin.*.tin_expire_date.after_or_equal' => translate('The tin expiry date must be a future date'),
            'tin.*.tin_certificate.mimes' => translate('TIN certificate must be a pdf, doc, docx, jpg or jpeg.'),
            'tin.*.tin_certificate.max' => translate('The tin certificate must not exceed').getFileUploadMaxSize(type: 'file').'MB',
        ];
    }
}
