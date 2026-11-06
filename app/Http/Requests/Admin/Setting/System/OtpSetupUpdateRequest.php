<?php

namespace App\Http\Requests\Admin\Setting\System;

use App\Http\Requests\BaseRequest;
use App\Services\New\BusinessSettingService;

class OtpSetupUpdateRequest extends BaseRequest
{
    public const MIN_ATTEMPTS = 1;

    public const MAX_ATTEMPTS = 20;

    public const MIN_SECONDS = 10;

    public const MAX_SECONDS = 86400;

    public const UNIT_SECONDS = 'seconds';

    public const UNIT_ATTEMPTS = 'attempts';

    public function rules(): array
    {
        $rules = [];

        foreach (self::fields() as $name => $field) {
            $rules[$name] = ['required', 'integer', 'min:'.$field['min'], 'max:'.$field['max']];
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [];

        foreach (self::fields() as $name => $field) {
            $range = self::rangeMessage($field);

            $messages[$name.'.required'] = translate(':field is required', ['field' => translate($field['label'])]);
            $messages[$name.'.integer'] = $range;
            $messages[$name.'.min'] = $range;
            $messages[$name.'.max'] = $range;
        }

        return $messages;
    }

    public static function rangeMessage(array $field): string
    {
        return translate($field['unit'] === self::UNIT_SECONDS
            ? ':field must be between :min and :max seconds'
            : ':field must be between :min and :max',
            ['min' => $field['min'], 'max' => $field['max'], 'field' => translate($field['label'])],
        );
    }

    /** @return array<string, array{label: string, unit: string, min: int, max: int}> */
    public static function fields(): array
    {
        return [
            'maximum_otp_hit' => ['label' => 'Maximum OTP Hit', 'unit' => self::UNIT_ATTEMPTS, 'min' => self::MIN_ATTEMPTS, 'max' => self::MAX_ATTEMPTS],
            'otp_resend_time' => ['label' => 'OTP Resend Time', 'unit' => self::UNIT_SECONDS, 'min' => self::MIN_SECONDS, 'max' => self::MAX_SECONDS],
            'temporary_block_time' => ['label' => 'Temporary Block Time', 'unit' => self::UNIT_SECONDS, 'min' => self::MIN_SECONDS, 'max' => self::MAX_SECONDS],
            'otp_expiry_time' => ['label' => 'OTP Expiry Time', 'unit' => self::UNIT_SECONDS, 'min' => BusinessSettingService::MINIMUM_OTP_EXPIRY_SECONDS, 'max' => self::MAX_SECONDS],
            'maximum_login_hit' => ['label' => 'Maximum login hit', 'unit' => self::UNIT_ATTEMPTS, 'min' => self::MIN_ATTEMPTS, 'max' => self::MAX_ATTEMPTS],
            'temporary_login_block_time' => ['label' => 'Temporary login block time', 'unit' => self::UNIT_SECONDS, 'min' => self::MIN_SECONDS, 'max' => self::MAX_SECONDS],
        ];
    }
}
