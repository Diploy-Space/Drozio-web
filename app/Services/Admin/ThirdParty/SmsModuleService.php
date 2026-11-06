<?php

namespace App\Services\Admin\ThirdParty;

use App\Models\Setting;

class SmsModuleService
{
    public function ensureTwoFactorOtpTemplate(): void
    {
        $twoFactor = Setting::where(['key_name' => '2factor', 'settings_type' => 'sms_config'])->first();

        if ($twoFactor && $twoFactor->live_values) {
            $liveValues = is_array($twoFactor->live_values)
                ? $twoFactor->live_values
                : json_decode($twoFactor->live_values, true);
            $liveValues['otp_template'] = $liveValues['otp_template'] ?? 'Your OTP is: #OTP#';

            Setting::where(['key_name' => '2factor', 'settings_type' => 'sms_config'])->update([
                'live_values' => json_encode($liveValues),
                'test_values' => json_encode($liveValues),
            ]);
        }
    }
}
