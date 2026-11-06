<?php

namespace App\Traits;

use App\Services\New\AddonService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait AddonListResponseTrait
{
    private function respond(Request $request, bool $succeeded, string $message): JsonResponse|RedirectResponse
    {
        if ($request->ajax()) {
            return response()->json([
                'status' => $succeeded ? 'success' : 'error',
                'message' => $message,
                ...($succeeded ? $this->listPayload() : []),
            ]);
        }

        $succeeded ? ToastMagic::success($message) : ToastMagic::error($message);

        return back();
    }

    private function listPayload(): array
    {
        $addonInfos = $this->addonService->getList();
        $statusCounts = $this->addonService->getStatusCounts();

        return [
            'view' => view('admin-views.system-setup.addons.partials._addon-list-wrapper', compact('addonInfos'))->render(),
            'counts' => ['total' => count($addonInfos)] + $statusCounts,
        ];
    }

    private function failureMessage(array $result): string
    {
        if ($result['reason'] === AddonService::REASON_INCOMPATIBLE_VERSION) {
            return translate('This version is not supported. Supported versions: :versions', ['versions' => $result['detail']]);
        }

        $message = match ($result['reason']) {
            AddonService::REASON_ADDON_NOT_FOUND => translate('Invalid addon'),
            AddonService::REASON_UPDATE_PENDING => translate('Finish the addon update before activating it'),
            AddonService::REASON_ARCHIVE_UNREADABLE => translate('File upload failed'),
            AddonService::REASON_INVALID_ARCHIVE => translate('Invalid file!'),
            AddonService::REASON_ALREADY_INSTALLED => translate('Already installed'),
            AddonService::REASON_MODULES_DIRECTORY_NOT_WRITABLE => translate('Addon extraction failed modules directory is not writable'),
            AddonService::REASON_BUNDLE_EXTRACTION_FAILED => translate('Builder activation failed during bundle extraction'),
            default => '',
        };

        $parts = array_filter([$message, $result['detail']]);

        return $parts === [] ? translate('Something went wrong') : implode(' — ', $parts);
    }
}
