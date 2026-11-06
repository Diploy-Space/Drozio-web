<?php

namespace App\Http\Requests\Vendor\Seller;

use App\Http\Requests\BaseRequest;

class UpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'image' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true),
        ];
    }

    public function messages(): array
    {
       return [
           'image.required' => translate('Profile image is required.'),
           'image.mimes' => translate('The profile image must be a file of type:') . getFileUploadFormats(skip: ['.svg', '.gif'], asMessage: true),
           'image.max' => translate('The profile image may not be greater than') . getFileUploadMaxSize() . ' MB.',

       ];
    }
}
