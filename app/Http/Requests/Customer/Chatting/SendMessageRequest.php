<?php

namespace App\Http\Requests\Customer\Chatting;

use App\Enums\GlobalConstant;
use App\Http\Requests\BaseRequest;
use App\Rules\DisallowedExtension;
use Illuminate\Contracts\Validation\ValidationRule;

class SendMessageRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->stopOnFirstFailure = ! $this->isApiRequest();
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return $this->isApiRequest() ? $this->apiRules() : $this->webRules();
    }

    public function messages(): array
    {
        return $this->isApiRequest() ? $this->apiMessages() : $this->webMessages();
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    private function apiRules(): array
    {
        $maximumUploadSize = getFileUploadMaxSize(unit: 'kb');

        return [
            'message' => 'required_without_all:file,media',
            'media.*' => [
                'file',
                new DisallowedExtension,
                'max:'.$maximumUploadSize,
                'mimes:'.$this->mediaExtensions(),
            ],
            'file.*' => [
                'file',
                new DisallowedExtension,
                'max:'.$maximumUploadSize,
                'mimes:'.$this->documentExtensions(),
            ],
        ];
    }

    private function apiMessages(): array
    {
        return [
            'message.required_without_all' => translate('Type something').'!',
            'media.*.mimes' => $this->unsupportedMediaMessage(),
            'media.*.max' => translate('Media maximum size').' '.getFileUploadMaxSize().'MB',
            'file.*.mimes' => $this->unsupportedDocumentMessage(),
            'file.*.max' => translate('File maximum size').getFileUploadMaxSize().'MB',
        ];
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    private function webRules(): array
    {
        $maximumUploadSize = checkServerUploadMaxFileSizeInMB();

        return [
            'message' => 'required_without_all:file,media',
            'media.*' => 'max:'.$maximumUploadSize.'|mimes:'.$this->mediaExtensions(),
            'file.*' => 'file|max:2048|mimes:'.$this->documentExtensions(),
        ];
    }

    private function webMessages(): array
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
