<?php

namespace App\Http\Controllers\Admin\Payment;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\ThirdParty\OfflinePaymentAddRequest;
use App\Http\Requests\Admin\Setting\ThirdParty\OfflinePaymentUpdateRequest;
use App\Services\New\OfflinePaymentMethodService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OfflinePaymentMethodController extends BaseController
{
    private const LIST_ROUTE = 'admin.settings.third-party.offline-payment-method.index';

    private const LIST_LIMIT = 10;

    private const SETUP_GUIDE_KEY = 'offline_payment_setup';

    private const ADMIN_PANEL = 'admin';

    private const STATUS_FILTER_ACTIVE = 'active';

    private const STATUS_FILTER_INACTIVE = 'inactive';

    public function __construct(
        private readonly OfflinePaymentMethodService $offlinePaymentMethodService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.third-party.offline-payment-method.index', [
            'methods' => $this->offlinePaymentMethodService->getList(
                searchValue: $request['searchValue'],
                dataLimit: self::LIST_LIMIT,
                filters: $this->statusFilter(status: $request['status']),
                sortBy: OfflinePaymentMethodService::SORT_NEWEST_ID,
            ),
        ]);
    }

    public function getAddView(): View
    {
        return view('admin-views.third-party.offline-payment-method.add-view');
    }

    public function add(OfflinePaymentAddRequest $request): JsonResponse
    {
        $methodInformation = $this->methodInformation(request: $request);

        if ($methodInformation === null) {
            return $this->duplicateInformationFieldResponse();
        }

        $this->offlinePaymentMethodService->create(data: [
            'method_name' => $request['method_name'],
            'method_fields' => $this->methodFields(request: $request),
            'method_informations' => $methodInformation,
        ]);

        updateSetupGuideCacheKey(key: self::SETUP_GUIDE_KEY, panel: self::ADMIN_PANEL);

        return $this->savedResponse(message: translate('Offline payment method added successfully'));
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        $method = $this->offlinePaymentMethodService->find(id: $id);

        if ($method === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route(self::LIST_ROUTE);
        }

        return view('admin-views.third-party.offline-payment-method.update-view', compact('method'));
    }

    public function update(OfflinePaymentUpdateRequest $request, string|int $id): JsonResponse
    {
        $methodInformation = $this->methodInformation(request: $request);

        if ($methodInformation === null) {
            return $this->duplicateInformationFieldResponse();
        }

        $this->offlinePaymentMethodService->update(id: $id, data: [
            'method_name' => $request['method_name'],
            'method_fields' => $this->methodFields(request: $request),
            'method_informations' => $methodInformation,
        ]);

        updateSetupGuideCacheKey(key: self::SETUP_GUIDE_KEY, panel: self::ADMIN_PANEL);

        return $this->savedResponse(message: translate('Offline payment method updated successfully'));
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $method = $this->offlinePaymentMethodService->find(id: $request['id']);

        if ($method === null) {
            return response()->json([
                'success_status' => 0,
                'message' => translate('Status update failed'),
            ]);
        }

        $this->offlinePaymentMethodService->updateStatus(id: $method->id, status: $method->status != 1);
        updateSetupGuideCacheKey(key: self::SETUP_GUIDE_KEY, panel: self::ADMIN_PANEL);

        return response()->json([
            'success_status' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Status')]),
        ]);
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->offlinePaymentMethodService->delete(id: $request['id'])
            ? ToastMagic::success(translate('Offline payment method deleted successfully'))
            : ToastMagic::error(translate('Not found'));

        return redirect()->route(self::LIST_ROUTE);
    }

    /** @return array<string, mixed> */
    private function statusFilter(mixed $status): array
    {
        return match ($status) {
            self::STATUS_FILTER_ACTIVE => ['status' => OfflinePaymentMethodService::STATUS_ACTIVE],
            self::STATUS_FILTER_INACTIVE => ['status' => OfflinePaymentMethodService::STATUS_INACTIVE],
            default => [],
        };
    }

    /** @return array<int, array{input_name: string, input_data: mixed}> */
    private function methodFields(OfflinePaymentAddRequest|OfflinePaymentUpdateRequest $request): array
    {
        $methodFields = [];

        foreach ($request['input_name'] ?? [] as $key => $inputName) {
            $methodFields[] = [
                'input_name' => $this->normalizedFieldName(name: $inputName),
                'input_data' => $request['input_data'][$key],
            ];
        }

        return $methodFields;
    }

    /** @return array<int, array{customer_input: string, customer_placeholder: mixed, is_required: int}>|null */
    private function methodInformation(OfflinePaymentAddRequest|OfflinePaymentUpdateRequest $request): ?array
    {
        $methodInformation = [];

        foreach ($request['customer_input'] ?? [] as $key => $customerInput) {
            $inputName = $this->normalizedFieldName(name: $customerInput);

            if (in_array($inputName, array_column($methodInformation, 'customer_input'), true)) {
                return null;
            }

            $methodInformation[] = [
                'customer_input' => $inputName,
                'customer_placeholder' => $request['customer_placeholder'][$key],
                'is_required' => isset($request['is_required'][$key]) ? 1 : 0,
            ];
        }

        return $methodInformation;
    }

    private function normalizedFieldName(string $name): string
    {
        return strtolower(str_replace("'", '', preg_replace('/[^a-zA-Z0-9\']/', '_', $name)));
    }

    private function duplicateInformationFieldResponse(): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'message' => translate('Information Input Field Name must be unique'),
            'redirect_url' => '',
        ]);
    }

    private function savedResponse(string $message): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'message' => $message,
            'redirect_url' => route(self::LIST_ROUTE),
        ]);
    }
}
