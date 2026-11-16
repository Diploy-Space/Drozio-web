<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorKycProfile extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CORRECTION_REQUESTED = 'correction_requested';

    public const REG_GST = 'gst';
    public const REG_NON_GST = 'non_gst';

    protected $fillable = [
        'seller_id','shop_id','business_type','legal_name','trade_name','registration_type',
        'gst_number','pan_number','pan_holder_name','pan_verification_status',
        'business_address_line1','business_address_line2','business_city','business_state','business_pincode','business_country',
        'bank_account_name','bank_name','bank_branch','bank_account_no','bank_ifsc','bank_verification_status',
        'status','rejection_reason','correction_note','submitted_at','reviewed_at','reviewed_by',
    ];

    protected $casts = [
        'seller_id' => 'integer',
        'shop_id' => 'integer',
        'reviewed_by' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorKycDocument::class, 'vendor_kyc_profile_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VendorKycReview::class, 'vendor_kyc_profile_id')->latest('id');
    }

    public function isEditableByVendor(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED, self::STATUS_CORRECTION_REQUESTED], true);
    }

    public function canResubmit(): bool
    {
        return in_array($this->status, [self::STATUS_REJECTED, self::STATUS_CORRECTION_REQUESTED], true);
    }
}
