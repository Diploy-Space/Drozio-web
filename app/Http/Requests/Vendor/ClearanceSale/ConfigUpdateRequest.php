<?php

namespace App\Http\Requests\Vendor\ClearanceSale;

use App\Enums\ClearanceDiscountType;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ConfigUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'discount_type' => ['required', Rule::in([ClearanceDiscountType::FLAT, ClearanceDiscountType::PRODUCT_WISE])],
            'discount_amount' => 'numeric|min:0',
            'offer_active_time' => 'required',
            'clearance_sale_duration' => 'required',
            'offer_active_range' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'discount_type.required' => translate('The discount type field is required'),
            'discount_type.in' => translate('The discount type must be flat or product wise'),
            'discount_amount.numeric' => translate('The discount amount must be a number'),
            'discount_amount.min' => translate('The discount amount cannot be less than zero'),
            'offer_active_time.required' => translate('The offer active time field is required'),
            'clearance_sale_duration.required' => translate(':field is required', ['field' => translate('Clearance sale duration')]),
            'offer_active_range.string' => translate('Invalid time range format'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! empty($this['clearance_sale_duration'])) {
                    $dates = explode(' - ', $this['clearance_sale_duration']);

                    if (count($dates) !== 2 || ! checkDateFormatInMDYAndTime($dates[0]) || ! checkDateFormatInMDYAndTime($dates[1])) {
                        $validator->errors()->add('clearance_sale_duration', translate('Invalid date range format').'!');
                    }
                }

                if ($this['discount_type'] === ClearanceDiscountType::FLAT && $this['discount_amount'] <= 0) {
                    $validator->errors()->add(
                        'discount_amount', translate(':field is required', ['field' => translate('Discount Amount')]).'!'
                    );
                }

                if ($this['discount_type'] === ClearanceDiscountType::FLAT && $this['discount_amount'] > 100) {
                    $validator->errors()->add('discount_amount', translate('Discount amount cannot be greater than 100').'!');
                }

                if ($this['offer_active_time'] !== 'specific_time') {
                    return;
                }

                if (empty($this['offer_active_range'])) {
                    $validator->errors()->add('offer_active_range', translate('Offer active range is required').'!');

                    return;
                }

                $times = explode(' - ', $this['offer_active_range']);

                if (count($times) !== 2 || ! checkTimeFormatInRequestTime($times[0]) || ! checkTimeFormatInRequestTime($times[1])) {
                    $validator->errors()->add('offer_active_range', translate('Invalid time range format').'!');
                }
            },
        ];
    }
}
