<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;

class MapApiUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => 'nullable|in:0,1',
            'map_api_key' => 'nullable|string|max:500',
            'map_api_key_server' => 'nullable|string|max:500',
        ];
    }
}
