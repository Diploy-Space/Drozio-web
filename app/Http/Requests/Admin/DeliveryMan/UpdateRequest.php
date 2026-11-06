<?php

namespace App\Http\Requests\Admin\DeliveryMan;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

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
class UpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = false;

    public function rules(): array
    {
        $rules = [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('delivery_men', 'email')->ignore($this->route('id')),
            ],
            'phone' => 'required|max:20|min:4',
            'country_code' => 'required',
            'image' => getRulesStringForImageValidation(
                rules: ['nullable', 'image'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
        ];
        if ($this['password']) {
            $rules['password'] = 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!.*\s).{8,}$/|same:confirm_password';
        }

        return $rules;
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
            'image.mimes' => translate('The image type must be').getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: 'true'),
            'image.max' => translate('The image may not be greater than :size MB', ['size' => getFileUploadMaxSize()]),
            'country_code.required' => translate('The country code field is required'),
            'password.regex' => translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.',
        ];
    }
}
