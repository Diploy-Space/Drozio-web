<?php

namespace App\Http\Requests\Customer\Currency;

use App\Http\Requests\BaseRequest;
use App\Services\New\CurrencyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

class UpdateRequest extends BaseRequest
{
    private bool $currencyResolved = false;

    private ?Model $currency = null;

    public function __construct(private readonly CurrencyService $currencyService) {}

    public function rules(): array
    {
        return [
            'currency_code' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'currency_code.required' => translate(':field is required', ['field' => translate('Currency')]),
            'currency_code.string' => translate('Please select a valid currency'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('currency_code') && $this->currency() === null) {
                    $validator->errors()->add('currency_code', translate('Please select a valid currency'));
                }
            },
        ];
    }

    private function currency(): ?Model
    {
        if (! $this->currencyResolved) {
            $this->currency = $this->currencyService->findBy(params: ['code' => $this['currency_code']], relations: []);
            $this->currencyResolved = true;
        }

        return $this->currency;
    }
}
