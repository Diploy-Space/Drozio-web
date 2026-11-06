<?php

namespace App\Http\Requests\Admin\DeliveryMan;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Carbon;

/**
 * Class DeliveryManAddRequest
 *
 * @property int $id
 * @property string $f_name
 * @property string $l_name
 * @property string $phone
 * @property string $email
 * @property string $country_code
 * @property string $password
 * @property string $confirm_password
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = false;

    public function rules(): array
    {
        return [
            'f_name' => 'required',
            'l_name' => 'required',
            'phone' => 'required|max:20|min:4|unique:delivery_men',
            'email' => 'required|unique:delivery_men',
            'image' => getRulesStringForImageValidation(
                rules: ['required', 'image'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!.*\s).{8,}$/|same:confirm_password',
        ];
    }

    public function messages(): array
    {
        return [
            'f_name.required' => translate('The first name field is required'),
            'l_name.required' => translate('The last name field is required'),
            'phone.required' => translate('The phone field is required'),
            'phone.max' => translate('Enter a valid phone number of up to 20 characters.'),
            'phone.min' => translate('Phone number with a minimum length requirement of 4 characters'),
            'email.required' => translate('The email field is required'),
            'email.unique' => translate('Email has already been taken'),
            'phone.unique' => translate('Phone number has already been taken'),
            'image.required' => translate('The delivery man image field is required'),
            'image.mimes' => translate('The image type must be').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: 'true'),
            'image.max' => translate('The image may not be greater than :size MB', ['size' => getFileUploadMaxSize()]),
            'password.required' => translate('The password field is required'),
            'password.same' => translate('The password and confirm password must match'),
            'password.regex' => translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.',
            'password.min' => translate('The password must be at least :min characters', ['min' => 8]),
        ];
    }
}
