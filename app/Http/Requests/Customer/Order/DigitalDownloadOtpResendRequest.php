<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;

class DigitalDownloadOtpResendRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'guest_id' => 'nullable|integer',
            ];
        }

        return [
            'order_details_id' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.integer' => translate('Enter a valid guest ID'),
        ];
    }
}
