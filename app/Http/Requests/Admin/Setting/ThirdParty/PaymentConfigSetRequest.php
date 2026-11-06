<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use Illuminate\Contracts\Validation\ValidationRule;

class PaymentConfigSetRequest extends PaymentConfigUpdateRequest
{
    private const GATEWAY_IMAGE_PATH = 'storage/app/public/payment_modules/gateway_image/';

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        if ($this->requiresGatewayImage()) {
            $rules['gateway_image'] = 'required';
        }

        return $rules;
    }

    private function requiresGatewayImage(): bool
    {
        $gateway = $this->findPaymentGateway(keyName: $this['gateway']);
        $additionalData = $gateway?->additional_data;
        $storedImage = $additionalData === null ? null : (json_decode($additionalData)?->gateway_image ?? null);

        return empty($storedImage) || ! file_exists(base_path(self::GATEWAY_IMAGE_PATH.$storedImage));
    }
}
