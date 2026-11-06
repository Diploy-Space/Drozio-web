<?php

namespace App\Http\Requests\Customer\Shop;

use App\Http\Requests\BaseRequest;
use App\Services\New\ProductRetailService;
use Illuminate\Validation\Rule;

class VacationCheckRequest extends BaseRequest
{
    private const ADDED_BY = [ProductRetailService::ADDED_BY_SELLER, ProductRetailService::ADDED_BY_ADMIN];

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'added_by' => ['required', Rule::in(self::ADDED_BY)],
            'user_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Please select a product'),
            'id.integer' => translate('The selected product is not valid'),
            'added_by.required' => translate('Please select a shop'),
            'added_by.in' => translate('The selected shop is not valid'),
            'user_id.integer' => translate('The selected shop is not valid'),
        ];
    }

    public function isSellerShop(): bool
    {
        return $this['added_by'] === ProductRetailService::ADDED_BY_SELLER;
    }
}
