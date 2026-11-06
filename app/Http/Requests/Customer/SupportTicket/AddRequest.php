<?php

namespace App\Http\Requests\Customer\SupportTicket;

use App\Http\Requests\BaseRequest;
use App\Rules\DisallowedExtension;
use App\Services\New\SupportTicket\SupportTicketService;
use Illuminate\Validation\Rule;

class AddRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('priority'))) {
            $this->merge(['priority' => strtolower(trim($this->input('priority')))]);
        }
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'description' => 'required|string|max:255',
            'priority' => ['nullable', Rule::in(SupportTicketService::priorities())],
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
            'subject.required' => translate(':field is required', ['field' => translate('Subject')]),
            'subject.max' => translate('Subject must not be longer than 255 characters'),
            'type.required' => translate(':field is required', ['field' => translate('Type')]),
            'description.required' => translate(':field is required', ['field' => translate('Description')]),
            'description.max' => translate('Description must not be longer than 255 characters'),
            'priority.in' => translate('Choose a valid priority'),
            'image.*.mimes' => translate('Invalid image format image type must be :').getFileUploadFormats(skip: ['.svg'], asMessage: true),
            'image.*.max' => translate('Image size exceeds maximum size').' '.getFileUploadMaxSize().'MB',
            'file.*.file' => translate('The attachment must be a valid file'),
            'file.*.mimes' => translate('File format must be').' '.getFileUploadFormats(type: 'file', asBladeMessage: true),
            'file.*.max' => translate('File size exceeds maximum size').' '.getFileUploadMaxSize(type: 'file').'MB',
        ];
    }
}
