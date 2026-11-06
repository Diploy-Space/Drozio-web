<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\System\AddonInstallationCompleteRequest;
use App\Http\Requests\Admin\Setting\System\AddonUploadRequest;
use App\Services\New\AddonService;
use App\Traits\AddonListResponseTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class AddonController extends BaseController
{
    use AddonListResponseTrait;

    public function __construct(
        private readonly AddonService $addonService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $addonInfos = $this->addonService->getList();
        $statusCounts = $this->addonService->getStatusCounts();

        return view('admin-views.system-setup.addons.index', compact('addonInfos', 'statusCounts'));
    }

    public function publish(Request $request): JsonResponse|RedirectResponse
    {
        $addon = $this->addonService->find(id: $request['path']);

        if ($addon === null) {
            return $this->respond(request: $request, succeeded: false, message: translate('Invalid addon'));
        }

        if ($addon['purchase_code'] == null || $addon['username'] == null) {
            return $this->respondWithActivationModal(request: $request, path: $request['path'], addon: $addon);
        }

        $result = $this->addonService->updateStatus(id: $request['path'], status: ! $addon['is_published']);

        return $this->respond(
            request: $request,
            succeeded: $result['status'],
            message: $result['status']
                ? translate(':item updated successfully', ['item' => translate('Status')])
                : $this->failureMessage($result),
        );
    }

    public function activation(Request $request): Redirector|RedirectResponse|Application
    {
        $result = $this->addonService->activateByDomain(
            path: $request['path'],
            username: $request['username'],
            purchaseCode: $request['purchase_code'],
        );

        if ($result['status']) {
            ToastMagic::success(translate('Activated successfully'));

            return back();
        }

        return redirect($result['activationUrl']);
    }

    public function upload(AddonUploadRequest $request): JsonResponse|RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            return $this->respond(
                request: $request,
                succeeded: false,
                message: translate('Uploading ZIP files is currently unavailable in demo mode'),
            );
        }

        $result = $this->addonService->create(archive: $request->file('file_upload'));

        return $this->respond(
            request: $request,
            succeeded: $result['status'],
            message: $result['status'] ? translate('Uploaded successfully') : $this->failureMessage($result),
        );
    }

    public function completeInstallation(AddonInstallationCompleteRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->addonService->completeInstallation(id: $request['path']);

        return $this->respond(
            request: $request,
            succeeded: $result['status'],
            message: $result['status'] ? translate('Addon update finished successfully') : $this->failureMessage($result),
        );
    }

    public function delete(Request $request): JsonResponse|RedirectResponse
    {
        $isDeleted = $this->addonService->delete(id: $request['path']);

        return $this->respond(
            request: $request,
            succeeded: $isDeleted,
            message: $isDeleted ? translate('File deleted successfully') : translate('File delete failed'),
        );
    }

    private function respondWithActivationModal(Request $request, string $path, array $addon): JsonResponse|RedirectResponse
    {
        if (! $request->ajax()) {
            ToastMagic::error(translate('Activate this addon with your purchase key first'));

            return back();
        }

        return response()->json([
            'flag' => 'inactive',
            'view' => view('admin-views.system-setup.addons.partials.addon-activation-modal', [
                'fullData' => $addon,
                'path' => $path,
                'addonName' => $addon['name'],
            ])->render(),
        ]);
    }
}
