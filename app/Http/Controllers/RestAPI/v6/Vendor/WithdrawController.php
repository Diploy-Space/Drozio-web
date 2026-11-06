<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\Withdraw\AddRequest;
use App\Http\Requests\Vendor\Withdraw\UpdateRequest;
use App\Http\Resources\V6\Shared\WithdrawalMethodResource;
use App\Http\Resources\V6\Vendor\WithdrawResource;
use App\Services\New\VendorWalletService;
use App\Services\New\WithdrawalMethodService;
use App\Services\New\WithdrawRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class WithdrawController extends BaseApiController
{
    public function __construct(
        private readonly WithdrawRequestService $withdrawRequestService,
        private readonly WithdrawalMethodService $withdrawalMethodService,
        private readonly VendorWalletService $vendorWalletService,
    ) {}

    #[ApiDoc(
        summary: 'The vendor own withdrawal requests',
        description: 'Delivery-man requests are excluded; this is the vendor payout history the app shows as transactions.',
        tag: 'Withdraw (Vendor)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'status' => 'nullable|string', 'from' => 'nullable|date', 'to' => 'nullable|date'],
        status: [401, 403],
        guard: 'seller',
    )]
    public function getList(Request $request): JsonResponse
    {
        $args = [

            'search' => $request->query('search'),

            'status' => $request->query('status'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $withdraws = $this->withdrawRequestService->getList(
            dataLimit: $limit,
            page: $offset,
            filters: $this->scope($request)
                + ['delivery_man_id' => null]
                + $this->approvalFilter($args['status'])
                + $this->dateFilter($request),
            sortBy: WithdrawRequestService::SORT_LATEST,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => WithdrawResource::collection($withdraws),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($withdraws),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Withdrawal methods the vendor may request against',
        tag: 'Withdraw (Vendor)',
        status: [401, 403],
        guard: 'seller',
    )]
    public function getMethods(Request $request): JsonResponse
    {
        $methods = $this->withdrawalMethodService->getList(
            dataLimit: 'all',
            filters: ['is_active' => 1, 'vendor_status' => 1],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => WithdrawalMethodResource::collection($methods)],
        );
    }

    #[ApiDoc(
        summary: 'Raise a withdrawal request',
        description: 'The amount is moved from withdrawable earnings into the pending reservation by a conditional update, so two concurrent requests cannot reserve the same balance twice.',
        tag: 'Withdraw (Vendor)',
        status: [201, 401, 403, 422],
        guard: 'seller',
    )]
    public function store(AddRequest $request): JsonResponse
    {
        $sellerId = (int) $request->attributes->get('seller')->id;
        $method = $this->withdrawalMethodService->find(id: $request['withdraw_method']);
        $amount = (float) currencyConverter(amount: $request['amount']);

        $withdraw = DB::transaction(function () use ($sellerId, $method, $amount, $request) {
            if (! $this->vendorWalletService->reserveWithdraw(sellerId: $sellerId, amount: $amount)) {
                return null;
            }

            return $this->withdrawRequestService->create(data: [
                'seller_id' => $sellerId,
                'amount' => $amount,
                'withdrawal_method_id' => $method->id,
                'withdrawal_method_fields' => json_encode($this->methodFields(method: $method, input: $request->all())),
                'approved' => WithdrawRequestService::APPROVAL_PENDING,
            ]);
        });

        return $withdraw === null
            ? $this->insufficientBalance()
            : $this->responseFormatter(
                config: config('response.withdraw_request_sent_successfully'),
                content: ['data' => new WithdrawResource($withdraw),
                ],
            );
    }

    #[ApiDoc(
        summary: 'Change one of the vendor own pending withdrawal requests',
        description: 'Only a pending request the vendor raised can be changed. The previous reservation is released and the new amount reserved in one transaction.',
        tag: 'Withdraw (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        $sellerId = (int) $request->attributes->get('seller')->id;
        $withdraw = $this->ownPendingWithdraw($request, $id);

        if ($withdraw === null) {
            return $this->notFound();
        }

        $method = $this->withdrawalMethodService->find(id: $request['withdraw_method']);
        $amount = (float) currencyConverter(amount: $request['amount']);

        $updated = DB::transaction(function () use ($withdraw, $sellerId, $amount, $method, $request) {
            if (! $this->moveReservation(sellerId: $sellerId, from: (float) $withdraw->amount, to: $amount)) {
                return null;
            }

            return $this->withdrawRequestService->update(id: $withdraw->id, data: [
                'amount' => $amount,
                'withdrawal_method_id' => $method->id,
                'withdrawal_method_fields' => json_encode($this->methodFields(method: $method, input: $request->all())),
            ]);
        });

        return $updated === null
            ? $this->insufficientBalance()
            : $this->responseFormatter(
                config: config('response.withdraw_request_updated_successfully'),
                content: ['data' => new WithdrawResource($updated),
                ],
            );
    }

    #[ApiDoc(
        summary: 'Close one of the vendor own pending withdrawal requests',
        description: 'Only a pending request the vendor raised can be closed, and the released amount returns to that same vendor.',
        tag: 'Withdraw (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function delete(Request $request, string $id): JsonResponse
    {
        $sellerId = (int) $request->attributes->get('seller')->id;
        $withdraw = $this->ownPendingWithdraw($request, $id);

        if ($withdraw === null) {
            return $this->notFound();
        }

        DB::transaction(function () use ($withdraw, $sellerId): void {
            $this->vendorWalletService->releaseWithdraw(sellerId: $sellerId, amount: (float) $withdraw->amount);
            $this->withdrawRequestService->delete(id: $withdraw->id);
        });

        return $this->responseFormatter(config: config('response.withdraw_request_closed_successfully'));
    }

    /**
     * Re-reserve a changed request by moving only the difference, so the wallet never briefly
     * releases the whole amount and a raise that the balance cannot cover changes nothing.
     */
    private function moveReservation(int $sellerId, float $from, float $to): bool
    {
        $delta = round($to - $from, 2);

        return match (true) {
            $delta > 0 => $this->vendorWalletService->reserveWithdraw(sellerId: $sellerId, amount: $delta),
            $delta < 0 => $this->vendorWalletService->releaseWithdraw(sellerId: $sellerId, amount: abs($delta)),
            default => true,
        };
    }

    /** @return array<string, mixed> */
    private function scope(Request $request): array
    {
        return ['seller_id' => $request->attributes->get('seller')->id];
    }

    private function ownPendingWithdraw(Request $request, string $id): mixed
    {
        return $this->withdrawRequestService->findBy(params: $this->scope($request) + [
            'id' => (int) $id,
            'delivery_man_id' => null,
            'approved' => WithdrawRequestService::APPROVAL_PENDING,
        ]);
    }

    /** @return array<string, mixed> */
    private function approvalFilter(?string $status): array
    {
        return match ($status) {
            'approve', WithdrawRequestService::STATUS_APPROVED => ['approved' => WithdrawRequestService::APPROVAL_APPROVED],
            'deny', WithdrawRequestService::STATUS_DENIED => ['approved' => WithdrawRequestService::APPROVAL_DENIED],
            WithdrawRequestService::STATUS_PENDING => ['approved' => WithdrawRequestService::APPROVAL_PENDING],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private function dateFilter(Request $request): array
    {
        return $request['from'] && $request['to']
            ? [WithdrawRequestService::FILTER_DATE_RANGE => [$request['from'], $request['to']]]
            : [];
    }

    /** @return array<string, mixed> */
    private function methodFields(mixed $method, array $input): array
    {
        $fields = ['method_name' => $method->method_name];

        foreach ($method->method_fields ?? [] as $field) {
            $name = $field['input_name'] ?? null;

            if ($name !== null && array_key_exists($name, $input)) {
                $fields[$name] = $input[$name];
            }
        }

        return $fields;
    }

    private function insufficientBalance(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.currently_this_amount_is_not_withdrawable'));
    }

    private function notFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.withdraw_request_not_found'));
    }
}
