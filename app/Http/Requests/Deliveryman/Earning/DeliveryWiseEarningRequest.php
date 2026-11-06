<?php

namespace App\Http\Requests\Deliveryman\Earning;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class DeliveryWiseEarningRequest extends BaseRequest
{
    protected bool $useLegacyApiErrorFormat = true;

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'offset' => 'required',
            'limit' => 'required',
        ];
    }
}
