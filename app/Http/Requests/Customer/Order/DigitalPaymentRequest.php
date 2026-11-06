<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class DigitalPaymentRequest extends BaseRequest
{
    private const APP_PAYMENT_REQUEST = 'app';

    public function rules(): array
    {
        $rules = [
            'payment_method' => 'required',
            'payment_platform' => 'required',
            'customer_id' => Rule::requiredIf(fn (): bool => ! $this->isApiRequest() && $this->isAppPaymentRequest()),
            'is_guest' => Rule::requiredIf(fn (): bool => ! $this->isApiRequest() && $this->isAppPaymentRequest()),
        ];

        if (! $this->isApiRequest()) {
            return $rules;
        }

        return $rules + [
            'guest_id' => 'nullable|integer',
            'address_id' => 'nullable|integer',
            'billing_address_id' => 'nullable|integer',
            'coupon_code' => 'nullable|string',
            'coupon_discount' => 'nullable|numeric',
            'order_note' => 'nullable|string',
            'current_currency_code' => 'nullable|string',
            'external_redirect_link' => 'nullable|string',
            'password' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => translate(':field is required', ['field' => translate('Payment method')]),
            'payment_platform.required' => translate(':field is required', ['field' => translate('Payment platform')]),
            'customer_id.required' => translate(':field is required', ['field' => translate('Customer')]),
            'is_guest.required' => translate(':field is required', ['field' => translate('Guest customer')]),
            'guest_id.integer' => translate('The guest session could not be identified'),
            'address_id.integer' => translate('The selected shipping address could not be identified'),
            'billing_address_id.integer' => translate('The selected billing address could not be identified'),
            'coupon_code.string' => translate('The coupon code must be text'),
            'coupon_discount.numeric' => translate('The coupon discount must be a number'),
            'order_note.string' => translate('The order note must be text'),
            'current_currency_code.string' => translate('The selected currency could not be identified'),
            'external_redirect_link.string' => translate('The redirect link must be text'),
            'password.string' => translate('Please enter a valid password'),
        ];
    }

    public function couponCode(): string
    {
        return is_scalar($this->input('coupon_code')) ? (string) $this->input('coupon_code') : '';
    }

    public function isCreateAccountRequested(): bool
    {
        return (bool) $this->input('is_check_create_account');
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->isApiRequest()) {
            parent::failedValidation($validator);

            return;
        }

        $errors = $this->formatLegacyValidationErrors($validator);

        if ($this->isAppPaymentRequest()) {
            throw new HttpResponseException(response()->json(['errors' => $errors], 403));
        }

        foreach ($errors as $error) {
            ToastMagic::error($error['message']);
        }

        throw new HttpResponseException(redirect()->back());
    }

    private function isAppPaymentRequest(): bool
    {
        return in_array($this->input('payment_request_from'), [self::APP_PAYMENT_REQUEST]);
    }
}
