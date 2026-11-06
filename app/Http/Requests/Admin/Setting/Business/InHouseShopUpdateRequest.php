<?php

namespace App\Http\Requests\Admin\Setting\Business;

use App\Http\Requests\BaseRequest;

class InHouseShopUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'contact' => 'required',
            'country_code' => 'required',
            'address' => 'required|string',

            'shop_banner' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),

            'bottom_banner' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),

            'offer_banner' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'shop_banner.mimes' => translate('Banner image type').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'shop_banner.max' => translate('Banner maximum size').getFileUploadMaxSize().'MB',
            'image.mimes' => translate('Image type jpg,').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'image.max' => translate('Image maximum size').getFileUploadMaxSize().'MB',
            'bottom_banner.mimes' => translate('Bottom banner type').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'bottom_banner.max' => translate('Bottom banner maximum size').getFileUploadMaxSize().'MB',
            'offer_banner.mimes' => translate('Offer banner type').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'offer_banner.max' => translate('Offer banner maximum size').getFileUploadMaxSize().'MB',
        ];
    }
}
