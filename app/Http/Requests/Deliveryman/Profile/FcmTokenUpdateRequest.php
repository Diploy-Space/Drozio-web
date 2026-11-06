<?php

namespace App\Http\Requests\Deliveryman\Profile;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class FcmTokenUpdateRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'fcm_token' => 'required',
        ];
    }
}
