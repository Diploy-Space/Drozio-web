<?php

namespace App\Http\Requests\Customer\SupportTicket;

use App\Http\Requests\BaseRequest;
use App\Rules\DisallowedExtension;

class ReplyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'message' => 'required|string|max:191',
            'image.*' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'file.*' => [
                'nullable',
                'file',
                getFileUploadFormats(type: 'file', asRule: true),
                'max:'.getFileUploadMaxSize(type: 'file', unit: 'kb'),
                new DisallowedExtension,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => translate(':field is required', ['field' => translate('Message')]),
            'message.max' => translate('Message must not be longer than 191 characters'),
            'image.*.mimes' => translate('Invalid image format image type must be :').getFileUploadFormats(skip: ['.svg'], asMessage: true),
            'image.*.max' => translate('Image size exceeds maximum size').' '.getFileUploadMaxSize().'MB',
            'file.*.file' => translate('The attachment must be a valid file'),
            'file.*.mimes' => translate('File format must be').' '.getFileUploadFormats(type: 'file', asBladeMessage: true),
            'file.*.max' => translate('File size exceeds maximum size').' '.getFileUploadMaxSize(type: 'file').'MB',
        ];
    }
}
