<?php

namespace App\Services\New;

use App\Models\Seller;
use App\Models\Shop;
use App\Models\VendorKycDocument;
use App\Models\VendorKycProfile;
use App\Models\VendorKycReview;
use App\Services\BaseService;
use App\Services\New\Sandbox\SandboxKycVerificationService;
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

    protected function sandbox(): SandboxKycVerificationService
    {
        return app(SandboxKycVerificationService::class);
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
            'verification_mode' => 'manual',
        ]);

        return $profile->load(['documents', 'shop']);
    }

    public function updateProfile(VendorKycProfile $profile, array $data): VendorKycProfile
    {
        $fields = Arr::only($data, [
            'business_type','legal_name','trade_name','registration_type','gst_number',
            'pan_number','pan_holder_name','pan_dob',
            'business_address_line1','business_address_line2','business_city','business_state','business_pincode','business_country',
            'bank_account_name','bank_name','bank_branch','bank_account_no','bank_ifsc',
            'verification_mode',
        ]);

        if (isset($fields['registration_type'])) {
            $fields['registration_type'] = in_array($fields['registration_type'], [VendorKycProfile::REG_GST, VendorKycProfile::REG_NON_GST], true)
                ? $fields['registration_type']
                : $fields['registration_type'];
        }
        if (($fields['registration_type'] ?? $profile->registration_type) === VendorKycProfile::REG_NON_GST) {
            $fields['gst_number'] = $fields['gst_number'] ?? null;
            $fields['gst_verification_status'] = null;
        }

        $profile->fill($fields);
        $profile->save();

        return $profile->fresh(['documents', 'shop', 'seller']);
    }

    public function requirements(?VendorKycProfile $profile = null, ?string $businessType = null, ?string $registrationType = null): array
    {
        $registrationType = $registrationType ?: ($profile?->registration_type);
        $businessType = $businessType ?: ($profile?->business_type);
        $sandbox = $this->sandbox();
        $online = $sandbox->isEnabled();

        $docs = [
            ['type' => self::DOC_PAN, 'label' => 'PAN Card', 'required' => true, 'verifiable_online' => $sandbox->canVerify('pan')],
            ['type' => self::DOC_ADDRESS, 'label' => 'Business Address Proof', 'required' => true, 'verifiable_online' => false],
            ['type' => self::DOC_BANK, 'label' => 'Bank Proof (cancelled cheque/passbook)', 'required' => true, 'verifiable_online' => $sandbox->canVerify('bank')],
            ['type' => self::DOC_BUSINESS, 'label' => 'Business Registration Proof', 'required' => true, 'verifiable_online' => false],
        ];
        if ($registrationType === VendorKycProfile::REG_GST) {
            array_splice($docs, 1, 0, [['type' => self::DOC_GST, 'label' => 'GST Certificate', 'required' => true, 'verifiable_online' => $sandbox->canVerify('gstin')]]);
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
            'online_verification' => [
                'enabled' => $online,
                'manual_fallback' => $sandbox->manualFallbackEnabled(),
                'providers' => ['sandbox'],
                'capabilities' => [
                    'pan' => $sandbox->canVerify('pan'),
                    'gstin' => $sandbox->canVerify('gstin'),
                    'bank' => $sandbox->canVerify('bank'),
                ],
            ],
            'notes' => [
                'Online API verification is primary for supported documents; manual upload is available as fallback.',
                'PAN and bank details must match legal entity where applicable.',
            ],
        ];
    }

    public function uploadDocument(VendorKycProfile $profile, string $documentType, UploadedFile $file): VendorKycDocument
    {
        return DB::transaction(function () use ($profile, $documentType, $file) {
            $existing = VendorKycDocument::query()
                ->where('vendor_kyc_profile_id', $profile->id)
                ->where('document_type', $documentType)
                ->first();

            $storedName = $this->upload(dir: self::DOC_DIR, format: $file->getClientOriginalExtension() ?: 'bin', image: $file);

            if ($existing) {
                $this->deleteFile(dir: self::DOC_DIR . $existing->storage . '/', file: $existing->file_path);
                $existing->update([
                    'file_path' => $storedName,
                    'storage' => config('filesystems.disks.default') ?? 'public',
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => VendorKycDocument::STATUS_PENDING,
                    'review_note' => null,
                    'verification_method' => 'manual',
                    'verification_provider' => null,
                    'verification_reference' => null,
                    'verified_at' => null,
                    'verification_meta' => null,
                ]);
                return $existing->fresh();
            }

            return VendorKycDocument::query()->create([
                'vendor_kyc_profile_id' => $profile->id,
                'document_type' => $documentType,
                'file_path' => $storedName,
                'storage' => config('filesystems.disks.default') ?? 'public',
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'status' => VendorKycDocument::STATUS_PENDING,
                'verification_method' => 'manual',
            ]);
        });
    }

    public function verifyPanOnline(VendorKycProfile $profile, array $input = []): array
    {
        $pan = $input['pan_number'] ?? $profile->pan_number;
        $name = $input['pan_holder_name'] ?? $profile->pan_holder_name ?? $profile->legal_name;
        $dob = $input['pan_dob'] ?? $profile->pan_dob;
        $consent = $input['consent'] ?? 'Y';
        $reason = $input['reason'] ?? 'Vendor KYC verification and onboarding compliance';
        if (empty($pan) || empty($name)) {
            return ['ok' => false, 'verification_status' => 'failed', 'message' => translate('PAN number and holder name are required for online verification')];
        }
        $result = $this->sandbox()->verifyPan((string)$pan, (string)$name, $dob ? (string)$dob : null, (string)$consent, (string)$reason);
        $status = $result['verification_status'] ?? ($result['ok'] ? 'verified' : 'failed');

        DB::transaction(function () use ($profile, $result, $status, $dob, $consent) {
            $updates = [
                'pan_number' => $profile->pan_number,
                'pan_dob' => $dob ?: $profile->pan_dob,
                'pan_verification_status' => $status,
                'pan_verified_at' => now(),
                'pan_verification_ref' => $result['reference_id'] ?? null,
                'pan_verification_meta' => $result['meta'] ?? $result['summary'] ?? null,
                'verification_mode' => $this->inferMode($profile, 'pan', $status),
                'online_consent_at' => in_array($consent, ['Y','y','1',1,true], true) ? ($profile->online_consent_at ?? now()) : $profile->online_consent_at,
            ];
            $profile->update($updates);
            // mark/link PAN document as online-verified when successful
            if ($status === 'verified') {
                $doc = $profile->documents()->where('document_type', self::DOC_PAN)->first();
                if ($doc) {
                    $doc->update([
                        'verification_method' => 'online',
                        'verification_provider' => 'sandbox',
                        'verification_reference' => $result['reference_id'] ?? null,
                        'verified_at' => now(),
                        'verification_meta' => $result['summary'] ?? null,
                        'status' => 'approved',
                    ]);
                }
            }
            $this->logReview($profile, $status === 'verified' ? 'online_pan_verified' : 'online_pan_failed', 'system', null, $profile->status, $profile->status, $result['message'] ?? null, ['provider'=>'sandbox','type'=>'pan','result'=>$status,'reference'=>$result['reference_id'] ?? null]);
        });

        return $result;
    }

    public function verifyGstinOnline(VendorKycProfile $profile, array $input = []): array
    {
        $gstin = $input['gst_number'] ?? $profile->gst_number;
        if (empty($gstin)) {
            return ['ok' => false, 'verification_status' => 'failed', 'message' => translate('GST number is required for online verification')];
        }
        $result = $this->sandbox()->verifyGstin((string)$gstin);
        $status = $result['verification_status'] ?? ($result['ok'] ? 'verified' : 'failed');

        DB::transaction(function () use ($profile, $result, $status) {
            $profile->update([
                'gst_verification_status' => $status,
                'gst_verified_at' => now(),
                'gst_verification_ref' => $result['reference_id'] ?? null,
                'gst_verification_meta' => $result['meta'] ?? $result['summary'] ?? null,
                'verification_mode' => $this->inferMode($profile, 'gstin', $status),
            ]);
            if ($status === 'verified') {
                $doc = $profile->documents()->where('document_type', self::DOC_GST)->first();
                if ($doc) {
                    $doc->update([
                        'verification_method' => 'online',
                        'verification_provider' => 'sandbox',
                        'verification_reference' => $result['reference_id'] ?? null,
                        'verified_at' => now(),
                        'verification_meta' => $result['summary'] ?? null,
                        'status' => 'approved',
                    ]);
                }
            }
            $this->logReview($profile, $status === 'verified' ? 'online_gstin_verified' : 'online_gstin_failed', 'system', null, $profile->status, $profile->status, $result['message'] ?? null, ['provider'=>'sandbox','type'=>'gstin','result'=>$status,'reference'=>$result['reference_id'] ?? null]);
        });

        return $result;
    }

    public function verifyBankOnline(VendorKycProfile $profile, array $input = []): array
    {
        $account = $input['bank_account_no'] ?? $profile->bank_account_no;
        $ifsc = $input['bank_ifsc'] ?? $profile->bank_ifsc;
        $name = $input['bank_account_name'] ?? $profile->bank_account_name;
        if (empty($account) || empty($ifsc)) {
            return ['ok' => false, 'verification_status' => 'failed', 'message' => translate('Bank account number and IFSC are required for online verification')];
        }
        $result = $this->sandbox()->verifyBank((string)$account, (string)$ifsc, $name ? (string)$name : null);
        $status = $result['verification_status'] ?? ($result['ok'] ? 'verified' : 'failed');

        DB::transaction(function () use ($profile, $result, $status) {
            $profile->update([
                'bank_verification_status' => $status,
                'bank_verified_at' => now(),
                'bank_verification_ref' => $result['reference_id'] ?? null,
                'bank_verification_meta' => $result['meta'] ?? $result['summary'] ?? null,
                'verification_mode' => $this->inferMode($profile, 'bank', $status),
            ]);
            if ($status === 'verified') {
                $doc = $profile->documents()->where('document_type', self::DOC_BANK)->first();
                if ($doc) {
                    $doc->update([
                        'verification_method' => 'online',
                        'verification_provider' => 'sandbox',
                        'verification_reference' => $result['reference_id'] ?? null,
                        'verified_at' => now(),
                        'verification_meta' => $result['summary'] ?? null,
                        'status' => 'approved',
                    ]);
                }
            }
            $this->logReview($profile, $status === 'verified' ? 'online_bank_verified' : 'online_bank_failed', 'system', null, $profile->status, $profile->status, $result['message'] ?? null, ['provider'=>'sandbox','type'=>'bank','result'=>$status,'reference'=>$result['reference_id'] ?? null]);
        });

        return $result;
    }

    public function verifyOnline(VendorKycProfile $profile, array $types = ['pan','gstin','bank'], array $input = []): array
    {
        $results = [];
        foreach ($types as $type) {
            $results[$type] = match ($type) {
                'pan' => $this->verifyPanOnline($profile->fresh(), $input),
                'gstin', 'gst' => $this->verifyGstinOnline($profile->fresh(), $input),
                'bank' => $this->verifyBankOnline($profile->fresh(), $input),
                default => ['ok' => false, 'verification_status' => 'failed', 'message' => 'Unsupported verification type'],
            };
            $profile = $profile->fresh();
        }
        return $results;
    }

    protected function inferMode(VendorKycProfile $profile, string $type, string $status): string
    {
        $current = $profile->verification_mode ?: 'manual';
        if ($status === 'verified') return $current === 'manual' ? 'hybrid' : ($current ?: 'online');
        return $current ?: 'manual';
    }

    public function statusPayload(VendorKycProfile $profile): array
    {
        $profile->loadMissing(['documents', 'reviews']);
        $latestReview = $profile->reviews()->latest('id')->first();
        return [
            'profile_status' => $profile->status,
            'verification_mode' => $profile->verification_mode,
            'is_verified' => $profile->status === VendorKycProfile::STATUS_APPROVED,
            'submitted_at' => $profile->submitted_at,
            'reviewed_at' => $profile->reviewed_at,
            'rejection_reason' => $profile->rejection_reason,
            'correction_note' => $profile->correction_note,
            'online_verification' => [
                'enabled' => $this->sandbox()->isEnabled(),
                'manual_fallback' => $this->sandbox()->manualFallbackEnabled(),
                'pan' => ['status' => $profile->pan_verification_status, 'verified_at' => $profile->pan_verified_at, 'reference' => $profile->pan_verification_ref],
                'gstin' => ['status' => $profile->gst_verification_status, 'verified_at' => $profile->gst_verified_at, 'reference' => $profile->gst_verification_ref],
                'bank' => ['status' => $profile->bank_verification_status, 'verified_at' => $profile->bank_verified_at, 'reference' => $profile->bank_verification_ref],
            ],
            'documents' => $profile->documents->map(fn ($d) => [
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
            return ['ok' => false, 'message' => translate('Please complete all required KYC details, online verification, or manual document uploads'), 'missing' => $missing];
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
            if ($field === 'pan_number' && $this->isPanSatisfied($profile)) continue;
            if (in_array($field, ['bank_account_name','bank_name','bank_account_no','bank_ifsc'], true) && $this->isBankSatisfied($profile)) {
                // still require core bank fields present
            }
            if (empty($profile->{$field})) $missing[] = $field;
        }
        if (($profile->registration_type) === VendorKycProfile::REG_GST && !$this->isGstSatisfied($profile)) {
            $missing[] = 'gst_number';
        }

        // documents: required unless satisfied by online verification where applicable
        $present = $profile->documents()->pluck('document_type')->all();
        foreach ($req['documents'] as $doc) {
            if (!($doc['required'] ?? false)) continue;
            $type = $doc['type'];
            if ($type === self::DOC_PAN && $this->isPanSatisfied($profile)) continue;
            if ($type === self::DOC_GST && $this->isGstSatisfied($profile)) continue;
            if ($type === self::DOC_BANK && $this->isBankSatisfied($profile)) continue;
            if (! in_array($type, $present, true)) $missing[] = 'document:'.$type;
        }
        return array_values(array_unique($missing));
    }

    protected function isPanSatisfied(VendorKycProfile $profile): bool
    {
        if (($profile->pan_verification_status ?? '') === 'verified') return true;
        // manual doc present is handled in document check
        return false;
    }

    protected function isGstSatisfied(VendorKycProfile $profile): bool
    {
        if (($profile->registration_type ?? '') !== VendorKycProfile::REG_GST) return true;
        if (!empty($profile->gst_number) && ($profile->gst_verification_status ?? '') === 'verified') return true;
        if (!empty($profile->gst_number) && $profile->documents()->where('document_type', self::DOC_GST)->exists()) return true;
        return !empty($profile->gst_number) ? false : false;
    }

    protected function isBankSatisfied(VendorKycProfile $profile): bool
    {
        if (($profile->bank_verification_status ?? '') === 'verified') return true;
        if ($profile->documents()->where('document_type', self::DOC_BANK)->exists() && !empty($profile->bank_account_no) && !empty($profile->bank_ifsc)) return true;
        return false;
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
        return $q->latest('submitted_at')->latest('id')->paginate($limit);
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
