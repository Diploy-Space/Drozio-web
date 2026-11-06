<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Enums\GlobalConstant;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class PaymentStatusUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'key_name' => ['required', 'string', Rule::in(GlobalConstant::DEFAULT_PAYMENT_GATEWAYS)],
            'status' => 'nullable|in:0,1',
        ];
    }
}
