<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Events\WithdrawStatusUpdateEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\DeliveryManWithdraw\StatusUpdateRequest;
use App\Http\Resources\V6\Vendor\DeliveryManWithdrawResource;
use App\Services\New\DeliverymanWalletService;
use App\Services\New\WithdrawRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class DeliveryManWithdrawController extends BaseApiController
{
    public function __construct(
        private readonly WithdrawRequestService $withdrawRequestService,
        private readonly DeliverymanWalletService $deliverymanWalletService,
    ) {}

    #[ApiDoc(
        summary: 'Withdrawal requests raised by the vendor delivery men',
        tag: 'Delivery Man (Vendor)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'status' => 'nullable|string'],
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
            relations: ['deliveryMan'],
            filters: $this->scope($request)
                + [WithdrawRequestService::FILTER_HAS_DELIVERY_MAN => true]
                + $this->approvalFilter($args['status']),
            sortBy: WithdrawRequestService::SORT_LATEST,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => DeliveryManWithdrawResource::collection($withdraws),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($withdraws),
            ],
        );
    }

    #[ApiDoc(
        summary: 'One withdrawal request raised by a vendor delivery man',
        tag: 'Delivery Man (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function getDetails(Request $request, string $id): JsonResponse
    {
        $withdraw = $this->ownWithdraw($request, $id);

        return $withdraw === null
            ? $this->notFound()
            : $this->responseFormatter(
                config: config('response.data_fetched'),
                content: ['data' => new DeliveryManWithdrawResource($withdraw),
                ],
            );
    }

    #[ApiDoc(
        summary: 'Approve or deny a delivery man withdrawal request',
        description: 'Only a pending request can be decided, so approving twice cannot move the wallet twice. The wallet is adjusted in the same transaction as the decision.',
        tag: 'Delivery Man (Vendor)',
        status: [401, 403, 404, 409, 422],
        guard: 'seller',
    )]
    public function updateStatus(StatusUpdateRequest $request, string $id): JsonResponse
    {
        $withdraw = $this->ownWithdraw($request, $id);

        if ($withdraw === null) {
            return $this->notFound();
        }

        if ((int) $withdraw->approved !== WithdrawRequestService::APPROVAL_PENDING) {
            return $this->responseFormatter(config: config('response.this_withdrawal_request_has_already_been_decided'));
        }

        $isApproved = (int) $request['approved'] === WithdrawRequestService::APPROVAL_APPROVED;

        $settled = DB::transaction(fn (): bool => $this->deliverymanWalletService->settleWithdraw(
            deliverymanId: (int) $withdraw->delivery_man_id,
            amount: (float) $withdraw->amount,
            isApproved: $isApproved,
        ) && $this->withdrawRequestService->update(id: $withdraw->id, data: [
            'approved' => $isApproved ? WithdrawRequestService::APPROVAL_APPROVED : WithdrawRequestService::APPROVAL_DENIED,
            'transaction_note' => $request['transaction_note'],
        ]) !== null);

        if (! $settled) {
            return $this->responseFormatter(config: config('response.this_withdrawal_request_could_not_be_settled'));
        }

        if (! empty($withdraw->deliveryMan?->fcm_token)) {
            WithdrawStatusUpdateEvent::dispatch(
                'withdraw_request_status_message',
                'delivery_man',
                $withdraw->deliveryMan->app_language ?? getDefaultLanguage(),
                $isApproved ? 1 : 0,
                $withdraw->deliveryMan->fcm_token,
            );
        }

        return $this->responseFormatter(config: config('response.data_fetched'));
    }

    /** @return array<string, mixed> */
    private function scope(Request $request): array
    {
        return ['seller_id' => $request->attributes->get('seller')->id];
    }

    /** @return array<string, mixed> */
    private function approvalFilter(?string $status): array
    {
        return match ($status) {
            WithdrawRequestService::STATUS_APPROVED => ['approved' => WithdrawRequestService::APPROVAL_APPROVED],
            WithdrawRequestService::STATUS_DENIED => ['approved' => WithdrawRequestService::APPROVAL_DENIED],
            WithdrawRequestService::STATUS_PENDING => ['approved' => WithdrawRequestService::APPROVAL_PENDING],
            default => [],
        };
    }

    private function ownWithdraw(Request $request, string $id): mixed
    {
        $withdraw = $this->withdrawRequestService->findBy(
            params: $this->scope($request) + ['id' => (int) $id],
            relations: ['deliveryMan'],
        );

        return $withdraw?->delivery_man_id === null ? null : $withdraw;
    }

    private function notFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.withdrawal_request_not_found'));
    }
}
