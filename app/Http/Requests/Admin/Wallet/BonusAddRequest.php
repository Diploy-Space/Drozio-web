<?php

namespace App\Http\Requests\Admin\Wallet;

use App\Http\Requests\BaseRequest;

class BonusAddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = false;

    public function rules(): array
    {
        return [
            'title' => 'required',
            'bonus_type' => 'required',
            'bonus_amount' => 'required|numeric|min:.01',
            'min_add_money_amount' => 'required|numeric|min:.01',
            'max_bonus_amount' => 'required_if:bonus_type,percentage',
            'start_date_time' => 'required',
            'end_date_time' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => translate('The title field is required.'),
            'bonus_type.required' => translate('The bonus type field is required.'),
            'bonus_amount.required' => translate('The bonus amount field is required.'),
            'bonus_amount.numeric' => translate('The bonus amount must be a number'),
            'bonus_amount.min' => translate('The bonus amount must be at least 0.01.'),
            'min_add_money_amount.required' => translate('The minimum add money amount field is required.'),
            'min_add_money_amount.numeric' => translate('The minimum add money amount must be a number'),
            'min_add_money_amount.min' => translate('The minimum add money amount must be at least 0.01.'),
            'start_date_time.required' => translate('The start date and time field is required.'),
            'end_date_time.required' => translate('The end date and time field is required.'),
        ];
    }
}
