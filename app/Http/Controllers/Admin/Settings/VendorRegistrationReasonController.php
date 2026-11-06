<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Vendor\Registration\ReasonAddRequest;
use App\Http\Requests\Admin\Vendor\Registration\ReasonUpdateRequest;
use App\Services\New\VendorRegistrationReasonService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorRegistrationReasonController extends BaseController
{
    public function __construct(
        private readonly VendorRegistrationReasonService $vendorRegistrationReasonService,
    ) {}

    public function index(?Request $request, ?string $type = null): ?RedirectResponse
    {
        return null;
    }

    public function add(ReasonAddRequest $request): RedirectResponse
    {
        $this->vendorRegistrationReasonService->create(data: $this->reasonFromRequest(request: $request));
        ToastMagic::success(translate('Vendor registration reason added successfully'));

        return redirect()->back();
    }

    public function getUpdateView(Request $request): JsonResponse
    {
        $vendorRegistrationReason = $this->vendorRegistrationReasonService->find(id: $request['id']);

        return response()->json([
            'view' => view(
                'admin-views.business-settings.vendor-registration-setting.partial.update-reason-modal',
                compact('vendorRegistrationReason'),
            )->render(),
        ]);
    }

    public function update(ReasonUpdateRequest $request): RedirectResponse
    {
        $this->vendorRegistrationReasonService->update(id: $request['id'], data: $this->reasonFromRequest(request: $request));
        ToastMagic::success(translate('Vendor registration reason updated successfully'));

        return redirect()->back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->vendorRegistrationReasonService->delete(id: $request['id'])
            ? ToastMagic::success(translate('Vendor registration reason deleted successfully'))
            : ToastMagic::error(translate('Not found'));

        return redirect()->back();
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $reason = $this->vendorRegistrationReasonService->find(id: $request['id']);

        if ($reason === null) {
            return response()->json(['message' => translate('Not found')]);
        }

        $this->vendorRegistrationReasonService->updateStatus(id: $reason->id, status: ! $reason->status);

        return response()->json(['message' => translate('Vendor registration reason status changed successfully')]);
    }

    /** @return array<string, mixed> */
    private function reasonFromRequest(ReasonAddRequest|ReasonUpdateRequest $request): array
    {
        return [
            'title' => $request['title'],
            'description' => $request['description'],
            'priority' => $request['priority'],
            'status' => $request->get('status', 0),
        ];
    }
}
