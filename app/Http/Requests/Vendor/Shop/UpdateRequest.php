<?php

namespace App\Http\Requests\Vendor\Shop;

use App\Enums\BusinessModule;
use App\Http\Requests\BaseRequest;

class UpdateRequest extends BaseRequest
{
    public const MODULE_TIN_PREFIXES = [BusinessModule::WHOLESALE, BusinessModule::AUCTION];

    protected function prepareForValidation(): void
    {
        if ($this->has('contact') && ! $this->has('company_phone')) {
            $this->merge(['company_phone' => $this['contact']]);
        }
    }

    public function rules(): array
    {
        $maxFileSize = getFileUploadMaxSize(unit: 'kb');
        $bannerRule = getRulesStringForImageValidation(
            rules: ['nullable'],
            skipMimes: ['.svg'],
            maxSize: $maxFileSize,
            isDisallowed: true
        );

        if ($this->isApiRequest()) {
            return [
                ...$this->moduleTinRules(maxFileSize: $maxFileSize),
                'wholesale_free_delivery_status' => ['sometimes', 'boolean'],
                'wholesale_free_delivery_over_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'wholesale_minimum_order_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'address' => ['sometimes', 'required', 'string', 'max:500'],
                'company_phone' => ['sometimes', 'required', 'string', 'min:4', 'max:20'],
                'tax_identification_number' => ['sometimes', 'nullable', 'string', 'max:'.OtherSetupUpdateRequest::TIN_MAX_LENGTH, 'regex:'.OtherSetupUpdateRequest::TIN_PATTERN],
                'tin_expire_date' => ['sometimes', 'nullable', 'date'],
                'minimum_order_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'free_delivery_status' => ['sometimes', 'boolean'],
                'free_delivery_over_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'stock_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'logo' => $bannerRule,
                'banner' => $bannerRule,
                'bottom_banner' => $bannerRule,
                'offer_banner' => $bannerRule,
                'tin_certificate' => ['nullable', 'mimes:pdf,doc,docx,jpg', 'max:'.$maxFileSize],
            ];
        }

        return [
            'name' => 'required:string|max:255',
            'company_phone' => 'required',
            'country_code' => 'required',
            'address' => 'required|string',
            'banner' => $bannerRule,
            'image' => $bannerRule,
            'bottomBanner' => $bannerRule,
            'offerBanner' => $bannerRule,
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->moduleTinMessages(),
            'wholesale_free_delivery_status.boolean' => translate('This setting can only be turned on or off'),
            'wholesale_free_delivery_over_amount.numeric' => translate('The wholesale free delivery over amount must be a number'),
            'wholesale_free_delivery_over_amount.min' => translate('The wholesale free delivery over amount can not be negative'),
            'wholesale_minimum_order_amount.numeric' => translate('The wholesale minimum order amount must be a number'),
            'wholesale_minimum_order_amount.min' => translate('The wholesale minimum order amount cannot be negative'),
            'name.required' => translate(':field is required', ['field' => translate('Shop name')]),
            'name.max' => translate('The shop name is too long'),
            'address.required' => translate(':field is required', ['field' => translate('Shop address')]),
            'address.max' => translate('The shop address is too long'),
            'company_phone.required' => translate(':field is required', ['field' => translate('Phone number')]),
            'company_phone.min' => translate('Phone number with a minimum length requirement of 4 characters'),
            'company_phone.max' => translate('Enter a valid phone number of up to 20 characters.'),
            'tax_identification_number.max' => translate('The TIN cannot be longer than :max characters', ['max' => OtherSetupUpdateRequest::TIN_MAX_LENGTH]),
            'tax_identification_number.regex' => translate('The TIN can only contain letters, numbers, hyphens and slashes'),
            'tin_expire_date.date' => translate('Choose a valid TIN expiry date'),
            'minimum_order_amount.numeric' => translate('The minimum order amount must be a number'),
            'minimum_order_amount.min' => translate('The minimum order amount cannot be negative'),
            'free_delivery_status.boolean' => translate('This setting can only be turned on or off'),
            'free_delivery_over_amount.numeric' => translate('The free delivery amount must be a number'),
            'free_delivery_over_amount.min' => translate('The free delivery amount cannot be negative'),
            'stock_limit.integer' => translate('The stock limit must be a whole number'),
            'stock_limit.min' => translate('The stock limit cannot be negative'),
            'tin_certificate.mimes' => translate('The TIN certificate must be a pdf, doc, docx or jpg file'),
            'banner.mimes' => translate('Banner image type').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'banner.max' => translate('Banner maximum size').getFileUploadMaxSize().'MB',
            'image.mimes' => translate('Image type jpg,').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'image.max' => translate('Image maximum size').getFileUploadMaxSize().'MB',
            'logo.mimes' => translate('Image type jpg,').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'bottomBanner.mimes' => translate('Bottom banner type').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'bottomBanner.max' => translate('Bottom banner maximum size').getFileUploadMaxSize().'MB',
            'offerBanner.mimes' => translate('Offer banner type').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'offerBanner.max' => translate('Offer banner maximum size').getFileUploadMaxSize().'MB',
        ];
    }

    /** @return array<string, array<int, string>> */
    private function moduleTinRules(int|float|string $maxFileSize): array
    {
        $rules = [];

        foreach (self::MODULE_TIN_PREFIXES as $module) {
            $rules[$module.'_tax_identification_number'] = ['sometimes', 'nullable', 'string', 'max:'.OtherSetupUpdateRequest::TIN_MAX_LENGTH, 'regex:'.OtherSetupUpdateRequest::TIN_PATTERN];
            $rules[$module.'_tin_expire_date'] = ['sometimes', 'nullable', 'date'];
            $rules[$module.'_tin_certificate'] = ['nullable', 'mimes:pdf,doc,docx,jpg', 'max:'.$maxFileSize];
        }

        return $rules;
    }

    /** @return array<string, string> */
    private function moduleTinMessages(): array
    {
        $messages = [];

        foreach (self::MODULE_TIN_PREFIXES as $module) {
            $messages[$module.'_tax_identification_number.max'] = translate('The TIN cannot be longer than :max characters', ['max' => OtherSetupUpdateRequest::TIN_MAX_LENGTH]);
            $messages[$module.'_tax_identification_number.regex'] = translate('The TIN can only contain letters, numbers, hyphens and slashes');
            $messages[$module.'_tin_expire_date.date'] = translate('Choose a valid TIN expiry date');
            $messages[$module.'_tin_certificate.mimes'] = translate('The TIN certificate must be a pdf, doc, docx or jpg file');
        }

        return $messages;
    }
}
