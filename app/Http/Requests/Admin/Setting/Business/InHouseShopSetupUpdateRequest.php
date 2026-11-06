<?php

namespace App\Http\Requests\Admin\Setting\Business;

use App\Enums\WebConfigKey;
use App\Http\Requests\BaseRequest;
use App\Services\New\BusinessSettingService;

class InHouseShopSetupUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        $rules = [
            WebConfigKey::IN_HOUSE_SHOP_VERIFIED_BADGE_STATUS => ['nullable', 'boolean'],
        ];

        foreach ($this->freeDeliveryFields() as $fields) {
            $rules[$fields['status']] = ['nullable', 'boolean'];
            $rules[$fields['amount']] = ['nullable', 'numeric', 'min:0'];
        }

        foreach ($this->minimumOrderAmountFields() as $fields) {
            $rules[$fields['amount']] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            WebConfigKey::IN_HOUSE_SHOP_VERIFIED_BADGE_STATUS.'.boolean' => translate('Please turn the verified badge on or off.'),
        ];

        foreach ($this->freeDeliveryFields() as $moduleKey => $fields) {
            $business = translate(businessModuleManager()->availableInPanel()[$moduleKey]->label);

            $messages[$fields['status'].'.boolean'] = translate('Please turn the :business free delivery over amount on or off.', ['business' => $business]);
            $messages[$fields['amount'].'.numeric'] = translate('Please enter the :business free delivery over amount as a number.', ['business' => $business]);
            $messages[$fields['amount'].'.min'] = translate('The :business free delivery over amount cannot be less than zero.', ['business' => $business]);
        }

        foreach ($this->minimumOrderAmountFields() as $moduleKey => $fields) {
            $business = translate(businessModuleManager()->availableInPanel()[$moduleKey]->label);

            $messages[$fields['amount'].'.numeric'] = translate('Please enter the minimum order amount for :business as a number.', ['business' => $business]);
            $messages[$fields['amount'].'.min'] = translate('The minimum order amount for :business cannot be less than zero.', ['business' => $business]);
        }

        return $messages;
    }

    /** @return array<string, array{status: bool, amount: float}> */
    public function freeDeliveryInput(): array
    {
        $freeDelivery = [];

        foreach ($this->freeDeliveryFields() as $moduleKey => $fields) {
            if (! $this->has($fields['amount'])) {
                continue;
            }

            $freeDelivery[$moduleKey] = [
                'status' => (bool) $this[$fields['status']],
                'amount' => (float) ($this[$fields['amount']] ?? 0),
            ];
        }

        return $freeDelivery;
    }

    /** @return array<string, float> */
    public function minimumOrderAmountInput(): array
    {
        $minimumOrderAmounts = [];

        foreach ($this->minimumOrderAmountFields() as $fields) {
            if ($this->has($fields['amount'])) {
                $minimumOrderAmounts[$fields['amount']] = (float) ($this[$fields['amount']] ?? 0);
            }
        }

        return $minimumOrderAmounts;
    }

    /** @return array<string, array{status: string, amount: string}> */
    private function minimumOrderAmountFields(): array
    {
        return array_intersect_key(
            BusinessSettingService::IN_HOUSE_MINIMUM_ORDER_AMOUNT_SETTINGS,
            businessModuleManager()->availableInPanel(),
        );
    }

    /** @return array<string, array{status: string, amount: string}> */
    private function freeDeliveryFields(): array
    {
        return array_intersect_key(
            BusinessSettingService::IN_HOUSE_FREE_DELIVERY_SETTINGS,
            businessModuleManager()->availableInPanel(),
        );
    }
}
