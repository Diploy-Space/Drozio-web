<?php

namespace App\Http\Requests\Admin\ClearanceSale;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Validator;

class ConfigUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'setup_by' => 'required',
            'discount_type' => 'required',
            'discount_amount' => 'numeric|min:0',
            'offer_active_time' => 'required',
            'clearance_sale_duration' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'setup_by.required' => translate('The setup by field is required'),
            'discount_type.required' => translate('The discount type field is required'),
            'discount_amount.numeric' => translate('The discount amount must be a number'),
            'discount_amount.min' => translate('The discount amount cannot be less than zero'),
            'offer_active_time.required' => translate('The offer active time field is required'),
            'clearance_sale_duration.required' => translate(':field is required', ['field' => translate('Clearance sale duration')]),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (empty($this['clearance_sale_duration'])) {
                    $validator->errors()->add(
                        'clearance_sale_duration', translate(':field is required', ['field' => translate('Clearance sale duration')]).'!'
                    );
                } else {
                    $dates = explode(' - ', $this['clearance_sale_duration']);
                    if (count($dates) !== 2 || ! checkDateFormatInMDYAndTime($dates[0]) || ! checkDateFormatInMDYAndTime($dates[1])) {
                        $validator->errors()->add(
                            'clearance_sale_duration', translate('Invalid date range format').'!'
                        );
                    }
                }

                if ($this['discount_type'] == 'flat' && $this['discount_amount'] <= 0) {
                    $validator->errors()->add(
                        'discount_amount', translate(':field is required', ['field' => translate('Discount Amount')]).'!'
                    );
                }

                if ($this['discount_type'] == 'flat' && $this['discount_amount'] > 100) {
                    $validator->errors()->add(
                        'discount_amount', translate('Discount amount cannot be greater than 100').'!'
                    );
                }

                if ($this['offer_active_time'] == 'specific_time' && empty($this['offer_active_range'])) {
                    $validator->errors()->add(
                        'offer_active_range', translate('Offer active range is required').'!'
                    );
                }

                if ($this['offer_active_time'] == 'specific_time' && ! empty($this['offer_active_range'])) {
                    $dates = explode(' - ', $this['offer_active_range']);
                    if (count($dates) !== 2 || ! checkTimeFormatInRequestTime($dates[0]) || ! checkTimeFormatInRequestTime($dates[1])) {
                        $validator->errors()->add(
                            'offer_active_range', translate('Invalid time range format').'!'
                        );
                    }
                }
            },
        ];
    }
}
