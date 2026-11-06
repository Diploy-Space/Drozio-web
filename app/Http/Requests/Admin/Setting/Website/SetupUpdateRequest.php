<?php

namespace App\Http\Requests\Admin\Setting\Website;

use App\Http\Requests\BaseRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class SetupUpdateRequest extends BaseRequest
{
    private const IMAGE_FIELDS = [
        'company_web_logo',
        'company_footer_logo',
        'company_fav_icon',
        'loader_gif',
        'company_mobile_logo',
    ];

    protected $stopOnFirstFailure = true;

    /** @return array<string, UploadedFile> */
    public function websiteSetupImages(): array
    {
        $images = [];

        foreach (self::IMAGE_FIELDS as $field) {
            if ($this->hasFile($field)) {
                $images[$field] = $this->file($field);
            }
        }

        return $images;
    }

    public function rules(): array
    {
        return [
            'company_web_logo' => getRulesStringForImageValidation(
                rules: ['sometimes', 'image'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'company_footer_logo' => getRulesStringForImageValidation(
                rules: ['sometimes', 'image'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'company_fav_icon' => getRulesStringForImageValidation(
                rules: ['sometimes', 'image'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'loader_gif' => getRulesStringForImageValidation(
                rules: ['sometimes', 'image'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'company_mobile_logo' => getRulesStringForImageValidation(
                rules: ['sometimes', 'image'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'app_store_download_url' => [
                Rule::requiredIf($this->has('app_store_download_status')),
                'nullable',
                'string',
            ],
            'play_store_download_url' => [
                Rule::requiredIf($this->has('play_store_download_status')),
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'company_web_logo.mimes' => translate('The company web logo must be a file of type').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'company_web_logo.max' => translate('The company web logo may not be greater than').getFileUploadMaxSize().'MB',
            'company_web_logo.dimensions' => translate('The company web logo must be at least 325x100 pixels'),

            'company_footer_logo.mimes' => translate('The company footer logo must be a file of type').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'company_footer_logo.max' => translate('The company footer logo may not be greater than').getFileUploadMaxSize().'MB',
            'company_footer_logo.dimensions' => translate('The company footer logo must be at least 325x100 pixels'),

            'company_fav_icon.mimes' => translate('The company fav icon must be a file of type').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'company_fav_icon.max' => translate('The company fav icon may not be greater than').getFileUploadMaxSize().'MB',

            'loader_gif.mimes' => translate('The loader gif must be a file of type').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'loader_gif.max' => translate('The loader gif may not be greater than').getFileUploadMaxSize().'MB',

            'company_mobile_logo.mimes' => translate('The company mobile logo must be a file of type').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'company_mobile_logo.max' => translate('The company mobile logo may not be greater than').getFileUploadMaxSize().'MB',
            'company_mobile_logo.dimensions' => translate('The company mobile logo must be at least 100x60 pixels'),

            'app_store_download_url.required' => translate('The app store download url field is required'),
            'app_store_download_url.string' => translate('Please enter a valid app store download link'),

            'play_store_download_url.required' => translate('The play store download url field is required'),
            'play_store_download_url.string' => translate('Please enter a valid Play Store download link'),
        ];
    }
}
