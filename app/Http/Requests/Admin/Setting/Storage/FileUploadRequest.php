<?php

namespace App\Http\Requests\Admin\Setting\Storage;

use App\Http\Requests\BaseRequest;

/**
 * @property-read string $images
 * @property-read string $file
 * @property-read string $path
 */
class FileUploadRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'images' => 'required_without:file',
            'images.*' => [
                'mimes:jpg,jpeg,png,gif,webp',
                'max:'.getFileUploadMaxSize(unit: 'kb'),
            ],
            'file' => 'required_without:images',
            'path' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'images.required_without' => translate('Either images or file required'),
            'images.*.mimes' => translate('Upload file must be jpeg jpg png gif webp format'),
            'images.*.max' => translate('Each uploaded image may not be greater than 2 MB'),
            'file.required_without' => translate('Either images or file required'),
            'path.required' => translate(':field is required', ['field' => translate('The path')]),
        ];
    }
}
