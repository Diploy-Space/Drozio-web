<?php

namespace App\Http\Requests\Admin\Vendor\Registration;

use App\Http\Requests\BaseRequest;

class HeaderUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:51',
            'sub_title' => 'required|string|max:161',
            'image' => 'nullable|image|'.getFileUploadFormats(skip: '.svg,.gif', asRule: 'true').'|max:'.getFileUploadMaxSize(unit: 'kb'),
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => translate(':field is required', ['field' => translate('Title')]),
            'title.max' => translate('Title may not be greater than 51 characters'),
            'sub_title.required' => translate(':field is required', ['field' => translate('Sub Title')]),
            'sub_title.max' => translate('Sub title may not be greater than 161 characters'),
            'image.image' => translate('Image type must be').' jpg, jpeg, png',
            'image.max' => translate('Image max size is 2 MB'),
        ];
    }
}
