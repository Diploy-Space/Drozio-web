<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman;

use App\Enums\AuthErrorCode;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Deliveryman\Earning\ListRequest as EarningListRequest;
use App\Http\Requests\Deliveryman\Withdraw\AddRequest as WithdrawAddRequest;
use App\Http\Requests\Deliveryman\Withdraw\ListRequest as WithdrawListRequest;
use App\Http\Resources\V6\Deliveryman\CashCollectionListResource;
use App\Http\Resources\V6\Deliveryman\EarningListResource;
use App\Http\Resources\V6\Deliveryman\WalletResource;
use App\Http\Resources\V6\Deliveryman\WithdrawListResource;
use App\Services\New\DeliveryManTransactionService;
use App\Services\New\DeliverymanWalletService;
use App\Services\New\OrderService;
use App\Services\New\WithdrawRequestService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class WalletController extends BaseApiController
{
    private const ADMIN_OWNER_ID = 0;

    public function __construct(
        private readonly DeliverymanWalletService $deliverymanWalletService,
        private readonly WithdrawRequestService $withdrawRequestService,
        private readonly DeliveryManTransactionService $deliveryManTransactionService,
        private readonly OrderService $orderService,
    ) {}

    #[ApiDoc(
        summary: 'Delivery man wallet balances',
        description: 'The same balances the profile returns, on their own so a wallet screen does not have to fetch the whole profile.',
        tag: 'Delivery-man Wallet',
        status: [401, 403],
        guard: 'deliveryman',
    )]
    public function getWallet(Request $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new WalletResource(
                $this->deliverymanWalletService->getBalanceSummary(deliverymanId: (int) $deliveryman->id),
            ),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Request a withdrawal',
        description: 'The amount is reserved against the withdrawable balance in one conditional update, so two concurrent requests cannot together exceed it.',
        tag: 'Delivery-man Wallet',
        status: [201, 401, 403, 422],
        guard: 'deliveryman',
    )]
    public function createWithdraw(WithdrawAddRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $amount = (float) $request['amount'];

        $withdraw = DB::transaction(function () use ($deliveryman, $request, $amount) {
            $reserved = $this->deliverymanWalletService->reservePendingWithdraw(
                deliverymanId: (int) $deliveryman->id,
                amount: $amount,
            );

            if (! $reserved) {
                return null;
            }

            return $this->withdrawRequestService->create(data: [
                'delivery_man_id' => $deliveryman->id,
                'amount' => $amount,
                'transaction_note' => $request['note'],
                'approved' => WithdrawRequestService::APPROVAL_PENDING,
            ] + $this->ownerColumn($deliveryman));
        });

        if ($withdraw === null) {
            return $this->authError(
                config: config('response.withdraw_amount_above_balance'),
                errorCode: AuthErrorCode::INSUFFICIENT_BALANCE,
            );
        }

        return $this->responseFormatter(
            config: config('response.withdraw_request_sent'),
            content: ['data' => new WithdrawListResource($withdraw),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Withdraw requests the delivery man has made',
        description: 'Filter with type=pending|approved|denied and an optional start_date/end_date range.',
        tag: 'Delivery-man Wallet',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function getWithdraws(WithdrawListRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $args = [

            'search' => $request->query('search'),

            'type' => $request->query('type'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $filters = ['delivery_man_id' => $deliveryman->id];
        $approval = $this->approvalFor($args['type'] ?? null);

        if ($approval !== null) {
            $filters['approved'] = $approval;
        }

        $range = $this->dateRange($request);

        if ($range !== []) {
            $filters[WithdrawRequestService::FILTER_DATE_RANGE] = $range;
        }

        $withdraws = $this->withdrawRequestService->getList(
            dataLimit: $limit,
            page: $offset,
            filters: $filters,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => WithdrawListResource::collection($withdraws),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($withdraws),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Earnings per delivered order',
        description: 'Paid orders assigned to the delivery man, newest first, with an optional start_date/end_date range.',
        tag: 'Delivery-man Wallet',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function getEarnings(EarningListRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $orders = $this->orderService->getList(
            dataLimit: $limit,
            page: $offset,
            relations: ['seller.shop', 'customer'],
            filters: [
                'delivery_man_id' => $deliveryman->id,
                'payment_status' => OrderService::PAYMENT_STATUS_PAID,
            ] + $this->orderDateFilters($request),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => EarningListResource::collection($orders),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($orders),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Cash collection history',
        description: 'The cash-in-hand transactions recorded against the delivery man.',
        tag: 'Delivery-man Wallet',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function getCashCollections(EarningListRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $transactions = $this->deliveryManTransactionService->getList(
            dataLimit: $limit,
            page: $offset,
            filters: array_filter([
                'delivery_man_id' => $deliveryman->id,
                'transaction_type' => DeliveryManTransactionService::TYPE_CASH_IN_HAND,
                DeliveryManTransactionService::FILTER_DATE_RANGE => $this->dateRange($request),
            ]),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => CashCollectionListResource::collection($transactions),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($transactions),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function ownerColumn(mixed $deliveryman): array
    {
        $parentId = (int) ($deliveryman->seller_id ?? self::ADMIN_OWNER_ID);

        return $parentId === self::ADMIN_OWNER_ID
            ? ['admin_id' => self::ADMIN_OWNER_ID]
            : ['seller_id' => $parentId];
    }

    private function approvalFor(?string $type): ?int
    {
        return match ($type) {
            WithdrawRequestService::STATUS_APPROVED => WithdrawRequestService::APPROVAL_APPROVED,
            WithdrawRequestService::STATUS_DENIED => WithdrawRequestService::APPROVAL_DENIED,
            WithdrawRequestService::STATUS_PENDING => WithdrawRequestService::APPROVAL_PENDING,
            default => null,
        };
    }

    /**
     * OrderService only applies date_range when date_type is custom, so both keys travel together.
     *
     * @return array<string, mixed>
     */
    private function orderDateFilters(Request $request): array
    {
        $range = $this->dateRange($request);

        return $range === [] ? [] : [
            OrderService::FILTER_DATE_TYPE => OrderService::DATE_TYPE_CUSTOM,
            OrderService::FILTER_DATE_RANGE => $range,
        ];
    }

    /**
     * @return array<int, Carbon>
     */
    private function dateRange(Request $request): array
    {
        if (empty($request['start_date']) || empty($request['end_date'])) {
            return [];
        }

        return [
            Carbon::parse($request['start_date'])->startOfDay(),
            Carbon::parse($request['end_date'])->endOfDay(),
        ];
    }
}
