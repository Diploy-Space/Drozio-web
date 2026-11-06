<?php

namespace App\Http\Requests\Vendor\Customer;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class AddRequest extends BaseRequest
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

        return [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'unique:users|min:4|max:20',
        ];
    }

    public function messages(): array
    {
        if ($this->isApiRequest()) {
            return $this->apiMessages();
        }

        return [
            'f_name.required' => translate(':field is required', ['field' => translate('First Name')]),
            'l_name.required' => translate(':field is required', ['field' => translate('Last Name')]),
            'email.required' => translate(':field is required', ['field' => translate('Email')]),
            'email.email' => translate('Email must be valid'),
            'email.unique' => translate('Email has already been taken'),
            'phone.required' => translate(':field is required', ['field' => translate('Phone')]),
            'phone.max' => translate('Enter a valid phone number of up to 20 characters.'),
            'phone.min' => translate('Phone number with a minimum length requirement of 4 characters'),
        ];
    }

    /** Key order is the contract: the frozen v3 body lists errors in rule order. */
    private function apiRules(): array
    {
        return [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'unique:users',
            'country' => 'required',
            'city' => 'required',
            'zip_code' => 'required',
            'address' => 'required',
        ];
    }

    private function apiMessages(): array
    {
        return [
            'f_name.required' => 'First name is required!',
            'l_name.required' => 'Last name is required!',
        ];
    }
}
