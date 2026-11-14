<?php

namespace App\Http\Requests\Vendor\DrojioVerification;

use App\Http\Requests\BaseRequest;
use App\Models\VendorKycProfile;

class ProfileUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        $isGst = $this->input('registration_type') === VendorKycProfile::REG_GST;
        return [
            'business_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'legal_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'trade_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'registration_type' => ['sometimes', 'nullable', 'in:gst,non_gst'],
            'gst_number' => [$isGst ? 'sometimes' : 'sometimes', 'nullable', 'string', 'max:50'],
            'pan_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'pan_holder_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'business_address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'business_address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'business_city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'business_state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'business_pincode' => ['sometimes', 'nullable', 'string', 'max:20'],
            'business_country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_account_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bank_branch' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bank_account_no' => ['sometimes', 'nullable', 'string', 'max:50'],
            'bank_ifsc' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }
}
