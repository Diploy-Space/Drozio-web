<?php

namespace App\Http\Requests\Customer\HelpAndSupport;

use App\Http\Requests\BaseRequest;
use App\Rules\DisallowedExtension;
use Illuminate\Contracts\Validation\ValidationRule;

class SupportTicketCommentRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'media.*' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true,
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
            'media.*.mimes' => translate('Image format must be').' '.getFileUploadFormats(skip: ['.svg'], asBladeMessage: true),
            'media.*.max' => translate('Image size exceeds maximum size').' '.getFileUploadMaxSize().'MB',
            'file.*.file' => translate('The attachment must be a valid file'),
            'file.*.mimes' => translate('File format must be').' '.getFileUploadFormats(type: 'file', asBladeMessage: true),
            'file.*.max' => translate('File size exceeds maximum size').' '.getFileUploadMaxSize(type: 'file').'MB',
        ];
    }
}
