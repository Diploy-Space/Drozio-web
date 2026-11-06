<?php

namespace App\Http\Requests\Admin\Promotion\FlashDeal;

use App\Http\Requests\BaseRequest;

class UpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.*' => ['nullable', 'string', 'max:100'],
            'lang' => ['required', 'array'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'image' => getRulesStringForImageValidation(
                rules: ['sometimes', 'nullable', 'image'],
                skipMimes: ['.svg'],
                isDisallowed: true,
            ),
            'meta_title' => ['nullable', 'string', 'max:50'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_image' => getRulesStringForImageValidation(
                rules: ['nullable', 'image'],
                skipMimes: ['.svg', '.gif'],
                isDisallowed: true,
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => translate('Title field is required'),
            'title.*.max' => translate('Title must not be longer than 100 characters'),
            'start_date.required' => translate('Start date field is required'),
            'start_date.date' => translate('Start date must be a valid date'),
            'end_date.required' => translate('End date field is required'),
            'end_date.date' => translate('End date must be a valid date'),
            'end_date.after_or_equal' => translate('End date must be the same as or after the start date'),
            'image.image' => translate('The banner must be an image file'),
            'image.mimes' => translate('The banner must be one of these formats: :formats', ['formats' => getFileUploadFormats(skip: '.svg', asMessage: 'true')]),
            'image.max' => translate('The banner must not be larger than :size MB', ['size' => getFileUploadMaxSize()]),
            'meta_title.max' => translate('Meta title must not be longer than 50 characters'),
            'meta_description.max' => translate('Meta description must not be longer than 160 characters'),
            'meta_image.image' => translate('The meta image must be an image file'),
            'meta_image.mimes' => translate('The meta image must be one of these formats: :formats', ['formats' => getFileUploadFormats(skip: '.svg,.gif', asMessage: 'true')]),
            'meta_image.max' => translate('The meta image must not be larger than :size MB', ['size' => getFileUploadMaxSize()]),
        ];
    }
}
