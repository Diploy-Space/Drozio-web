<?php

namespace App\Http\Requests\Admin\Vendor\Registration;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class DownloadAppSectionUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];
        if ($this->section_type == 'title_section') {
            $rules += [
                'title' => 'required|string|max:50',
                'sub_title' => 'required|string|max:160',
                'image' => getRulesStringForImageValidation(
                    rules: ['sometimes', 'image'],
                    skipMimes: ['.svg'],
                    maxSize: getFileUploadMaxSize(unit: 'kb'),
                    isDisallowed: true
                ),
            ];
        } elseif ($this->section_type == 'link_section') {
            $rules += [
                'download_google_app' => 'required|string',
                'download_apple_app' => 'required|string',
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => translate('Download app title is required'),
            'title.string' => translate('Please enter a valid download app title'),
            'title.max' => translate('Download app title max character is 50'),

            'sub_title.required' => translate('Download app subtitle is required'),
            'sub_title.string' => translate('Please enter a valid download app subtitle'),
            'sub_title.max' => translate('Download app subtitle max character is 160'),

            'image.mimes' => translate('Image type must be').' '.getFileUploadFormats(skip: '.svg'),
            'image.max' => translate('Image max size is').' '.getFileUploadMaxSize().' MB',

            'download_google_app.required' => translate('Google play link is required'),
            'download_google_app.string' => translate('Please enter a valid Google Play link'),
            'download_apple_app.required' => translate('Apple store link is required'),
            'download_apple_app.string' => translate('Please enter a valid Apple store link'),
        ];
    }
}
