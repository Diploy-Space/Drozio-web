<?php

namespace App\Http\Requests\Deliveryman\Profile;

use App\Http\Requests\BaseRequest;

class OnlineStatusRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'is_online' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'is_online.required' => translate('Choose whether you are going online or offline'),
            'is_online.boolean' => translate('The online status must be either 0 or 1'),
        ];
    }
}
