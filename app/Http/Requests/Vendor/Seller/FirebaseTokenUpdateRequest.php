<?php

namespace App\Http\Requests\Vendor\Seller;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class FirebaseTokenUpdateRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'cm_firebase_token' => 'required',
        ];
    }
}
