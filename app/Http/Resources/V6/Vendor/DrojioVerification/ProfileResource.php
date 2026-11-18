<?php

namespace App\Http\Resources\V6\Vendor\DrojioVerification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_id' => $this->seller_id,
            'shop_id' => $this->shop_id,
            'business_type' => $this->business_type,
            'legal_name' => $this->legal_name,
            'trade_name' => $this->trade_name,
            'registration_type' => $this->registration_type,
            'verification_mode' => $this->verification_mode,
            'gst_number' => $this->gst_number,
            'gst_verification_status' => $this->gst_verification_status,
            'gst_verified_at' => $this->gst_verified_at,
            'gst_verification_ref' => $this->gst_verification_ref,
            'pan_number' => $this->pan_number,
            'pan_holder_name' => $this->pan_holder_name,
            'pan_dob' => $this->pan_dob,
            'pan_verification_status' => $this->pan_verification_status,
            'pan_verified_at' => $this->pan_verified_at,
            'pan_verification_ref' => $this->pan_verification_ref,
            'business_address' => [
                'line1' => $this->business_address_line1,
                'line2' => $this->business_address_line2,
                'city' => $this->business_city,
                'state' => $this->business_state,
                'pincode' => $this->business_pincode,
                'country' => $this->business_country,
            ],
            'bank_account' => [
                'account_name' => $this->bank_account_name,
                'bank_name' => $this->bank_name,
                'branch' => $this->bank_branch,
                'account_no' => $this->bank_account_no,
                'ifsc' => $this->bank_ifsc,
                'verification_status' => $this->bank_verification_status,
                'verified_at' => $this->bank_verified_at,
                'verification_ref' => $this->bank_verification_ref,
            ],
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'correction_note' => $this->correction_note,
            'submitted_at' => $this->submitted_at,
            'reviewed_at' => $this->reviewed_at,
            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(fn($d) => [
                    'id' => $d->id,
                    'document_type' => $d->document_type,
                    'status' => $d->status,
                    'verification_method' => $d->verification_method ?? 'manual',
                    'verification_provider' => $d->verification_provider,
                    'verification_reference' => $d->verification_reference,
                    'verified_at' => $d->verified_at,
                    'review_note' => $d->review_note,
                    'file_url' => $d->file_full_url,
                    'original_name' => $d->original_name,
                    'uploaded_at' => $d->updated_at,
                ])->values();
            }),
        ];
    }
}
