<?php

namespace App\Http\Requests\Admin\Customer;

use App\Http\Requests\BaseRequest;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $f_name
 * @property string $l_name
 * @property string $phone
 * @property string $image
 * @property string $email
 * @property $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property $created_at
 * @property $updated_at
 * @property string $street_address
 * @property string $country
 * @property string $city
 * @property string $zip
 * @property string $house_no
 * @property string $apartment_no
 * @property string|null $cm_firebase_token
 * @property bool $is_active
 * @property string|null $payment_card_last_four
 * @property string|null $payment_card_brand
 * @property string|null $payment_card_fawry_token
 * @property string|null $login_medium
 * @property string|null $social_id
 * @property bool $is_phone_verified
 * @property string|null $temporary_token
 * @property bool $is_email_verified
 * @property float $wallet_balance
 * @property float $loyalty_point
 * @property int $login_hit_count
 * @property bool $is_temp_blocked
 * @property $temp_block_time
 * @property string|null $referral_code
 * @property int $referred_by
 */
class SettingsUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        $rules = [
            'add_fund_bonus' => 'nullable|numeric|max:100|min:0',
            'loyalty_point_exchange_rate' => 'nullable|numeric|min:1',
            'ref_earning_exchange_rate' => 'nullable|numeric|min:0',
            'maximum_add_fund_amount' => 'nullable|numeric|min:0',
            'minimum_add_fund_amount' => 'nullable|numeric|min:1',
            'item_purchase_point' => 'nullable|numeric|min:0',
            'minimum_transfer_point' => 'nullable|numeric|min:0',
        ];

        if ($this->request->get('ref_earning_discount_status') == 1) {
            $rules = array_merge($rules, [
                'discount_amount' => 'required|numeric|min:0',
                'discount_type' => 'required|in:percentage,flat',
                'validity' => 'required|numeric|min:1',
                'validity_type' => 'required|in:day,week,month',
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'add_fund_bonus.numeric' => translate('The add fund bonus must be a number'),
            'add_fund_bonus.max' => translate('The add fund bonus cannot exceed 100'),
            'add_fund_bonus.min' => translate('The add fund bonus must be at least 0'),
            'loyalty_point_exchange_rate.numeric' => translate('The loyalty point exchange rate must be a number'),
            'loyalty_point_exchange_rate.min' => translate('The loyalty point exchange rate must be at least 1'),
            'ref_earning_exchange_rate.numeric' => translate('The referral earning exchange rate must be a number'),
            'ref_earning_exchange_rate.min' => translate('The referral earning exchange rate must be at least 0'),
            'maximum_add_fund_amount.numeric' => translate('The maximum add fund amount must be a number'),
            'maximum_add_fund_amount.min' => translate('The maximum add fund amount must be at least 0'),
            'minimum_add_fund_amount.numeric' => translate('The minimum add fund amount must be a number'),
            'minimum_add_fund_amount.min' => translate('The minimum add fund amount must be at least 1'),
            'item_purchase_point.numeric' => translate('The item purchase point must be a number'),
            'item_purchase_point.min' => translate('The item purchase point must be at least 0'),
            'minimum_transfer_point.numeric' => translate('The minimum transfer point must be a number'),
            'minimum_transfer_point.min' => translate('The minimum transfer point must be at least 0'),
            'discount_amount.required' => translate('The discount amount field is required when referral earning discount is enabled'),
            'discount_amount.numeric' => translate('The discount amount must be a number'),
            'discount_amount.min' => translate('The discount amount must be at least 0'),
            'discount_type.required' => translate('The discount type field is required when referral earning discount is enabled'),
            'discount_type.in' => translate('The discount type must be either percentage or amount'),
            'validity.required' => translate('The validity field is required when referral earning discount is enabled'),
            'validity.numeric' => translate('The validity must be a number'),
            'validity.min' => translate('The validity must be at least 1'),
            'validity_type.required' => translate('The validity type field is required when referral earning discount is enabled'),
            'validity_type.in' => translate('The validity type must be day week or month'),
        ];
    }
}
