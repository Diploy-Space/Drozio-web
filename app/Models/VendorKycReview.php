<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorKycReview extends Model
{
    protected $fillable = [
        'vendor_kyc_profile_id','action','actor_type','actor_id','from_status','to_status','note','meta',
    ];

    protected $casts = [
        'vendor_kyc_profile_id' => 'integer',
        'actor_id' => 'integer',
        'meta' => 'array',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(VendorKycProfile::class, 'vendor_kyc_profile_id');
    }
}
