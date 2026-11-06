<?php

namespace App\Http\Requests\Admin\Setting\System;

use App\Http\Requests\BaseRequest;
use Modules\TaxModule\app\Traits\VatTaxManagement;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $icon
 * @property int $parent_id
 * @property int $position
 * @property int $home_status
 * @property int $priority
 */
class CurrencyAddRequest extends BaseRequest
{
    use VatTaxManagement;

    protected $stopOnFirstFailure = false;

    public function rules(): array
    {
        $result = [
            'name' => 'required|string',
            'symbol' => 'required|string',
            'code' => 'required|string',
        ];

        $currencyModel = getWebConfig(name: 'currency_model');
        if ($currencyModel == 'multi_currency') {
            $result['exchange_rate'] = 'required';
        }

        return $result;
    }

    public function messages(): array
    {
        $result = [
            'name.required' => translate(':field is required', ['field' => translate('Currency Name')]),
            'symbol.required' => translate(':field is required', ['field' => translate('Currency symbol')]),
            'code.required' => translate(':field is required', ['field' => translate('Currency code')]),
        ];

        $currencyModel = getWebConfig(name: 'currency_model');
        if ($currencyModel == 'multi_currency') {
            $result['exchange_rate.required'] = translate('Currency exchange rate is required');
        }

        return $result;
    }
}
