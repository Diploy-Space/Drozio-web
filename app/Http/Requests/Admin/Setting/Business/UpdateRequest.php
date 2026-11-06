<?php

namespace App\Http\Requests\Admin\Setting\Business;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'company_name' => 'required|string',
            'country_code' => 'required|string',
            'shop_address' => 'required|string',
            'company_email' => 'required|email',
            'pagination_limit' => 'required|decimal:0,2|min:5|max:100',
            'decimal_point_settings' => 'required|integer|min:0',
            'company_copyright_text' => 'required|string',
            'cookie_text' => [
                Rule::requiredIf($this->has('cookie_status')),
                'nullable',
                'string',
            ],
            'cash_on_delivery' => 'required_without_all:digital_payment,offline_payment|boolean',
            'digital_payment' => 'required_without_all:cash_on_delivery,offline_payment|boolean',
            'offline_payment' => 'required_without_all:cash_on_delivery,digital_payment|boolean',
            'timezone' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => translate(':field is required', ['field' => translate('Company Name')]),
            'company_name.string' => translate('Please enter a valid company name'),
            'country_code.required' => translate(':field is required', ['field' => translate('Country code')]),
            'country_code.string' => translate('Please enter a valid country code'),
            'shop_address.required' => translate(':field is required', ['field' => translate('Shop address')]),
            'shop_address.string' => translate('Please enter a valid shop address'),
            'company_email.required' => translate(':field is required', ['field' => translate('Company email')]),
            'company_email.email' => translate('Company email must be a valid email'),
            'pagination_limit.required' => translate(':field is required', ['field' => translate('Pagination limit')]),
            'pagination_limit.min' => translate('Pagination limit cannot be less than 5'),
            'pagination_limit.max' => translate('Pagination limit cannot be more than 100'),
            'decimal_point_settings.required' => translate(':field is required', ['field' => translate('Decimal point settings')]),
            'decimal_point_settings.integer' => translate('Decimal point settings must be a whole number'),
            'decimal_point_settings.min' => translate('Decimal point settings cannot be negative'),
            'cash_on_delivery.required_without_all' => 'At least one payment option should be checked.',
            'digital_payment.required_without_all' => 'At least one payment option should be checked.',
            'offline_payment.required_without_all' => 'At least one payment option should be checked.',
        ];
    }
}
