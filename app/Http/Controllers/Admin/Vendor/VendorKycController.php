<?php

namespace App\Http\Controllers\Admin\Vendor;

use App\Http\Controllers\BaseController;
use App\Models\VendorKycProfile;
use App\Services\New\VendorKycService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class VendorKycController extends BaseController
{
    public function __construct(private readonly VendorKycService $vendorKycService) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse|JsonResponse
    {
        $status = $request?->query('status');
        $search = $request?->query('searchValue');
        $profiles = $this->vendorKycService->adminList($status ?: null, $search ?: null, (int) (getWebConfig(name: 'pagination_limit') ?: 25));

        return view('admin-views.vendor.kyc.index', [
            'profiles' => $profiles,
            'status' => $status,
            'searchValue' => $search,
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $profile = VendorKycProfile::query()->with(['seller', 'shop', 'documents', 'reviews'])->findOrFail($id);
        return view('admin-views.vendor.kyc.show', [
            'profile' => $profile,
            'requirements' => $this->vendorKycService->requirements($profile),
        ]);
    }

    public function review(Request $request, int $id): RedirectResponse
    {
        $profile = VendorKycProfile::query()->findOrFail($id);
        $action = (string) $request->input('action');
        $note = $request->input('note');
        $result = $this->vendorKycService->review($profile, $action, $note ? (string) $note : null, (int) auth('admin')->id());

        if ($result['ok'] ?? false) {
            ToastMagic::success($result['message']);
        } else {
            ToastMagic::error($result['message'] ?? translate('Something went wrong'));
        }

        return back();
    }
}
