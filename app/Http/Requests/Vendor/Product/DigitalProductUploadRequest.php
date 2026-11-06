<?php

namespace App\Http\Requests\Vendor\Product;

use App\Http\Requests\BaseRequest;
use App\Rules\DisallowedExtension;

class DigitalProductUploadRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'digital_file_ready' => [
                'required',
                'file',
                new DisallowedExtension,
                'max:'.getFileUploadMaxSize(unit: 'kb'),
                'mimes:jpg,jpeg,png,gif,zip,pdf',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'digital_file_ready.required' => translate('The digital file is required'),
            'digital_file_ready.file' => translate('The digital file must be an uploaded file'),
            'digital_file_ready.max' => translate('The digital file size must not exceed').' '.getFileUploadMaxSize().' MB!',
            'digital_file_ready.mimes' => translate('The digital file format is not supported. Allowed formats: jpg, jpeg, png, gif, zip, pdf.'),
        ];
    }
}
