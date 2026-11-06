<?php

namespace App\Http\Requests\Deliveryman\Chatting;

use App\Enums\GlobalConstant;
use App\Http\Requests\BaseRequest;
use App\Rules\DisallowedExtension;

class SendMessageRequest extends BaseRequest
{
    protected bool $useLegacyRequestErrorFormat = true;

    public function rules(): array
    {
        return [
            'id' => 'required',
            'message' => 'required_without_all:file,media',
            'media.*' => [
                'file',
                new DisallowedExtension,
                'max:'.getFileUploadMaxSize(unit: 'kb'),
                'mimes:'.str_replace('.', '', implode(',', GlobalConstant::MEDIA_EXTENSION)),
            ],
            'file.*' => [
                'file',
                new DisallowedExtension,
                'max:'.getFileUploadMaxSize(unit: 'kb'),
                'mimes:'.str_replace('.', '', implode(',', GlobalConstant::DOCUMENT_EXTENSION)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'required_without_all' => translate('Type something').'!',
            'media.*.mimes' => translate('The media format is not supported').' '.
                translate('Supported format are').' '.
                str_replace('.', '', implode(',', GlobalConstant::MEDIA_EXTENSION)),

            'media.*.max' => translate('Media maximum size').' '.
                (getFileUploadMaxSize() / 1024).' MB',

            'file.*.mimes' => translate('The file format is not supported').' '.
                translate('Supported format are').' '.
                str_replace('.', '', implode(',', GlobalConstant::DOCUMENT_EXTENSION)),

            'file.*.max' => translate('File maximum size').
                (getFileUploadMaxSize() / 1024).' MB',
        ];
    }
}
