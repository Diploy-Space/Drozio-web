<?php

namespace App\Http\Requests\Admin\Setting\System;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class EmailTemplateUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'logo' => getRulesStringForImageValidation(
                rules: ['sometimes', 'image'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'icon' => getRulesStringForImageValidation(
                rules: ['sometimes', 'image'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                $bodyEn = $this->body['en'] ?? null;
                $cleaned = trim(strip_tags($bodyEn));
                if (! array_key_exists('en', $this->title) || blank($this->title['en'])) {
                    $validator->errors()->add('title', translate('Title field is required').'!');
                }
                if ($cleaned === '') {
                    $validator->errors()->add('body', translate('Mail Body is required').'!');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'logo.mimes' => translate('Logo image type must be').' '.getFileUploadFormats(skip: '.svg', asBladeMessage: true),
            'logo.max' => translate('Logo image may not be greater than').getFileUploadMaxSize().'MB',
            'icon.mimes' => translate('Icon image type must be').' '.getFileUploadFormats(skip: '.svg', asBladeMessage: true),
            'icon.max' => translate('Icon image may not be greater than').getFileUploadMaxSize().'MB',
        ];
    }
}
