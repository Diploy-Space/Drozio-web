<?php

namespace App\Http\Requests\Shared\DeliveryMan;

use App\Http\Requests\BaseRequest;

class WithdrawRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'approved' => 'required',
            'note' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'approved.required' => translate('Please choose whether to approve or deny this request').'.',
            'note.required' => translate('Please type your note').'.',
        ];
    }
}
