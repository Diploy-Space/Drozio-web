<?php

namespace App\Http\Controllers\Admin\Vendor;

use App\Enums\WebConfigKey;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Vendor\Verification\GrantRequest;
use App\Http\Requests\Admin\Vendor\Verification\RevokeRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\VendorService;
use App\Utils\VendorVerificationCriteria;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class VerificationController extends BaseController
{
    private const DEMO_MODE = 'demo';

    public function __construct(
        private readonly VendorService $vendorService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return $this->getCriteriaView();
    }

    public function getCriteriaView(): View
    {
        $this->abortWhenDisabled();

        return view('admin-views.vendor.partials.offcanvas._verification-criteria', [
            'criteria' => VendorVerificationCriteria::displayRows(),
        ]);
    }

    public function getCandidateListView(Request $request): View|JsonResponse
    {
        $this->abortWhenDisabled();

        $page = $request['page'] === null ? null : (int) $request['page'];

        $candidates = $this->vendorService->getVerificationCandidates(
            searchValue: $request['searchValue'],
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            page: $page,
            relations: ['shop'],
        );

        $rows = $this->candidateRows(candidates: $candidates);

        if ($page !== null) {
            return response()->json([
                'html' => view('admin-views.vendor.partials.offcanvas._verification-candidate-rows', [
                    'candidates' => $rows,
                ])->render(),
                'has_more' => $candidates->hasMorePages(),
            ]);
        }

        return view('admin-views.vendor.partials.offcanvas._verification-candidates', [
            'candidates' => $rows,
            'candidateCount' => $candidates->total(),
            'hasMore' => $candidates->hasMorePages(),
        ]);
    }

    public function grant(GrantRequest $request): JsonResponse
    {
        $this->abortWhenDisabled();

        if (config('app.mode') === self::DEMO_MODE) {
            return response()->json([
                'error' => 1,
                'message' => translate('Update option is disabled for demo'),
            ]);
        }

        $grantedCount = $this->vendorService->grantVerifiedBadge(
            ids: $request['vendor_ids'],
            adminId: auth('admin')->id(),
        );

        return response()->json([
            'error' => 0,
            'message' => $grantedCount > 0
                ? translate('Verified badge given successfully')
                : translate('No vendor was updated'),
            'granted_count' => $grantedCount,
            'candidate_count' => $this->vendorService->countVerificationCandidates(),
        ]);
    }

    public function revoke(RevokeRequest $request): JsonResponse
    {
        $this->abortWhenDisabled();

        if (config('app.mode') === self::DEMO_MODE) {
            return response()->json([
                'error' => 1,
                'message' => translate('Update option is disabled for demo'),
            ]);
        }

        $isRevoked = $this->vendorService->revokeVerifiedBadge(id: $request['vendor_id']);

        return response()->json([
            'error' => $isRevoked ? 0 : 1,
            'message' => $isRevoked
                ? translate('Verified badge removed successfully')
                : translate('Vendor not found It may be deleted'),
            'candidate_count' => $this->vendorService->countVerificationCandidates(),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function candidateRows(LengthAwarePaginator $candidates): array
    {
        return $candidates->getCollection()
            ->map(fn (Model $vendor): array => [
                'id' => $vendor->id,
                'name' => $vendor->shop?->name ?? translate('Not found'),
                'image' => $vendor->shop?->image_full_url,
                'rating' => number_format((float) $vendor->average_rating, 1),
                'bookings' => (int) $vendor->completed_orders_count,
            ])
            ->all();
    }

    private function abortWhenDisabled(): void
    {
        abort_if(! $this->isEnabled(), 404);
    }

    private function isEnabled(): bool
    {
        return (bool) $this->businessSettingService->getValueByType(
            type: WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS,
            default: 0,
        );
    }
}
