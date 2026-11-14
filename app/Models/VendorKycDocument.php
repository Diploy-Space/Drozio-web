<?php

namespace App\Models;

use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorKycDocument extends Model
{
    use StorageTrait;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'vendor_kyc_profile_id','document_type','file_path','storage','original_name','mime_type','file_size','status','review_note',
    ];

    protected $appends = ['file_full_url'];

    protected $casts = [
        'vendor_kyc_profile_id' => 'integer',
        'file_size' => 'integer',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(VendorKycProfile::class, 'vendor_kyc_profile_id');
    }

    public function getFileFullUrlAttribute(): string|null|array
    {
        return $this->storageLink('vendor/kyc/documents', $this->file_path, $this->storage ?? 'public');
    }
}
