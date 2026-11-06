<?php

namespace App\Http\Requests\Admin\Vendor\Registration;

use App\Http\Requests\BaseRequest;

class BusinessProcessUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:51',
            'sub_title' => 'required|string|max:161',
        ];

        for ($i = 1; $i <= 3; $i++) {
            $rules["section_{$i}_title"] = 'required|string|max:51';
            $rules["section_{$i}_description"] = 'required|string|max:161';
            $rules["section_{$i}_image"] = 'nullable|image|'.getFileUploadFormats(skip: '.svg,.gif', asRule: 'true').'|max:'.getFileUploadMaxSize(unit: 'kb');
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'title.required' => translate(':field is required', ['field' => translate('Title')]),
            'title.string' => translate('Please enter a valid title'),
            'title.max' => translate('Title must not exceed 51 characters'),

            'sub_title.required' => translate(':field is required', ['field' => translate('Sub Title')]),
            'sub_title.string' => translate('Please enter a valid sub title'),
            'sub_title.max' => translate('Sub title must not exceed 161 characters'),
        ];

        for ($i = 1; $i <= 3; $i++) {
            $messages["section_{$i}_title.required"] = translate('Section :number title is required', ['number' => $i]);
            $messages["section_{$i}_title.string"] = translate('Section :number title must be a string', ['number' => $i]);
            $messages["section_{$i}_title.max"] = translate('Section :number title must not exceed 51 characters', ['number' => $i]);

            $messages["section_{$i}_description.required"] = translate('Section :number description is required', ['number' => $i]);
            $messages["section_{$i}_description.string"] = translate('Section :number description must be a string', ['number' => $i]);
            $messages["section_{$i}_description.max"] = translate('Section :number description must not exceed 161 characters', ['number' => $i]);

            $messages["section_{$i}_image.image"] = translate('Section :number image must be an image', ['number' => $i]);
            $messages["section_{$i}_image.mimes"] = translate('Section :number image format is invalid', ['number' => $i]);
            $messages["section_{$i}_image.max"] = translate('Section :number image must not exceed 2 MB', ['number' => $i]);
        }

        return $messages;
    }
}
