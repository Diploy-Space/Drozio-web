<?php

namespace App\Http\Requests\Vendor\DeliveryMan;

use App\Http\Requests\BaseRequest;

class StatusUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => translate('Send whether the delivery man is active or inactive'),
            'status.boolean' => translate('This setting can only be turned on or off'),
        ];
    }
}
