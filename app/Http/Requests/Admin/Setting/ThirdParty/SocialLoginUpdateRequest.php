<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;

class SocialLoginUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'client_id' => 'nullable|string|max:500',
            'client_secret' => 'nullable|string|max:500',
        ];
    }
}
