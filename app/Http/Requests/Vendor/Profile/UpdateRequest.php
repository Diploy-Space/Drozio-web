<?php

namespace App\Http\Requests\Vendor\Profile;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        $imageRule = 'mimes:jpg,jpeg,png,webp,gif,bmp,tif,tiff';
        $uniquePhone = Rule::unique('sellers', 'phone')->ignore($this->sellerId());

        if ($this->isApiRequest()) {
            return [
                'f_name' => ['sometimes', 'required', 'string', 'max:100'],
                'l_name' => ['sometimes', 'required', 'string', 'max:100'],
                'phone' => ['sometimes', 'required', 'string', 'min:4', 'max:20', $uniquePhone],
                'password' => ['nullable', 'string', 'min:8'],
                'bank_name' => ['sometimes', 'nullable', 'string', 'max:100'],
                'branch' => ['sometimes', 'nullable', 'string', 'max:100'],
                'account_no' => ['sometimes', 'nullable', 'string', 'max:100'],
                'holder_name' => ['sometimes', 'nullable', 'string', 'max:100'],
                'image' => ['nullable', $imageRule],
            ];
        }

        return [
            'f_name' => 'required',
            'l_name' => 'required',
            'image' => $imageRule,
            'phone' => ['required', 'max:20', 'min:4', $uniquePhone],
        ];
    }

    public function messages(): array
    {
        return [
            'f_name.required' => translate(':field is required', ['field' => translate('First Name')]).'!',
            'l_name.required' => translate(':field is required', ['field' => translate('Last Name')]).'!',
            'image.mimes' => translate('The image type must be').'.jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff,.webp',
            'phone.required' => translate(':field is required', ['field' => translate('Phone number')]).'!',
            'phone.unique' => translate('This phone number already belongs to another account'),
            'phone.max' => translate('Enter a valid phone number of up to 20 characters.'),
            'phone.min' => translate('Phone number with a minimum length requirement of 4 characters'),
            'password.min' => translate('The password must be at least 8 characters'),
            'bank_name.max' => translate('The bank name is too long'),
            'branch.max' => translate('The branch name is too long'),
            'account_no.max' => translate('The account number is too long'),
            'holder_name.max' => translate('The account holder name is too long'),
        ];
    }

    private function sellerId(): mixed
    {
        return $this->attributes->get('seller')?->id ?? $this->id;
    }
}
