<?php

namespace App\Http\Requests\Customer\Wallet;

use App\Http\Requests\BaseRequest;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddFundRequest extends BaseRequest
{
    private const APP_PAYMENT_REQUEST = 'app';

    public function rules(): array
    {
        if (! $this->isApiRequest()) {
            return [
                'amount' => 'required',
                'payment_method' => 'required',
                'payment_platform' => 'required',
            ];
        }

        return [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_platform' => 'required|string|in:web,app',
            'current_currency_code' => 'nullable|string|max:10',
            'external_redirect_link' => 'nullable|url',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => translate(':field is required', ['field' => translate('Amount')]),
            'amount.numeric' => translate('Please enter a valid amount'),
            'amount.min' => translate('The amount must be greater than zero'),
            'payment_method.required' => translate(':field is required', ['field' => translate('Payment method')]),
            'payment_platform.required' => translate(':field is required', ['field' => translate('Payment platform')]),
            'payment_platform.in' => translate('Please select a valid payment platform'),
            'current_currency_code.max' => translate('Please select a valid currency'),
            'external_redirect_link.url' => translate('Please enter a valid redirect link'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->isApiRequest()) {
            parent::failedValidation($validator);

            return;
        }

        $errors = $this->formatLegacyValidationErrors($validator);

        if (in_array($this->input('payment_request_from'), [self::APP_PAYMENT_REQUEST])) {
            throw new HttpResponseException(response()->json(['errors' => $errors]));
        }

        foreach ($errors as $error) {
            ToastMagic::error($error['message']);
        }

        throw new HttpResponseException(redirect()->back());
    }
}
