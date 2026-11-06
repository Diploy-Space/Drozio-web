<?php

namespace App\Http\Requests\Admin\Shipping;

use App\Http\Requests\BaseRequest;

class DeliveryModeUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.boolean' => translate('The switch can only be turned on or off'),
        ];
    }
}
