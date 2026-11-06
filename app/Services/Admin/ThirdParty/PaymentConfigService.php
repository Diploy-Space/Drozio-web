<?php

namespace App\Services\Admin\ThirdParty;

use App\Models\Setting;

class PaymentConfigService
{
    public function isGatewayImageMissing(?string $gateway): bool
    {
        $settings = Setting::where('key_name', $gateway)->where('settings_type', 'payment_config')->first();
        $storedImage = $settings && $settings['additional_data'] != null
            ? (json_decode($settings['additional_data'])->gateway_image ?? null)
            : null;

        if (empty($storedImage)) {
            return true;
        }

        return ! file_exists(base_path('storage/app/public/payment_modules/gateway_image/'.$storedImage));
    }

    public function ensurePaymobAcceptDefaults(): void
    {
        Setting::updateOrCreate(['key_name' => 'paymob_accept', 'settings_type' => 'payment_config'], [
            'key_name' => 'paymob_accept',
            'live_values' => [
                'gateway' => "",
                'mode' => "live",
                'status' => "0",
                'supported_country' => "",
                'public_key' => "",
                'secret_key' => "",
                'integration_id' => "",
                'hmac' => "",
            ],
            'test_values' => [
                'gateway' => "",
                'mode' => "test",
                'status' => "0",
                'supported_country' => "",
                'public_key' => "",
                'secret_key' => "",
                'integration_id' => "",
                'hmac' => "",
            ],
            'settings_type' => 'payment_config',
            'mode' => 'test',
            'is_active' => 0,
            'additional_data' => null,
        ]);
    }
}
