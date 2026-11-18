<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;

class SandboxKycUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        $enabled = (int) $this->input('status') === 1;

        return [
            'status' => 'nullable|in:0,1',
            'mode' => 'required|in:test,live',
            'base_url_test' => 'nullable|string|max:255',
            'base_url_live' => 'nullable|string|max:255',
            'api_key' => $enabled ? 'nullable|string|max:500' : 'nullable|string|max:500',
            'api_secret' => $enabled ? 'nullable|string|max:500' : 'nullable|string|max:500',
            'api_version' => 'nullable|string|max:20',
            'verify_pan' => 'nullable|in:0,1',
            'verify_gstin' => 'nullable|in:0,1',
            'verify_bank' => 'nullable|in:0,1',
            'manual_fallback' => 'nullable|in:0,1',
        ];
    }
}
