<?php

namespace App\Http\Requests\Admin\Setting\System;

use App\Http\Requests\BaseRequest;
use App\Services\New\BusinessSettingService;

class CurrencyUpdateRequest extends BaseRequest
{
    private const CURRENCY_MODEL_KEY = 'currency_model';

    private const MULTI_CURRENCY_MODEL = 'multi_currency';

    protected $stopOnFirstFailure = false;

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {
        parent::__construct();
    }

    public function rules(): array
    {
        $rules = [
            'id' => 'required|integer',
            'name' => 'required|string',
            'symbol' => 'required|string',
            'code' => 'required|string',
        ];

        if ($this->isMultiCurrencyModel()) {
            $rules['exchange_rate'] = 'required|numeric';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'id.required' => translate(':field is required', ['field' => translate('Currency')]),
            'id.integer' => translate('Select a valid currency to update'),
            'name.required' => translate(':field is required', ['field' => translate('Currency Name')]),
            'name.string' => translate('Currency Name must be a valid text'),
            'symbol.required' => translate(':field is required', ['field' => translate('Currency symbol')]),
            'symbol.string' => translate('Currency symbol must be a valid text'),
            'code.required' => translate(':field is required', ['field' => translate('Currency code')]),
            'code.string' => translate('Currency code must be a valid text'),
            'exchange_rate.required' => translate('Currency exchange rate is required'),
            'exchange_rate.numeric' => translate('Currency exchange rate must be a number'),
        ];
    }

    private function isMultiCurrencyModel(): bool
    {
        return $this->businessSettingService->findBy(params: ['type' => self::CURRENCY_MODEL_KEY])?->value === self::MULTI_CURRENCY_MODEL;
    }
}
