<?php

namespace App\Http\Requests\Vendor\Order;

use App\Http\Requests\BaseRequest;

class ThirdPartyDeliveryRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'delivery_service_name' => ['nullable', 'string', 'max:255'],
            'third_party_delivery_tracking_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_service_name.string' => translate('Enter a valid delivery service name'),
            'delivery_service_name.max' => translate('The delivery service name is too long'),
            'third_party_delivery_tracking_id.string' => translate('Enter a valid tracking id'),
            'third_party_delivery_tracking_id.max' => translate('The tracking id is too long'),
        ];
    }
}
