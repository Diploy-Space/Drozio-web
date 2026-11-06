<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\PaymentInfo\AddRequest;
use App\Http\Requests\Vendor\PaymentInfo\UpdateRequest;
use App\Services\New\VendorWithdrawMethodInfoService;
use App\Services\New\WithdrawalMethodService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorPaymentInfoController extends Controller
{
    private const LIST_DATA_LIMIT = 10;

    public function __construct(
        private readonly VendorWithdrawMethodInfoService $vendorWithdrawMethodInfoService,
        private readonly WithdrawalMethodService $withdrawalMethodService,
    ) {}

    public function index(Request $request): View
    {
        return view('vendor-views.shop.payment-information', [
            'withdrawalMethods' => $this->withdrawalMethodService->getList(
                dataLimit: self::LIST_DATA_LIMIT,
                filters: [
                    'is_active' => WithdrawalMethodService::STATUS_ACTIVE,
                    WithdrawalMethodService::FILTER_USER_TYPE => WithdrawalMethodService::USER_TYPE_VENDOR,
                ],
            ),
            'vendorWithdrawMethods' => $this->vendorWithdrawMethodInfoService->getList(
                searchValue: $request['search'],
                dataLimit: self::LIST_DATA_LIMIT,
                relations: ['withdraw_method'],
                filters: ['user_id' => auth('seller')->id()],
                sortBy: VendorWithdrawMethodInfoService::SORT_LATEST,
            )->appends($request->all()),
        ]);
    }

    public function add(AddRequest $request): JsonResponse
    {
        $this->vendorWithdrawMethodInfoService->create(
            data: $request->all(),
            methodFields: $this->methodFields(id: $request['withdraw_method_id']),
            userId: auth('seller')->id(),
        );
        updateSetupGuideCacheKey(key: 'payment_information', panel: 'vendor');

        return response()->json([
            'status' => true,
            'message' => translate(':item added successfully', ['item' => translate('Payment Information')]),
        ]);
    }

    public function update(UpdateRequest $request): JsonResponse
    {
        $updated = $this->vendorWithdrawMethodInfoService->update(
            id: $request['id'],
            data: $request->all(),
            methodFields: $this->methodFields(id: $request['withdraw_method_id']),
            userId: auth('seller')->id(),
        );

        if ($updated === null) {
            return response()->json([
                'status' => false,
                'message' => translate('Payment method not found'),
            ]);
        }

        updateSetupGuideCacheKey(key: 'payment_information', panel: 'vendor');

        return response()->json([
            'status' => true,
            'message' => translate(':item updated successfully', ['item' => translate('Payment Information')]),
        ]);
    }

    public function getDynamicPaymentInformationView(Request $request): JsonResponse
    {
        $method = $this->withdrawalMethodService->find(id: $request['id']);
        $data = $method?->is_active == WithdrawalMethodService::STATUS_ACTIVE ? (array) $method->method_fields : null;

        return response()->json([
            'status' => $data !== null,
            'message' => $data !== null ? translate('Withdrawal method found') : translate('Not found'),
            'htmlView' => view('vendor-views.shop.dynamic-payment-information', ['data' => $data ?? []])->render(),
        ], 200);
    }

    /** @return array<int, array<string, mixed>> */
    private function methodFields(mixed $id): array
    {
        return (array) ($this->withdrawalMethodService->find(id: $id)?->method_fields ?? []);
    }

    public function getUpdateView($id): JsonResponse
    {
        $vendorWithdrawMethods = $this->vendorWithdrawMethodInfoService->getList(
            relations: ['withdraw_method'],
            filters: ['id' => $id, 'user_id' => auth('seller')->id()],
        );

        return response()->json([
            'status' => count($vendorWithdrawMethods) > 0,
            'data' => $vendorWithdrawMethods,
        ]);
    }

    public function delete($id): RedirectResponse
    {
        $this->vendorWithdrawMethodInfoService->delete(id: $id, userId: auth('seller')->id())
            ? ToastMagic::success(translate('Payment method has been deleted'))
            : ToastMagic::error(translate('Payment method not found'));

        return redirect()->back();
    }

    public function updateDefault(Request $request): RedirectResponse
    {
        $this->vendorWithdrawMethodInfoService->markAsDefault(id: $request['id'], userId: auth('seller')->id())
            ? ToastMagic::success(translate('Payment method set as default'))
            : ToastMagic::error(translate('Payment method not found'));

        return redirect()->back();
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $this->vendorWithdrawMethodInfoService->updateStatus(
            id: $request['id'],
            status: (bool) ($request['status'] ?? 0),
            userId: auth('seller')->id(),
        );
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Status')]));

        return redirect()->back();
    }
}
