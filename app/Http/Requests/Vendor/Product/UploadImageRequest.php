<?php

namespace App\Http\Requests\Vendor\Product;

use App\Http\Requests\BaseRequest;

class UploadImageRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'image' => getRulesStringForImageValidation(
                rules: ['required'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'type' => 'required|in:product,thumbnail,meta',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => translate('Image is required!'),
            'image.max' => translate('Image size must not exceed').' '.getFileUploadMaxSize().' MB!',
            'image.mimes' => translate('The image must be a file of type:').getFileUploadFormats(skip: ['.svg'], asMessage: true),
            'type.required' => translate('Type is required!'),
            'type.in' => translate('Invalid type provided!'),
        ];
    }
}
