<?php

namespace App\Http\Controllers\Admin\Deliveryman;

use App\Enums\WebConfigKey;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\EmergencyContact\AddRequest;
use App\Http\Requests\Admin\EmergencyContact\UpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\EmergencyContactService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmergencyContactController extends BaseController
{
    private const STATUS_ACTIVE = 1;

    public function __construct(
        private readonly EmergencyContactService $emergencyContactService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $contacts = $this->emergencyContactService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->businessSettingService->getValueByType(type: WebConfigKey::PAGINATION_LIMIT) ?? DEFAULT_DATA_LIMIT,
            filters: ['user_id' => EmergencyContactService::ADMIN_USER_ID],
        )->appends($request->all());

        return view('admin-views.delivery-man.emergency-contact', [
            'contacts' => $contacts,
            'overview' => $this->getContactOverview(),
        ]);
    }

    public function add(AddRequest $request): RedirectResponse
    {
        $this->emergencyContactService->create(data: $this->contactData(data: $request->validated()));
        ToastMagic::success(translate('Emergency contact added successfully'));

        return back();
    }

    public function getUpdateView($id): JsonResponse
    {
        $emergencyContact = $this->emergencyContactService->findBy(params: ['user_id' => EmergencyContactService::ADMIN_USER_ID, 'id' => $id]);

        return response()->json(['view' => view('admin-views.delivery-man.partials._update-emergency-contact', compact('emergencyContact'))->render()]);
    }

    public function update(UpdateRequest $request, $id): RedirectResponse
    {
        $contact = $this->emergencyContactService->findBy(params: ['user_id' => EmergencyContactService::ADMIN_USER_ID, 'id' => $id]);

        if ($contact === null) {
            ToastMagic::error(translate('Emergency contact not found'));

            return back();
        }

        $this->emergencyContactService->update(id: $contact->id, data: $request->validated());
        ToastMagic::success(translate('Emergency contact updated successfully'));

        return back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $contact = $this->emergencyContactService->findBy(params: ['user_id' => EmergencyContactService::ADMIN_USER_ID, 'id' => $request['id']]);

        $contact !== null && $this->emergencyContactService->delete(id: $contact->id)
            ? ToastMagic::success(translate('Emergency contact deleted successfully'))
            : ToastMagic::error(translate('Emergency contact not found'));

        return back();
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->emergencyContactService->updateWhere(
            params: ['user_id' => EmergencyContactService::ADMIN_USER_ID, 'id' => $request['id']],
            data: ['status' => (int) ($request['status'] ?? 0)],
        );
        $message = translate(':item changed successfully', ['item' => translate('Contact status')]);
        ToastMagic::success($message);

        return response()->json(['message' => $message]);
    }

    /** @return array<string, int> */
    private function getContactOverview(): array
    {
        $total = $this->emergencyContactService->count(filters: ['user_id' => EmergencyContactService::ADMIN_USER_ID]);
        $active = $this->emergencyContactService->count(filters: [
            'user_id' => EmergencyContactService::ADMIN_USER_ID,
            'status' => self::STATUS_ACTIVE,
        ]);

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
        ];
    }

    private function contactData(array $data): array
    {
        return $data + ['user_id' => EmergencyContactService::ADMIN_USER_ID, 'status' => self::STATUS_ACTIVE];
    }
}
