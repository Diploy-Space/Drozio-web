<?php

namespace App\Http\Requests\Admin\Setting\Website;

use App\Http\Requests\BaseRequest;

class RobotsMetaUpdateRequest extends BaseRequest
{
    public function rules(): array
    {

        return [
            'meta_title' => 'required|string|max:60',
            'meta_description' => 'required|string|max:160',
            'meta_image' => 'nullable|image|'.getFileUploadFormats(skip: '.svg,.webp', asRule: 'true').'|max:'.getFileUploadMaxSize(unit: 'kb'),
        ];
    }

    public function messages(): array
    {
        return [
            'meta_title.required' => translate('Meta title required'),
            'meta_title.max' => translate('Meta title max 60 characters'),
            'meta_description.required' => translate('Meta description required'),
            'meta_description.max' => translate('Meta description max 160 characters'),
            'meta_image.mimes' => translate('Meta image must be of type').translate(getFileUploadFormats(skip: '.svg,.webp', asMessage: 'true')),
            'meta_image.max' => translate('Meta image may not be greater than').getFileUploadMaxSize().'MB',
        ];
    }
}
