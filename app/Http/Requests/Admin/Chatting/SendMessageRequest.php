<?php

namespace App\Http\Requests\Admin\Chatting;

use App\Enums\GlobalConstant;
use App\Http\Requests\BaseRequest;

/**
 * @property string $image
 */
class SendMessageRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        $maximumUploadSize = checkServerUploadMaxFileSizeInMB();

        return [
            'message' => 'required_without_all:file,media',
            'media.*' => 'max:'.$maximumUploadSize.'|mimes:'.$this->mediaExtensions(),
            'file.*' => 'file|max:'.getFileUploadMaxSize(unit: 'kb').'|mimes:'.$this->documentExtensions(),
        ];
    }

    public function messages(): array
    {
        $maximumUploadSize = checkServerUploadMaxFileSizeInMB();

        return [
            'required_without_all' => translate('Type something').'!',
            'media.mimes' => $this->unsupportedMediaMessage(),
            'media.max' => translate('Media maximum size').' '.($maximumUploadSize / 1024).' MB',
            'file.mimes' => $this->unsupportedDocumentMessage(),
            'file.max' => translate('File maximum size').MAXIMUM_MEDIA_UPLOAD_SIZE,
        ];
    }

    private function mediaExtensions(): string
    {
        return str_replace('.', '', implode(',', GlobalConstant::MEDIA_EXTENSION));
    }

    private function documentExtensions(): string
    {
        return str_replace('.', '', implode(',', GlobalConstant::DOCUMENT_EXTENSION));
    }

    private function unsupportedMediaMessage(): string
    {
        return translate('The media format is not supported').' '.translate('Supported format are').' '.$this->mediaExtensions();
    }

    private function unsupportedDocumentMessage(): string
    {
        return translate('The file format is not supported').' '.translate('Supported format are').' '.$this->documentExtensions();
    }
}
