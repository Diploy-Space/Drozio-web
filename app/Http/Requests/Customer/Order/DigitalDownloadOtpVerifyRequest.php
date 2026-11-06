<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseRequest;

class DigitalDownloadOtpVerifyRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'guest_id' => 'nullable|integer',
                'otp' => 'required|string',
                'action' => 'nullable|string',
            ];
        }

        return [
            'order_details_id' => 'nullable',
            'otp' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.integer' => translate('Enter a valid guest ID'),
            'otp.required' => translate('Enter the OTP you received'),
            'otp.string' => translate('Enter a valid OTP'),
            'action.string' => translate('Enter a valid download action'),
        ];
    }
}
