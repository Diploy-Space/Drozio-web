<?php

namespace App\Http\Requests\Vendor\Auth;

use App\Http\Requests\BaseRequest;
use App\Traits\CalculatorTrait;
use App\Traits\RecaptchaTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class RegistrationRequest extends BaseRequest
{
    use CalculatorTrait;
    use RecaptchaTrait;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'f_name' => 'required',
            'l_name' => 'required',
            'phone' => 'required|unique:sellers|max:20',
            'email' => 'required|unique:sellers',
            'image' => 'required|mimes:jpg,jpeg,png,webp,gif,bmp,tif,tiff',
            'password' => 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!.*\s).{8,}$/|same:confirm_password',
            'shop_name' => 'required',
            'shop_address' => 'required',
            'logo' => 'required|mimes: jpg,jpeg,png,webp,gif,bmp,tif,tiff',
            'banner' => 'required|mimes: jpg,jpeg,png,webp,gif,bmp,tif,tiff',
            'bottom_banner' => 'mimes: jpg,jpeg,png,webp,gif,bmp,tif,tiff',
            'tax_identification_number' => 'nullable|string',
            'tin_expire_date' => 'nullable|date|after_or_equal:today',
            'tin_certificate' => 'nullable|mimes:pdf,doc,docx,jpg|max:5120',
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
            'email.required' => translate('The email field is required'),
            'email.unique' => translate('Email has already been taken'),
            'image.required' => translate('The image field is required'),
            'image.mimes' => translate('The image type must be').'.jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff,.webp',
            'password.required' => translate('The password field is required'),
            'password.same' => translate('The password and confirm password must match'),
            'password.regex' => translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.',
            'shop_name.required' => translate('The shop name field is required'),
            'shop_address.required' => translate('The shop address field is required'),
            'logo.mimes' => translate('The logo type must be').'.jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff,.webp',
            'banner.mimes' => translate('The banner type must be').'.jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff,.webp',
            'bottom_banner.mimes' => translate('The bottom banner type must be').'.jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff,.webp',
            'tax_identification_number.string' => translate('Please enter a valid TIN'),
            'tin_expire_date.date' => translate('The tin expiry date must be a valid date format'),
            'tin_expire_date.after_or_equal' => translate('The tin expiry date must be a future date'),
            'tin_certificate.mimes' => translate('TIN certificate must be a pdf, doc, docx or jpg.'),
            'tin_certificate.max' => translate('The tin certificate must not exceed 5MB'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                $numericPhoneValue = preg_replace('/[^0-9]/', '', $this['phone']);
                $numericLength = strlen($numericPhoneValue);
                if ($numericLength < 4) {
                    $validator->errors()->add(
                        'phone.min', translate('The phone number must be at least 4 characters')
                    );
                }

                if ($numericLength > 20) {
                    $validator->errors()->add(
                        'phone.max', translate('The phone number may not be greater than 20 characters')
                    );
                }
            },
        ];
    }
}
