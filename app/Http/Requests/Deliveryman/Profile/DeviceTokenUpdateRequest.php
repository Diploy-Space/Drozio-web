<?php

namespace App\Http\Requests\Deliveryman\Profile;

use App\Http\Requests\BaseRequest;

class DeviceTokenUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'fcm_token' => 'required|string|max:512',
        ];
    }

    public function messages(): array
    {
        return [
            'fcm_token.required' => translate('The device token is required'),
            'fcm_token.max' => translate('The device token must not be longer than 512 characters'),
        ];
    }
}
