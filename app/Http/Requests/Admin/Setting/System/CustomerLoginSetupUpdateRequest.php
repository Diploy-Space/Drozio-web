<?php

namespace App\Http\Requests\Admin\Setting\System;

use App\Http\Requests\BaseRequest;

class CustomerLoginSetupUpdateRequest extends BaseRequest
{
    private const TOGGLES = [
        'manual_login',
        'otp_login',
        'social_login',
        'google_login',
        'facebook_login',
        'apple_login',
        'email_verification',
        'phone_verification',
    ];

    protected function prepareForValidation(): void
    {
        $submitted = array_intersect_key($this->all(), array_flip(self::TOGGLES));

        $this->merge(array_map(static function ($value) {
            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $parsed === null ? $value : (int) $parsed;
        }, $submitted));
    }

    public function rules(): array
    {
        return array_fill_keys(self::TOGGLES, ['nullable', 'boolean']);
    }

    public function messages(): array
    {
        return array_merge(...array_map(static fn (string $toggle): array => [
            $toggle.'.boolean' => translate('This setting can only be turned on or off'),
        ], self::TOGGLES));
    }
}
