<?php

namespace App\Http\Requests\Vendor\Shop;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class OtherSetupUpdateRequest extends BaseRequest
{
    public const TIN_MAX_LENGTH = 50;

    public const TIN_PATTERN = '/^[A-Za-z0-9\-\/]+$/';

    private const FREE_DELIVERY_ON = 'on';

    /** @return array<string, ValidationRule|array|string> */
    public function rules(): array
    {
        return [
            'tin' => 'nullable|array',
            'tin.*.tax_identification_number' => ['nullable', 'string', 'max:'.self::TIN_MAX_LENGTH, 'regex:'.self::TIN_PATTERN],
            'tin.*.tin_expire_date' => 'nullable|date',
            'tin.*.remove_certificate' => 'nullable|boolean',
            'tin.*.tin_certificate' => 'nullable|mimes:pdf,doc,docx,jpg,jpeg|max:'.getFileUploadMaxSize(type: 'file', unit: 'kb'),
            'free_delivery_status' => 'nullable',
            'free_delivery_over_amount' => 'required_if:free_delivery_status,'.self::FREE_DELIVERY_ON.'|nullable|numeric|gt:0',
            'wholesale_free_delivery_over_amount' => 'required_if:wholesale_free_delivery_status,'.self::FREE_DELIVERY_ON.'|nullable|numeric|gt:0',
            'wholesale_free_delivery_status' => 'nullable',
            'wholesale_minimum_order_amount' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'tin.array' => translate('Please enter a valid TIN'),
            'tin.*.tax_identification_number.string' => translate('Please enter a valid TIN'),
            'tin.*.tax_identification_number.max' => translate('The TIN cannot be longer than :max characters', ['max' => self::TIN_MAX_LENGTH]),
            'tin.*.tax_identification_number.regex' => translate('The TIN can only contain letters, numbers, hyphens and slashes'),
            'tin.*.tin_expire_date.date' => translate('The tin expiry date must be a valid date format'),
            'tin.*.tin_certificate.mimes' => translate('TIN certificate must be a pdf, doc, docx, jpg or jpeg.'),
            'tin.*.tin_certificate.max' => translate('The tin certificate must not exceed').' '.getFileUploadMaxSize(type: 'file').'MB',
            'tin.*.remove_certificate.boolean' => translate('Please choose whether to remove the TIN certificate'),
            'free_delivery_over_amount.required_if' => translate('Please enter the free delivery over amount'),
            'free_delivery_over_amount.numeric' => translate('The free delivery amount must be a number'),
            'free_delivery_over_amount.gt' => translate('The free delivery amount must be greater than zero'),
            'wholesale_free_delivery_over_amount.required_if' => translate('Please enter the wholesale free delivery over amount'),
            'wholesale_free_delivery_over_amount.numeric' => translate('The wholesale free delivery over amount must be a number'),
            'wholesale_free_delivery_over_amount.gt' => translate('The wholesale free delivery amount must be greater than zero'),
            'wholesale_minimum_order_amount.numeric' => translate('The wholesale minimum order amount must be a number'),
            'wholesale_minimum_order_amount.min' => translate('The wholesale minimum order amount cannot be negative'),
        ];
    }
}
