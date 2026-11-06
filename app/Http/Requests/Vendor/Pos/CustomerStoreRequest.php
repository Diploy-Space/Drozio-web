<?php

namespace App\Http\Requests\Vendor\Pos;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class CustomerStoreRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
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

    public function messages(): array
    {
        return [
            'f_name.required' => translate(':field is required', ['field' => translate('First Name')]),
            'l_name.required' => translate(':field is required', ['field' => translate('Last Name')]),
        ];
    }
}
