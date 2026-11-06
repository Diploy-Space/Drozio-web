<?php

namespace App\Http\Requests\Customer\Profile;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class DeviceTokenUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'cm_firebase_token' => 'required|string|max:512',
            'guest_id' => [Rule::requiredIf(fn (): bool => auth('api')->user() === null), 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'cm_firebase_token.required' => translate('The device token is required'),
            'cm_firebase_token.max' => translate('The device token must not be longer than 512 characters'),
            'guest_id.required' => translate('Please sign in to set up notifications on this device'),
            'guest_id.integer' => translate('The guest account is not valid'),
        ];
    }
}
