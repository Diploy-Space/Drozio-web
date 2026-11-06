<?php

namespace App\Http\Requests\Admin\Setting\Business;

use App\Http\Requests\BaseRequest;
use App\Services\New\BusinessSettingService;
use Illuminate\Validation\Rule;

class OrderUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            BusinessSettingService::ORDER_VERIFICATION => ['nullable', 'boolean'],
            BusinessSettingService::MINIMUM_ORDER_AMOUNT_STATUS => ['nullable', 'boolean'],
            BusinessSettingService::BILLING_INPUT_BY_CUSTOMER => ['nullable', 'boolean'],
            BusinessSettingService::GUEST_CHECKOUT => ['nullable', 'boolean'],
            BusinessSettingService::FREE_DELIVERY_STATUS => ['nullable', 'boolean'],
            BusinessSettingService::FREE_DELIVERY_RESPONSIBILITY => ['nullable', Rule::in([
                BusinessSettingService::FREE_DELIVERY_RESPONSIBILITY_ADMIN,
                BusinessSettingService::FREE_DELIVERY_RESPONSIBILITY_SELLER,
            ])],
            BusinessSettingService::FREE_DELIVERY_OVER_AMOUNT_SELLER => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            BusinessSettingService::ORDER_VERIFICATION.'.boolean' => translate('Please turn order delivery verification on or off.'),
            BusinessSettingService::MINIMUM_ORDER_AMOUNT_STATUS.'.boolean' => translate('Please turn vendor can set minimum order amount on or off.'),
            BusinessSettingService::BILLING_INPUT_BY_CUSTOMER.'.boolean' => translate('Please turn show billing address on or off.'),
            BusinessSettingService::GUEST_CHECKOUT.'.boolean' => translate('Please turn guest checkout on or off.'),
            BusinessSettingService::FREE_DELIVERY_STATUS.'.boolean' => translate('Please turn free delivery on or off.'),
            BusinessSettingService::FREE_DELIVERY_RESPONSIBILITY.'.in' => translate('Please choose who is responsible for free delivery.'),
            BusinessSettingService::FREE_DELIVERY_OVER_AMOUNT_SELLER.'.numeric' => translate('Please enter the free delivery over amount as a number.'),
            BusinessSettingService::FREE_DELIVERY_OVER_AMOUNT_SELLER.'.min' => translate('The free delivery over amount cannot be less than zero.'),
        ];
    }
}
