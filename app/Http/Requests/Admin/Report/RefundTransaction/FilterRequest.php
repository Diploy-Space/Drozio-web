<?php

namespace App\Http\Requests\Admin\Report\RefundTransaction;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class FilterRequest extends BaseRequest
{
    public const PAYMENT_METHOD_ALL = 'all';

    public const PAYMENT_METHODS = ['cash', 'digitally_paid', 'customer_wallet'];

    public function rules(): array
    {
        return [
            'searchValue' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', Rule::in([self::PAYMENT_METHOD_ALL, ...self::PAYMENT_METHODS])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'searchValue.string' => translate('Enter a valid search term'),
            'searchValue.max' => translate('The search term is too long'),
            'payment_method.in' => translate('Choose a valid payment method'),
            'page.integer' => translate('Choose a valid page'),
            'page.min' => translate('Choose a valid page'),
        ];
    }

    public function paymentMethod(): ?string
    {
        $paymentMethod = $this->validated('payment_method');

        return $paymentMethod === self::PAYMENT_METHOD_ALL ? null : $paymentMethod;
    }
}
