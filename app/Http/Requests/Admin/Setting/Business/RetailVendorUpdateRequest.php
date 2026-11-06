<?php

namespace App\Http\Requests\Admin\Setting\Business;

use App\Http\Requests\BaseRequest;
use App\Services\New\BusinessSettingService;

class RetailVendorUpdateRequest extends BaseRequest
{
    private const BUSINESS_MODE_MULTI = 'multi';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {
        parent::__construct();
    }

    public function rules(): array
    {
        if ($this->businessSettingService->getValueByType(type: 'business_mode') !== self::BUSINESS_MODE_MULTI) {
            return [];
        }

        return [
            'sales_commission' => 'required|decimal:0,2|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'sales_commission.required' => translate(':field is required', ['field' => translate('Retail default commission')]),
            'sales_commission.decimal' => translate('Retail default commission must be a number with up to two decimal places'),
            'sales_commission.min' => translate('Retail default commission cannot be less than zero'),
            'sales_commission.max' => translate('Retail default commission cannot be more than 100'),
        ];
    }
}
