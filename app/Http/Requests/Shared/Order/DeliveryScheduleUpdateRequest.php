<?php

namespace App\Http\Requests\Shared\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderSettlementService;
use Illuminate\Validation\Rule;

class DeliveryScheduleUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'deliveryman_charge' => ['nullable', 'numeric', 'max:'.self::MAX_NUMERIC_VALUE],
                'expected_delivery_date' => ['nullable', 'date_format:Y-m-d'],
            ];
        }

        return [
            'field_name' => ['required', Rule::in(OrderSettlementService::DELIVERY_SCHEDULE_FIELDS)],
            'field_val' => match ($this['field_name']) {
                OrderSettlementService::FIELD_DELIVERYMAN_CHARGE => ['required', 'numeric', 'max:'.self::MAX_NUMERIC_VALUE],
                OrderSettlementService::FIELD_EXPECTED_DELIVERY_DATE => ['required', 'date_format:Y-m-d'],
                default => ['required'],
            },
        ];
    }

    public function messages(): array
    {
        return [
            'deliveryman_charge.numeric' => translate('The deliveryman charge must be a number'),
            'deliveryman_charge.max' => translate('The deliveryman charge is too large'),
            'expected_delivery_date.date_format' => translate('Enter the expected delivery date as year-month-day'),
            'field_name.required' => translate('Choose the deliveryman charge or the expected delivery date to update'),
            'field_name.in' => translate('Only the deliveryman charge or the expected delivery date can be updated'),
            'field_val.required' => translate('Enter a value to update'),
            'field_val.numeric' => translate('The deliveryman charge must be a number'),
            'field_val.max' => translate('The deliveryman charge is too large'),
            'field_val.date_format' => translate('Enter the expected delivery date as year-month-day'),
        ];
    }
}
