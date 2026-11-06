<?php

namespace App\Http\Controllers\Admin\People;

use App\Enums\WebConfigKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\People\WithdrawMethod\AddRequest;
use App\Http\Requests\Admin\People\WithdrawMethod\UpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\WithdrawalMethodService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WithdrawalMethodController extends Controller
{
    public function __construct(
        private readonly WithdrawalMethodService $withdrawalMethodService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(Request $request, string $userType): View
    {
        $withdrawalMethods = $this->withdrawalMethodService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->businessSettingService->findBy(params: ['type' => WebConfigKey::PAGINATION_LIMIT])?->value,
            filters: [WithdrawalMethodService::FILTER_USER_TYPE => $userType],
            sortBy: WithdrawalMethodService::SORT_LATEST,
        );

        $isCustomerMethod = $userType === WithdrawalMethodService::USER_TYPE_CUSTOMER;

        return view($isCustomerMethod ? 'admin-views.withdraw-method.customer-list' : 'admin-views.withdraw-method.vendor-list', [
            'withdrawalMethods' => $withdrawalMethods,
            'routePrefix' => $this->routePrefix($userType),
            'inputFieldTypes' => $this->inputFieldTypes(),
        ]);
    }

    public function add(AddRequest $request, string $userType): RedirectResponse
    {
        $this->withdrawalMethodService->create(data: $request->all(), userType: $userType);

        ToastMagic::success(translate(':item added successfully', ['item' => translate('Withdraw method')]));

        return redirect()->route($this->routePrefix($userType).'.list');
    }

    public function update(UpdateRequest $request, string $userType): RedirectResponse
    {
        $withdrawalMethod = $this->withdrawalMethodService->update(
            id: $request['id'],
            data: $request->all(),
            userType: $userType,
        );

        $withdrawalMethod === null
            ? ToastMagic::error(translate('Withdraw method not found'))
            : ToastMagic::success(translate(':item updated successfully', ['item' => translate('Withdraw method')]));

        return redirect()->route($this->routePrefix($userType).'.list');
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $isUpdated = $this->withdrawalMethodService->updateStatus(
            id: $request['id'],
            status: (bool) ($request['status'] ?? 0),
        );

        return response()->json([
            'success' => (int) $isUpdated,
            'message' => $isUpdated
                ? translate(':item updated successfully', ['item' => translate('Status')])
                : translate('The default withdraw method status cannot be changed'),
        ]);
    }

    public function updateDefaultStatus(Request $request, string $userType): JsonResponse
    {
        $isUpdated = $this->withdrawalMethodService->updateDefaultStatus(id: $request['id'], userType: $userType);

        return response()->json([
            'success' => (int) $isUpdated,
            'message' => $isUpdated
                ? translate(':item updated successfully', ['item' => translate('Default method')])
                : translate('Only an active withdraw method can be set as default'),
        ]);
    }

    public function delete(string|int $id, string $userType): RedirectResponse
    {
        if (! $this->withdrawalMethodService->exists(params: ['id' => $id])) {
            ToastMagic::error(translate('Withdraw method not found'));

            return redirect()->route($this->routePrefix($userType).'.list');
        }

        $this->withdrawalMethodService->delete(id: $id)
            ? ToastMagic::success(translate(':item removed successfully', ['item' => translate('Withdraw method')]))
            : ToastMagic::error(translate('The default withdraw method cannot be deleted'));

        return redirect()->route($this->routePrefix($userType).'.list');
    }

    /** @return array<string, string> */
    private function inputFieldTypes(): array
    {
        return [
            'string' => translate('String'),
            'number' => translate('Number'),
            'date' => translate('Date'),
            'password' => translate('Password'),
            'email' => translate('Email'),
            'phone' => translate('Phone'),
        ];
    }

    private function routePrefix(string $userType): string
    {
        return $userType === WithdrawalMethodService::USER_TYPE_CUSTOMER
            ? 'admin.people.customer.withdraw-method'
            : 'admin.people.vendors.withdraw-method';
    }
}
