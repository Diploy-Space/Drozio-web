<?php

namespace App\Services\New;

use App\Models\Seller;
use App\Models\Shop;
use App\Models\VendorKycDocument;
use App\Models\VendorKycProfile;
use App\Models\VendorKycReview;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class VendorKycService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }

    public const DOC_DIR = 'vendor/kyc/documents/';

    public const DOC_PAN = 'pan_card';
    public const DOC_GST = 'gst_certificate';
    public const DOC_ADDRESS = 'address_proof';
    public const DOC_BANK = 'bank_proof';
    public const DOC_BUSINESS = 'business_registration';

    public function __construct(VendorKycProfile $profile)
    {
        parent::__construct($profile);
    }

    public function getOrCreateForSeller(Seller $seller): VendorKycProfile
    {
        $profile = VendorKycProfile::query()->where('seller_id', $seller->id)->first();
        if ($profile) {
            return $profile->loadMissing(['documents', 'shop']);
        }

        $shop = Shop::query()->where('seller_id', $seller->id)->first();

        $profile = VendorKycProfile::query()->create([
            'seller_id' => $seller->id,
            'shop_id' => $shop?->id,
            'trade_name' => $shop?->name,
            'gst_number' => $seller->gst,
            'bank_name' => $seller->bank_name,
            'bank_branch' => $seller->branch,
            'bank_account_no' => $seller->account_no,
            'bank_account_name' => $seller->holder_name,
            'business_address_line1' => $shop?->address,
            'status' => VendorKycProfile::STATUS_DRAFT,
        ]);

        return $profile->load(['documents', 'shop']);
    }

    public function updateProfile(VendorKycProfile $profile, array $data): VendorKycProfile
    {
        $fields = Arr::only($data, [
            'business_type','legal_name','trade_name','registration_type','gst_number',
            'pan_number','pan_holder_name',
            'business_address_line1','business_address_line2','business_city','business_state','business_pincode','business_country',
            'bank_account_name','bank_name','bank_branch','bank_account_no','bank_ifsc',
        ]);

        if (isset($fields['registration_type'])) {
            $fields['registration_type'] = in_array($fields['registration_type'], [VendorKycProfile::REG_GST, VendorKycProfile::REG_NON_GST], true)
                ? $fields['registration_type']
                : $fields['registration_type'];
        }
        if (($fields['registration_type'] ?? $profile->registration_type) === VendorKycProfile::REG_NON_GST) {
            $fields['gst_number'] = $fields['gst_number'] ?? null;
        }

        $profile->fill($fields);
        $profile->save();

        return $profile->fresh(['documents', 'shop', 'seller']);
    }

    public function requirements(?VendorKycProfile $profile = null, ?string $businessType = null, ?string $registrationType = null): array
    {
        $registrationType = $registrationType ?: ($profile?->registration_type);
        $businessType = $businessType ?: ($profile?->business_type);

        $docs = [
            ['type' => self::DOC_PAN, 'label' => 'PAN Card', 'required' => true],
            ['type' => self::DOC_ADDRESS, 'label' => 'Business Address Proof', 'required' => true],
            ['type' => self::DOC_BANK, 'label' => 'Bank Proof (cancelled cheque/passbook)', 'required' => true],
            ['type' => self::DOC_BUSINESS, 'label' => 'Business Registration Proof', 'required' => true],
        ];
        if ($registrationType === VendorKycProfile::REG_GST) {
            array_splice($docs, 1, 0, [['type' => self::DOC_GST, 'label' => 'GST Certificate', 'required' => true]]);
        }

        return [
            'business_types' => ['proprietorship', 'partnership', 'llp', 'private_limited', 'public_limited', 'other'],
            'registration_types' => [
                ['value' => VendorKycProfile::REG_GST, 'label' => 'GST Registered'],
                ['value' => VendorKycProfile::REG_NON_GST, 'label' => 'Non-GST'],
            ],
            'profile_fields' => [
                'required' => ['business_type','legal_name','trade_name','registration_type','pan_number','business_address_line1','business_pincode','bank_account_name','bank_name','bank_account_no','bank_ifsc'],
                'conditional' => ['gst_number' => 'required_when:registration_type,gst'],
            ],
            'documents' => $docs,
            'notes' => ['PAN and bank details must match legal entity where applicable.'],
        ];
    }

    public function uploadDocument(VendorKycProfile $profile, string $documentType, UploadedFile $file): VendorKycDocument
    {
        return DB::transaction(function () use ($profile, $documentType, $file) {
            $existing = VendorKycDocument::query()
                ->where('vendor_kyc_profile_id', $profile->id)
                ->where('document_type', $documentType)
                ->first();

            $storedName = $existing?->file_path
                ? $this->updateFile(dir: self::DOC_DIR, oldImage: $existing->file_path, format: $file->getClientOriginalExtension(), image: $file, fileType: 'file')
                : $this->fileUpload(dir: self::DOC_DIR, format: $file->getClientOriginalExtension(), file: $file);

            $payload = [
                'vendor_kyc_profile_id' => $profile->id,
                'document_type' => $documentType,
                'file_path' => $storedName,
                'storage' => $this->storageDisk(),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'status' => VendorKycDocument::STATUS_PENDING,
                'review_note' => null,
            ];

            if ($existing) {
                $existing->fill($payload)->save();
                return $existing->fresh();
            }

            return VendorKycDocument::query()->create($payload);
        });
    }

    public function statusPayload(VendorKycProfile $profile): array
    {
        $profile->loadMissing(['documents', 'reviews']);
        $latestReview = $profile->reviews()->latest()->first();
        return [
            'profile_status' => $profile->status,
            'is_verified' => $profile->status === VendorKycProfile::STATUS_APPROVED,
            'submitted_at' => $profile->submitted_at,
            'reviewed_at' => $profile->reviewed_at,
            'rejection_reason' => $profile->rejection_reason,
            'correction_note' => $profile->correction_note,
            'documents' => $profile->documents->map(fn ($d) => [
                'id' => $d->id,
                'document_type' => $d->document_type,
                'status' => $d->status,
                'review_note' => $d->review_note,
                'file_url' => $d->file_full_url,
                'original_name' => $d->original_name,
                'uploaded_at' => $d->updated_at,
            ])->values(),
            'last_review' => $latestReview ? [
                'action' => $latestReview->action,
                'note' => $latestReview->note,
                'to_status' => $latestReview->to_status,
                'created_at' => $latestReview->created_at,
            ] : null,
            'can_edit' => $profile->isEditableByVendor(),
            'can_resubmit' => $profile->canResubmit(),
        ];
    }

    public function submit(VendorKycProfile $profile, string $actorType = 'vendor', ?int $actorId = null, bool $isResubmit = false): array
    {
        if ($profile->status === VendorKycProfile::STATUS_APPROVED) {
            return ['ok' => false, 'message' => translate('KYC is already approved')];
        }
        if ($profile->status === VendorKycProfile::STATUS_PENDING || $profile->status === VendorKycProfile::STATUS_UNDER_REVIEW) {
            return ['ok' => false, 'message' => translate('KYC is already submitted for review')];
        }

        $missing = $this->missingRequirements($profile);
        if (! empty($missing)) {
            return ['ok' => false, 'message' => translate('Please complete all required KYC details and documents'), 'missing' => $missing];
        }

        $from = $profile->status;
        $to = VendorKycProfile::STATUS_PENDING;
        $action = $isResubmit ? 'resubmitted' : 'submitted';

        DB::transaction(function () use ($profile, $from, $to, $action, $actorType, $actorId) {
            $profile->update([
                'status' => $to,
                'submitted_at' => now(),
                'rejection_reason' => null,
                'correction_note' => null,
            ]);
            $this->logReview($profile, $action, $actorType, $actorId, $from, $to, null);
        });

        return ['ok' => true, 'message' => translate($isResubmit ? 'KYC resubmitted successfully' : 'KYC submitted successfully')];
    }

    public function resubmit(VendorKycProfile $profile, ?int $actorId = null): array
    {
        if (! $profile->canResubmit()) {
            return ['ok' => false, 'message' => translate('KYC can only be resubmitted after rejection or correction request')];
        }
        return $this->submit($profile, 'vendor', $actorId, true);
    }

    public function missingRequirements(VendorKycProfile $profile): array
    {
        $missing = [];
        $req = $this->requirements($profile);
        foreach ($req['profile_fields']['required'] as $field) {
            if (empty($profile->{$field})) $missing[] = $field;
        }
        if (($profile->registration_type) === VendorKycProfile::REG_GST && empty($profile->gst_number)) {
            $missing[] = 'gst_number';
        }
        $requiredDocs = collect($req['documents'])->where('required', true)->pluck('type')->all();
        $present = $profile->documents()->pluck('document_type')->all();
        foreach ($requiredDocs as $doc) {
            if (! in_array($doc, $present, true)) $missing[] = 'document:'.$doc;
        }
        return $missing;
    }

    public function adminList(?string $status = null, ?string $search = null, int $limit = 25)
    {
        $q = VendorKycProfile::query()->with(['seller', 'shop', 'documents']);
        if ($status) $q->where('status', $status);
        if ($search) {
            $q->where(function ($qq) use ($search) {
                $qq->where('legal_name', 'like', "%{$search}%")
                   ->orWhere('trade_name', 'like', "%{$search}%")
                   ->orWhere('gst_number', 'like', "%{$search}%")
                   ->orWhere('pan_number', 'like', "%{$search}%")
                   ->orWhereHas('seller', fn($s) => $s->where('f_name','like',"%{$search}%")->orWhere('l_name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")->orWhere('phone','like',"%{$search}%"));
            });
        }
        return $q->latest('submitted_at')->latest()->paginate($limit);
    }

    public function review(VendorKycProfile $profile, string $action, ?string $note, int $adminId): array
    {
        $from = $profile->status;
        $action = strtolower($action);
        $map = [
            'approve' => VendorKycProfile::STATUS_APPROVED,
            'reject' => VendorKycProfile::STATUS_REJECTED,
            'request_correction' => VendorKycProfile::STATUS_CORRECTION_REQUESTED,
            'under_review' => VendorKycProfile::STATUS_UNDER_REVIEW,
        ];
        if (! isset($map[$action])) {
            return ['ok' => false, 'message' => translate('Invalid review action')];
        }
        $to = $map[$action];
        if (in_array($action, ['reject','request_correction'], true) && empty($note)) {
            return ['ok' => false, 'message' => translate('Reason/note is required')];
        }

        DB::transaction(function () use ($profile, $action, $to, $from, $note, $adminId) {
            $updates = [
                'status' => $to,
                'reviewed_at' => now(),
                'reviewed_by' => $adminId,
                'rejection_reason' => $action === 'reject' ? $note : null,
                'correction_note' => $action === 'request_correction' ? $note : null,
            ];
            $profile->update($updates);
            $this->logReview($profile, $action, 'admin', $adminId, $from, $to, $note);

            if ($action === 'approve') {
                $this->syncOnApprove($profile, $adminId);
            }
        });

        return ['ok' => true, 'message' => translate('KYC review updated successfully'), 'status' => $to];
    }

    protected function syncOnApprove(VendorKycProfile $profile, int $adminId): void
    {
        $seller = Seller::query()->find($profile->seller_id);
        $shop = $profile->shop_id ? Shop::query()->find($profile->shop_id) : ($seller ? Shop::query()->where('seller_id',$seller->id)->first() : null);
        if ($seller) {
            $seller->fill(array_filter([
                'gst' => $profile->gst_number ?: $seller->gst,
                'bank_name' => $profile->bank_name ?: $seller->bank_name,
                'branch' => $profile->bank_branch ?: $seller->branch,
                'account_no' => $profile->bank_account_no ?: $seller->account_no,
                'holder_name' => $profile->bank_account_name ?: $seller->holder_name,
            ], fn($v) => $v !== null && $v !== ''));
            $seller->forceFill([
                'is_verified' => 1,
                'verified_at' => now(),
                'verified_by' => $adminId,
            ])->save();
        }
        if ($shop) {
            $shopUpdates = [];
            if ($profile->trade_name) $shopUpdates['name'] = $profile->trade_name;
            if ($profile->business_address_line1) $shopUpdates['address'] = trim($profile->business_address_line1.' '.($profile->business_address_line2 ?? ''));
            if ($profile->gst_number) $shopUpdates['tax_identification_number'] = $profile->gst_number;
            if ($shopUpdates) $shop->fill($shopUpdates)->save();
        }
    }

    public function logReview(VendorKycProfile $profile, string $action, ?string $actorType, ?int $actorId, ?string $from, ?string $to, ?string $note, array $meta = []): VendorKycReview
    {
        return VendorKycReview::query()->create([
            'vendor_kyc_profile_id' => $profile->id,
            'action' => $action,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'meta' => $meta ?: null,
        ]);
    }
}
