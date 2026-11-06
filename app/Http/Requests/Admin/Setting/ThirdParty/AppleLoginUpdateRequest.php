<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;
use Closure;

class AppleLoginUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'client_id' => 'nullable|string|max:500',
            'client_secret' => 'nullable|string|max:500',
            'team_id' => 'nullable|string|max:500',
            'key_id' => 'nullable|string|max:500',
            'redirect_url' => 'nullable|string|max:500',
            'service_file' => [
                'nullable',
                'file',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (strtolower($value->getClientOriginalExtension()) !== 'p8') {
                        $fail(translate('File must be of type .p8'));
                    }
                },
            ],
        ];
    }
}
