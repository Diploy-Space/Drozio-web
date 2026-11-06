<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class AnalyticsUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([
                'meta_pixel', 'linkedin_insight', 'tiktok_tag', 'snapchat_tag',
                'twitter_tag', 'pinterest_tag', 'google_tag_manager', 'google_analytics',
            ])],
            // A tracking ID only — restrict to a safe charset so it can never break out
            // of the inline <script> string literals it is echoed into on the storefront.
            'script_id' => ['nullable', 'string', 'max:191', 'regex:/^[A-Za-z0-9._-]+$/'],
            'is_active' => 'nullable|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'script_id.regex' => translate('The script id may only contain letters, numbers, dashes, dots and underscores'),
        ];
    }
}
