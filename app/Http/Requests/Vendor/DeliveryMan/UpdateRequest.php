<?php

namespace App\Http\Requests\Vendor\DeliveryMan;

use App\Http\Requests\BaseRequest;
use App\Models\DeliveryMan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return $this->apiRules();
        }

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
        ];
        if ($this['password']) {
            $rules['password'] = 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!.*\s).{8,}$/|same:confirm_password';
        }

        return $rules;
    }

    /**
     * @return array[f_name.required: mixed, l_name.required: mixed, phone.required: mixed, email.required: mixed, email.unique: mixed, country_code.required: mixed, password.same: mixed, password.min: mixed]
     */
    public function messages(): array
    {
        if ($this->isApiRequest()) {
            return $this->apiMessages();
        }

        return [
            'f_name.required' => translate('The first name field is required'),
            'l_name.required' => translate('The last name field is required'),
            'phone.required' => translate('The phone field is required'),
            'phone.max' => translate('Enter a valid phone number of up to 20 characters.'),
            'phone.min' => translate('Phone number with a minimum length requirement of 4 characters'),
            'email.required' => translate('The email field is required'),
            'email.unique' => translate('Email has already been taken'),
            'country_code.required' => translate('The country code field is required'),
            'password.regex' => translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.',
        ];
    }

    /**
     * The frozen source built three validators but reassigned `$validator` each time and only
     * checked the last one, so exactly one rule set ever ran. Reproduced here in that same
     * precedence: a changed email wins, then a supplied password, otherwise the base set.
     *
     * @return array<string, ValidationRule|array|string>
     */
    private function apiRules(): array
    {
        if ($this->emailDiffersFromStoredDeliveryMan()) {
            return [
                'email' => 'required|unique:delivery_men',
            ];
        }

        if ($this->input('password')) {
            return [
                'password' => 'required|min:8|same:confirm_password',
            ];
        }

        return [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|email|unique:delivery_men,email,'.$this->route('id'),
            'phone' => 'required',
            'country_code' => 'required',
        ];
    }

    private function apiMessages(): array
    {
        return [
            'f_name.required' => 'First name is required!',
            'l_name.required' => 'Last name is required!',
        ];
    }

    private function emailDiffersFromStoredDeliveryMan(): bool
    {
        $deliveryMan = DeliveryMan::where([
            'id' => $this->route('id'),
            'seller_id' => $this->input('seller')?->id,
        ])->first();

        return isset($deliveryMan) && $this->input('email') != $deliveryMan['email'];
    }
}
