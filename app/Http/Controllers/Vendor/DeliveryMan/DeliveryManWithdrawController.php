<?php

namespace App\Http\Controllers\Vendor\DeliveryMan;

use App\Enums\WebConfigKey;
use App\Events\WithdrawStatusUpdateEvent;
use App\Exports\DeliveryManWithdrawRequestExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Shared\DeliveryMan\WithdrawRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\DeliverymanWalletService;
use App\Services\New\WithdrawRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeliveryManWithdrawController extends BaseController
{
    private const EXPORT_FILE_NAME = 'Delivery-Man-Withdraw-Request.xlsx';

    public function __construct(
        private readonly WithdrawRequestService $withdrawRequestService,
        private readonly DeliverymanWalletService $deliverymanWalletService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return view('vendor-views.delivery-man.withdraw.index', [
            'withdrawRequests' => $this->paginatedWithdrawRequests(
                searchValue: $request['searchValue'],
                status: $request['status'],
            ),
        ]);
    }

    public function getFiltered(Request $request): JsonResponse
    {
        $withdrawRequests = $this->paginatedWithdrawRequests(
            searchValue: $request['searchValue'],
            status: $request['status'],
        );

        return response()->json([
            'view' => view('vendor-views.delivery-man.withdraw._table', compact('withdrawRequests'))->render(),
            'count' => $withdrawRequests->total(),
        ], 200);
    }

    public function getDetails(string|int $withdrawId): JsonResponse
    {
        $details = $this->deliverymanWithdraw(withdrawId: $withdrawId);

        if ($details === null) {
            return response()->json(['error' => translate('Invalid withdraw')]);
        }

        return response()->json([
            'view' => view('vendor-views.delivery-man.withdraw._details', compact('details'))->render(),
        ]);
    }

    public function updateStatus(WithdrawRequest $request, string|int $withdrawId): JsonResponse
    {
        $withdraw = $this->deliverymanWithdraw(withdrawId: $withdrawId);

        if ($withdraw === null) {
            return response()->json(['error' => translate('Invalid withdraw')]);
        }

        if ((int) $withdraw->approved !== WithdrawRequestService::APPROVAL_PENDING) {
            return response()->json(['error' => translate('This withdraw request has already been decided')]);
        }

        $this->keepWallet(deliverymanId: (int) $withdraw->delivery_man_id);
        $isApproved = (int) $request['approved'] === WithdrawRequestService::APPROVAL_APPROVED;

        $isSettled = DB::transaction(fn (): bool => $this->deliverymanWalletService->settleWithdraw(
            deliverymanId: (int) $withdraw->delivery_man_id,
            amount: (float) $withdraw->amount,
            isApproved: $isApproved,
        ) && $this->withdrawRequestService->update(id: $withdraw->id, data: [
            'approved' => $isApproved ? WithdrawRequestService::APPROVAL_APPROVED : WithdrawRequestService::APPROVAL_DENIED,
            'transaction_note' => $request['note'],
        ]) !== null);

        if (! $isSettled) {
            return response()->json(['error' => translate('This withdraw request could not be settled')]);
        }

        $this->notifyDeliveryman(withdraw: $withdraw, isApproved: $isApproved);

        return response()->json(['message' => $isApproved
            ? translate('Delivery man payment has been approved successfully')
            : translate('Delivery man payment request has been denied successfully'),
        ]);
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $withdrawRequests = $this->withdrawRequestService->getList(
            searchValue: $this->searchableValue(searchValue: $request['searchValue']),
            dataLimit: 'all',
            relations: ['deliveryMan'],
            filters: $this->listFilters(status: $request['status']),
            sortBy: WithdrawRequestService::SORT_NEWEST_ID,
        );

        return Excel::download(new DeliveryManWithdrawRequestExport([
            'withdraw_request' => $withdrawRequests,
            'filter' => $request['status'],
            'searchValue' => $request['searchValue'],
            'pending_request' => $withdrawRequests->where('approved', WithdrawRequestService::APPROVAL_PENDING)->count(),
            'approved_request' => $withdrawRequests->where('approved', WithdrawRequestService::APPROVAL_APPROVED)->count(),
            'denied_request' => $withdrawRequests->where('approved', WithdrawRequestService::APPROVAL_DENIED)->count(),
        ]), self::EXPORT_FILE_NAME);
    }

    private function sellerId(): int
    {
        return (int) auth('seller')->id();
    }

    private function paginatedWithdrawRequests(?string $searchValue, ?string $status): LengthAwarePaginator
    {
        return $this->withdrawRequestService->getList(
            searchValue: $this->searchableValue(searchValue: $searchValue),
            dataLimit: $this->paginationLimit(),
            relations: ['deliveryMan'],
            filters: $this->listFilters(status: $status),
            sortBy: WithdrawRequestService::SORT_NEWEST_ID,
        )->appends(array_filter([
            'searchValue' => $searchValue,
            'status' => $status,
        ], static fn ($value) => $value !== null));
    }

    /** @return array<string, mixed> */
    private function listFilters(?string $status): array
    {
        $approval = match ($status) {
            WithdrawRequestService::STATUS_PENDING => WithdrawRequestService::APPROVAL_PENDING,
            WithdrawRequestService::STATUS_APPROVED => WithdrawRequestService::APPROVAL_APPROVED,
            WithdrawRequestService::STATUS_DENIED => WithdrawRequestService::APPROVAL_DENIED,
            default => null,
        };

        return [
            'seller_id' => $this->sellerId(),
            WithdrawRequestService::FILTER_HAS_DELIVERY_MAN => true,
        ] + ($approval === null ? [] : ['approved' => $approval]);
    }

    private function searchableValue(?string $searchValue): ?string
    {
        if ($searchValue === null) {
            return null;
        }

        $normalizedValue = str_replace(',', '', $searchValue);

        return is_numeric($normalizedValue)
            ? (string) round(currencyConverter(amount: (float) $normalizedValue), 2)
            : $searchValue;
    }

    private function deliverymanWithdraw(string|int $withdrawId): ?Model
    {
        $withdraw = $this->withdrawRequestService->findBy(
            params: ['id' => $withdrawId, 'seller_id' => $this->sellerId()],
            relations: ['deliveryMan'],
        );

        return $withdraw?->delivery_man_id === null ? null : $withdraw;
    }

    private function keepWallet(int $deliverymanId): void
    {
        if ($this->deliverymanWalletService->findBy(params: ['delivery_man_id' => $deliverymanId]) === null) {
            $this->deliverymanWalletService->create(data: ['delivery_man_id' => $deliverymanId]);
        }
    }

    private function notifyDeliveryman(Model $withdraw, bool $isApproved): void
    {
        if (empty($withdraw->deliveryMan?->fcm_token)) {
            return;
        }

        WithdrawStatusUpdateEvent::dispatch(
            'withdraw_request_status_message',
            'delivery_man',
            $withdraw->deliveryMan->app_language ?? getDefaultLanguage(),
            (string) ($isApproved ? WithdrawRequestService::APPROVAL_APPROVED : WithdrawRequestService::APPROVAL_DENIED),
            $withdraw->deliveryMan->fcm_token,
        );
    }

    private function paginationLimit(): int
    {
        return (int) ($this->businessSettingService->getValueByType(type: WebConfigKey::PAGINATION_LIMIT) ?? DEFAULT_DATA_LIMIT);
    }
}
