<?php

namespace App\Http\Requests\Deliveryman\Profile;

use App\Http\Requests\BaseRequest;

class UpdateInfoRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'f_name' => 'required',
            'l_name' => 'required',
            'image' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'password' => 'nullable|same:confirm_password|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'f_name.required' => 'The first name field is required.',
            'l_name.required' => 'The last name field is required.',
            'image.mimes' => translate('The image type must be').getFileUploadFormats(skip: '.svg,.gif,.webp', asMessage: 'true'),
        ];
    }
}
