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
        $isGst = $this->input('registration_type') === 'gst';

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

            // KYC fields
            'business_type' => 'required|string|max:50',
            'legal_name' => 'required|string|max:255',
            'trade_name' => 'required|string|max:255',
            'registration_type' => 'required|in:gst,non_gst',
            'gst_number' => [$isGst ? 'required' : 'nullable', 'string', 'max:50'],
            'pan_number' => 'required|string|max:20',
            'pan_holder_name' => 'required|string|max:255',
            'pan_dob' => 'nullable|string|max:20',
            'kyc_verification_mode' => 'nullable|in:online,manual',
            'online_consent' => 'nullable',
            'business_address_line1' => 'required|string|max:255',
            'business_address_line2' => 'nullable|string|max:255',
            'business_city' => 'required|string|max:100',
            'business_state' => 'required|string|max:100',
            'business_pincode' => 'required|string|max:20',
            'business_country' => 'required|string|max:100',
            'bank_account_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'bank_branch' => 'required|string|max:255',
            'bank_account_no' => 'required|string|max:50',
            'bank_ifsc' => 'required|string|max:30',

            // KYC documents (verifiable docs may be satisfied online; address/business always manual)
            'kyc_pan_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            'kyc_gst_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            'kyc_address_proof' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            'kyc_bank_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            'kyc_business_registration' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
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

            'business_type.required' => translate('Business type is required'),
            'legal_name.required' => translate('Legal name is required'),
            'trade_name.required' => translate('Trade name is required'),
            'registration_type.required' => translate('Registration type is required'),
            'gst_number.required' => translate('GST number is required for GST registered businesses'),
            'pan_number.required' => translate('PAN number is required'),
            'pan_holder_name.required' => translate('PAN holder name is required'),
            'business_address_line1.required' => translate('Business address is required'),
            'business_city.required' => translate('City is required'),
            'business_state.required' => translate('State is required'),
            'business_pincode.required' => translate('PIN code is required'),
            'business_country.required' => translate('Country is required'),
            'bank_account_name.required' => translate('Bank account name is required'),
            'bank_name.required' => translate('Bank name is required'),
            'bank_branch.required' => translate('Bank branch is required'),
            'bank_account_no.required' => translate('Bank account number is required'),
            'bank_ifsc.required' => translate('IFSC is required'),
            'kyc_pan_card.required' => translate('PAN card document is required'),
            'kyc_gst_certificate.required' => translate('GST certificate is required for GST registered businesses'),
            'kyc_address_proof.required' => translate('Address proof document is required'),
            'kyc_bank_proof.required' => translate('Bank proof document is required'),
            'kyc_business_registration.required' => translate('Business registration document is required'),
            'kyc_pan_card.mimes' => translate('PAN card must be a valid document file'),
            'kyc_gst_certificate.mimes' => translate('GST certificate must be a valid document file'),
            'kyc_address_proof.mimes' => translate('Address proof must be a valid document file'),
            'kyc_bank_proof.mimes' => translate('Bank proof must be a valid document file'),
            'kyc_business_registration.mimes' => translate('Business registration document must be a valid file'),
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
