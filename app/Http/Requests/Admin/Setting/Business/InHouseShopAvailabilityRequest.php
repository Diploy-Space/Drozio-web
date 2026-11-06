<?php

namespace App\Http\Requests\Admin\Setting\Business;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class InHouseShopAvailabilityRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in($this->availableModuleKeys())],
        ];
    }

    public function messages(): array
    {
        return [
            'status.array' => translate('Please choose which businesses your shop stays open for.'),
            'status.*.string' => translate('One of the selected businesses is not available on your shop.'),
            'status.*.in' => translate('One of the selected businesses is not available on your shop.'),
        ];
    }

    /** @return string[] */
    public function availableModuleKeys(): array
    {
        return array_keys(businessModuleManager()->availableInPanel());
    }
}
