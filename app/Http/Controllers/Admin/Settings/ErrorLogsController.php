<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\WebConfigKey;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Website\ErrorLogUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\ErrorLogService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ErrorLogsController extends BaseController
{
    private const DEFAULT_REDIRECT_STATUS = '301';

    public function __construct(
        private readonly ErrorLogService $errorLogService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.seo-settings.error-logs', [
            'errorLogs' => $this->errorLogService->getList(
                dataLimit: $this->paginationLimit(),
                sortBy: ErrorLogService::SORT_NEWEST_ID,
            ),
        ]);
    }

    public function update(ErrorLogUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('You cannot update this on demo mode'));

            return redirect()->back();
        }

        $this->errorLogService->update(id: $request['id'], data: [
            'redirect_url' => $request['redirect_url'],
            'redirect_status' => $request->get('redirect_status', self::DEFAULT_REDIRECT_STATUS),
        ]);
        ToastMagic::success(translate('Updated successfully'));

        return redirect()->back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->errorLogService->delete(id: $request['id']);
        ToastMagic::success(translate('Deleted successfully'));

        return redirect()->back();
    }

    public function deleteSelectedErrorLogs(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('You cannot update this on demo mode'));

            return redirect()->back();
        }

        $selectedIds = $this->selectedLogIds(data: $request->all());

        if ($selectedIds === []) {
            ToastMagic::warning(translate('Please select logs for delete'));

            return redirect()->back();
        }

        foreach ($selectedIds as $id) {
            $this->errorLogService->delete(id: $id);
        }
        ToastMagic::success(translate('Selected error logs deleted successfully'));

        return redirect()->back();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, mixed>
     */
    private function selectedLogIds(array $data): array
    {
        return array_values(array_filter((array) ($data['selected-ids'] ?? []), static fn ($id) => $id !== null && $id !== ''));
    }

    private function paginationLimit(): int|string
    {
        return $this->businessSettingService->getValueByType(type: WebConfigKey::PAGINATION_LIMIT) ?? DEFAULT_DATA_LIMIT;
    }
}
