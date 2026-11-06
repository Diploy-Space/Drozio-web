<?php

namespace App\Http\Controllers\Admin\HelpAndSupport;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\HelpAndSupport\HelpTopicAddRequest;
use App\Http\Requests\Admin\HelpAndSupport\HelpTopicUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\HelpTopicService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HelpTopicController extends BaseController
{
    private const LIST_DATA_LIMIT = 10;

    private const MAX_RANKING = 15;

    private const VENDOR_REGISTRATION_FAQ_STATUS = 'vendor_registration_faq_status';

    public function __construct(
        private readonly HelpTopicService $helpTopicService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $helps = $this->helpTopicService->getList(
            searchValue: $request['searchValue'],
            dataLimit: self::LIST_DATA_LIMIT,
            filters: ['type' => HelpTopicService::TYPE_DEFAULT],
        );

        return view('admin-views.pages-and-media.help-topics.list', [
            'helps' => $helps,
            'rankings' => range(1, self::MAX_RANKING),
        ]);
    }

    public function add(HelpTopicAddRequest $request): RedirectResponse
    {
        $this->helpTopicService->create(data: $request->all());
        ToastMagic::success(translate('FAQ added successfully'));

        return back();
    }

    public function updateFeatureStatus(Request $request): JsonResponse
    {
        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::VENDOR_REGISTRATION_FAQ_STATUS],
            data: ['value' => $request['status'] ?? 0],
        );

        return response()->json([
            'status' => true,
            'message' => translate(':item updated successfully', ['item' => translate('Status')]),
        ]);
    }

    public function updateStatus(Request $request, string|int $id): JsonResponse
    {
        $this->helpTopicService->updateStatus(id: $id, status: (bool) $request['status']);

        return response()->json([
            'status' => true,
            'message' => translate(':item updated successfully', ['item' => translate('Status')]),
        ]);
    }

    public function getUpdateResponse(string|int $id): JsonResponse
    {
        return response()->json($this->helpTopicService->find(id: $id));
    }

    public function update(HelpTopicUpdateRequest $request, string|int $id): RedirectResponse
    {
        $this->helpTopicService->update(id: $id, data: $request->all());
        ToastMagic::success(translate('FAQ updated successfully'));

        return back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->helpTopicService->delete(id: $request['id'])
            ? ToastMagic::success(translate('FAQ deleted successfully'))
            : ToastMagic::error(translate('FAQ not found'));

        return back();
    }
}
