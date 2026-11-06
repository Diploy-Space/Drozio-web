<?php

namespace App\Http\Requests\Admin\Notification;

use App\Http\Requests\BaseRequest;

class UpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'description' => 'required|string|max:191',
            'image' => getRulesStringForImageValidation(
                rules: ['nullable', 'image'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: false
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => translate(':field is required', ['field' => translate('Title')]),
            'title.max' => translate('The title may not be longer than 100 characters'),
            'description.required' => translate(':field is required', ['field' => translate('Description')]),
            'description.max' => translate('The description may not be longer than 191 characters'),
            'image.mimes' => translate('Only allowed').getFileUploadFormats(skip: '.svg,.gif', asMessage: true),
            'image.max' => translate('The image may not be greater than').getFileUploadMaxSize().'MB',
        ];
    }
}
