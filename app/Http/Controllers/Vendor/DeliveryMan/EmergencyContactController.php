<?php

namespace App\Http\Controllers\Vendor\DeliveryMan;

use App\Enums\WebConfigKey;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\EmergencyContact\AddRequest;
use App\Http\Requests\Vendor\EmergencyContact\UpdateRequest;
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
            filters: ['user_id' => $this->sellerId()],
        )->appends($request->all());

        return view('vendor-views.delivery-man.emergency-contact.index', [
            'contacts' => $contacts,
            'overview' => $this->getContactOverview(),
        ]);
    }

    public function add(AddRequest $request): RedirectResponse
    {
        $this->emergencyContactService->create(data: $this->contactData(data: $request->validated()));
        ToastMagic::success(translate('Emergency contact added successfully').'!');

        return redirect()->back();
    }

    public function getUpdateView($id): JsonResponse
    {
        $emergencyContact = $this->emergencyContactService->findBy(params: ['user_id' => $this->sellerId(), 'id' => $id]);

        return response()->json(['view' => view('vendor-views.delivery-man.emergency-contact._update-emergency-contact', compact('emergencyContact'))->render()]);
    }

    public function update(UpdateRequest $request, $id): RedirectResponse
    {
        $contact = $this->emergencyContactService->findBy(params: ['user_id' => $this->sellerId(), 'id' => $id]);

        if ($contact === null) {
            ToastMagic::error(translate('Emergency contact not found').'!');

            return back();
        }

        $this->emergencyContactService->update(id: $contact->id, data: $request->validated());
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Emergency Contact')]));

        return back();
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $contact = $this->emergencyContactService->findBy(params: ['user_id' => $this->sellerId(), 'id' => $request['id']]);

        if ($contact === null) {
            return response()->json([
                'message' => translate('Contact status change failed').'!',
                'fail' => 1,
            ]);
        }

        $this->emergencyContactService->updateStatus(id: $contact->id, status: (bool) ($request['status'] ?? 0));

        return response()->json([
            'message' => translate(':item changed successfully', ['item' => translate('Contact status')]).'!',
        ]);
    }

    public function delete(Request $request): RedirectResponse
    {
        $contact = $this->emergencyContactService->findBy(params: ['user_id' => $this->sellerId(), 'id' => $request['id']]);

        $contact !== null && $this->emergencyContactService->delete(id: $contact->id)
            ? ToastMagic::success(translate('Emergency contact deleted successfully').'!')
            : ToastMagic::error(translate('Emergency contact delete failed').'!');

        return redirect()->back();
    }

    /** @return array<string, int> */
    private function getContactOverview(): array
    {
        $total = $this->emergencyContactService->count(filters: ['user_id' => $this->sellerId()]);
        $active = $this->emergencyContactService->count(filters: [
            'user_id' => $this->sellerId(),
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
        return $data + ['user_id' => $this->sellerId(), 'status' => self::STATUS_ACTIVE];
    }

    private function sellerId(): ?int
    {
        return auth('seller')->id();
    }
}
