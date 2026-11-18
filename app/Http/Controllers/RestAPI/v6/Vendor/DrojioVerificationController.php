<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\DrojioVerification\DocumentUploadRequest;
use App\Http\Requests\Vendor\DrojioVerification\ProfileUpdateRequest;
use App\Http\Resources\V6\Vendor\DrojioVerification\ProfileResource;
use App\Services\New\VendorKycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrojioVerificationController extends BaseApiController
{
    public function __construct(private readonly VendorKycService $vendorKycService) {}

    protected function seller(Request $request)
    {
        return $request->attributes->get('seller');
    }

    public function getProfile(Request $request): JsonResponse
    {
        $profile = $this->vendorKycService->getOrCreateForSeller($this->seller($request));

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new ProfileResource($profile)]
        );
    }

    public function updateProfile(ProfileUpdateRequest $request): JsonResponse
    {
        $seller = $this->seller($request);
        $profile = $this->vendorKycService->getOrCreateForSeller($seller);

        if (! $profile->isEditableByVendor() && $profile->status !== 'draft') {
            return $this->responseFormatter(
                config: config('response.forbidden_403'),
                errors: [['error_code' => 'kyc_not_editable', 'message' => translate('KYC profile cannot be edited in current status')]]
            );
        }

        $profile = $this->vendorKycService->updateProfile($profile, $request->validated());

        return $this->responseFormatter(
            config: config('response.information_updated'),
            content: ['data' => new ProfileResource($profile)]
        );
    }

    public function uploadDocument(DocumentUploadRequest $request): JsonResponse
    {
        $seller = $this->seller($request);
        $profile = $this->vendorKycService->getOrCreateForSeller($seller);

        $doc = $this->vendorKycService->uploadDocument(
            $profile,
            (string) $request->input('document_type'),
            $request->file('document')
        );

        return $this->responseFormatter(
            config: config('response.information_added') ?? config('response.data_fetched'),
            content: ['data' => [
                'id' => $doc->id,
                'document_type' => $doc->document_type,
                'status' => $doc->status,
                'file_url' => $doc->file_full_url,
                'original_name' => $doc->original_name,
            ]]
        );
    }

    public function getStatus(Request $request): JsonResponse
    {
        $profile = $this->vendorKycService->getOrCreateForSeller($this->seller($request));

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => $this->vendorKycService->statusPayload($profile)]
        );
    }

    public function resubmit(Request $request): JsonResponse
    {
        $seller = $this->seller($request);
        $profile = $this->vendorKycService->getOrCreateForSeller($seller);
        $result = $this->vendorKycService->resubmit($profile, (int) $seller->id);

        if (! ($result['ok'] ?? false)) {
            return $this->responseFormatter(
                config: config('response.unprocessable_entity_422'),
                content: ['data' => $result],
                errors: [['error_code' => 'kyc_resubmit_failed', 'message' => $result['message'] ?? translate('Unable to resubmit KYC')]]
            );
        }

        $profile = $profile->fresh(['documents']);
        return $this->responseFormatter(
            config: config('response.information_updated'),
            content: ['data' => $this->vendorKycService->statusPayload($profile), 'message' => $result['message']]
        );
    }

    public function getRequirements(Request $request): JsonResponse
    {
        $profile = $this->vendorKycService->getOrCreateForSeller($this->seller($request));
        $requirements = $this->vendorKycService->requirements(
            $profile,
            $request->query('business_type'),
            $request->query('registration_type')
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => $requirements]
        );
    }

    public function submit(Request $request): JsonResponse
    {
        $seller = $this->seller($request);
        $profile = $this->vendorKycService->getOrCreateForSeller($seller);
        $result = $this->vendorKycService->submit($profile, 'vendor', (int) $seller->id, false);

        if (! ($result['ok'] ?? false)) {
            return $this->responseFormatter(
                config: config('response.unprocessable_entity_422'),
                content: ['data' => $result],
                errors: [['error_code' => 'kyc_submit_failed', 'message' => $result['message'] ?? translate('Unable to submit KYC')]]
            );
        }

        $profile = $profile->fresh(['documents']);
        return $this->responseFormatter(
            config: config('response.information_updated'),
            content: ['data' => $this->vendorKycService->statusPayload($profile), 'message' => $result['message']]
        );
    }

    public function verifyOnline(Request $request): JsonResponse
    {
        $seller = $this->seller($request);
        $profile = $this->vendorKycService->getOrCreateForSeller($seller);

        if (! $profile->isEditableByVendor() && $profile->status !== 'draft') {
            return $this->responseFormatter(
                config: config('response.forbidden_403'),
                errors: [['error_code' => 'kyc_not_editable', 'message' => translate('KYC profile cannot be edited in current status')]]
            );
        }

        // Optionally update profile fields provided for verification
        $updatable = array_filter($request->only([
            'pan_number','pan_holder_name','pan_dob','gst_number','bank_account_name','bank_name','bank_account_no','bank_ifsc','verification_mode','registration_type'
        ]), fn($v) => $v !== null);
        if (!empty($updatable)) {
            $profile = $this->vendorKycService->updateProfile($profile, $updatable);
        }

        $types = $request->input('types');
        if (is_string($types)) $types = array_filter(array_map('trim', explode(',', $types)));
        if (!is_array($types) || empty($types)) $types = ['pan','gstin','bank'];

        $results = $this->vendorKycService->verifyOnline($profile, $types, $request->all());
        $profile = $profile->fresh(['documents']);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => [
                'results' => $results,
                'profile' => new ProfileResource($profile),
                'status' => $this->vendorKycService->statusPayload($profile),
            ]]
        );
    }
}
