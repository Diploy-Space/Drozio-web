<?php

namespace App\Http\Requests\Admin\Report\Earning;

use App\Http\Requests\BaseRequest;

class DurationPdfRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'duration' => ['required', 'string', 'max:50'],
            'inhouse_earning' => ['required', 'numeric'],
            'admin_commission' => ['required', 'numeric'],
            'shipping_earn' => ['required', 'numeric'],
            'deliveryman_incentive' => ['required', 'numeric'],
            'discount_given' => ['required', 'numeric'],
            'total_tax' => ['required', 'numeric'],
            'refund_given' => ['required', 'numeric'],
            'total_earning' => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'duration.required' => translate('The duration of this earning row is missing'),
            'duration.string' => translate('The duration of this earning row is not valid'),
            'duration.max' => translate('The duration of this earning row is not valid'),
            'inhouse_earning.required' => translate('The in-house earning amount is missing'),
            'inhouse_earning.numeric' => translate('The in-house earning amount must be a number'),
            'admin_commission.required' => translate('The commission earning amount is missing'),
            'admin_commission.numeric' => translate('The commission earning amount must be a number'),
            'shipping_earn.required' => translate('The shipping earning amount is missing'),
            'shipping_earn.numeric' => translate('The shipping earning amount must be a number'),
            'deliveryman_incentive.required' => translate('The delivery man incentive amount is missing'),
            'deliveryman_incentive.numeric' => translate('The delivery man incentive amount must be a number'),
            'discount_given.required' => translate('The discount given amount is missing'),
            'discount_given.numeric' => translate('The discount given amount must be a number'),
            'total_tax.required' => translate('The VAT/TAX amount is missing'),
            'total_tax.numeric' => translate('The VAT/TAX amount must be a number'),
            'refund_given.required' => translate('The refund given amount is missing'),
            'refund_given.numeric' => translate('The refund given amount must be a number'),
            'total_earning.required' => translate('The total earning amount is missing'),
            'total_earning.numeric' => translate('The total earning amount must be a number'),
        ];
    }
}
