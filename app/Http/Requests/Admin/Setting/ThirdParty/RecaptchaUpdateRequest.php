<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;

class RecaptchaUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => 'nullable|in:0,1',
            'site_key' => 'nullable|string|max:500',
            'secret_key' => 'nullable|string|max:500',
        ];
    }
}
