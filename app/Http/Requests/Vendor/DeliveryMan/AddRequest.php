<?php

namespace App\Http\Requests\Vendor\DeliveryMan;

use App\Http\Requests\BaseRequest;

class AddRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'f_name' => 'required',
            'l_name' => 'required',
            'phone' => 'required|max:20|min:4|unique:delivery_men',
            'email' => 'required|unique:delivery_men,email',
            'country_code' => 'required',
            'identity_image*' => 'required|mimes:jpg,jpeg,png,webp,gif,bmp,tif,tiff',
            'image' => 'required|mimes:jpg,jpeg,png,webp,gif,bmp,tif,tiff',
            'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!.*\s).{8,}$/|same:confirm_password',
        ];
    }

    /**
     * @return array
     *               Get the validation error message
     */
    public function messages(): array
    {
        return [
            'f_name.required' => translate('The first name field is required'),
            'l_name.required' => translate('The last name field is required'),
            'phone.required' => translate('The phone field is required'),
            'phone.max' => translate('Enter a valid phone number of up to 20 characters.'),
            'phone.min' => translate('Phone number with a minimum length requirement of 4 characters'),
            'phone.unique' => translate('Phone number has already been taken'),
            'email.required' => translate('The email field is required'),
            'email.unique' => translate('Email has already been taken'),
            'image.required' => translate('The image field is required'),
            'image.mimes' => translate('The image type must be').'.jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff,.webp',
            'identity_image.required' => translate('The identity image is required'),
            'identity_image.mimes' => translate('The identity image type must be').'.jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff,.webp',
            'password.same' => translate('The password and confirm password must match'),
            'password.regex' => translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.',
            'password.min' => translate('The password must be at least :min characters', ['min' => 8]),
        ];
    }
}
