<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Events\CashCollectEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\DeliveryMan\AddRequest;
use App\Http\Requests\Vendor\DeliveryMan\StatusUpdateRequest;
use App\Http\Requests\Vendor\DeliveryMan\UpdateRequest;
use App\Http\Requests\Vendor\DeliveryMan\WalletRequest;
use App\Http\Resources\V6\Vendor\DeliveryManResource;
use App\Http\Resources\V6\Vendor\DeliveryManTransactionResource;
use App\Http\Resources\V6\Vendor\OrderResource;
use App\Services\New\DeliveryManService;
use App\Services\New\DeliveryManTransactionService;
use App\Services\New\DeliverymanWalletService;
use App\Services\New\OrderService;
use App\Services\New\OrderStatusHistoryService;
use App\Services\New\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class DeliveryManController extends BaseApiController
{
    public function __construct(
        private readonly DeliveryManService $deliveryManService,
        private readonly DeliverymanWalletService $deliverymanWalletService,
        private readonly DeliveryManTransactionService $deliveryManTransactionService,
        private readonly OrderStatusHistoryService $orderStatusHistoryService,
        private readonly OrderService $orderService,
        private readonly ReviewService $reviewService,
    ) {}

    #[ApiDoc(
        summary: 'Delivery men working for the vendor',
        tag: 'Delivery Man (Vendor)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [401, 403],
        guard: 'seller',
    )]
    public function getList(Request $request): JsonResponse
    {
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $deliveryMen = $this->deliveryManService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: ['rating'],
            withCount: ['orders'],
            filters: $this->sellerScope($request),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => DeliveryManResource::collection($deliveryMen),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($deliveryMen),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Every delivery man of the vendor, unpaginated',
        description: 'For an assignment picker. Returns the whole list in one call, so it carries no pagination.',
        tag: 'Delivery Man (Vendor)',
        status: [401, 403],
        guard: 'seller',
    )]
    public function getOptions(Request $request): JsonResponse
    {
        $deliveryMen = $this->deliveryManService->getList(
            dataLimit: 'all',
            filters: $this->sellerScope($request),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => DeliveryManResource::collection($deliveryMen)],
        );
    }

    #[ApiDoc(
        summary: 'The vendor delivery men ranked by delivered orders',
        tag: 'Delivery Man (Vendor)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [401, 403],
        guard: 'seller',
    )]
    public function getTop(Request $request): JsonResponse
    {
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $deliveryMen = $this->deliveryManService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: ['rating'],
            withCount: ['deliveredOrders'],
            filters: $this->sellerScope($request),
            sortBy: DeliveryManService::SORT_MOST_DELIVERED,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => DeliveryManResource::collection($deliveryMen),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($deliveryMen),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Add a delivery man',
        tag: 'Delivery Man (Vendor)',
        status: [201, 401, 403, 422],
        guard: 'seller',
    )]
    public function store(AddRequest $request): JsonResponse
    {
        $deliveryMan = $this->deliveryManService->create(
            data: $request->validated() + ['seller_id' => $request->attributes->get('seller')->id],
            image: $request->file('image'),
            identityImages: $request->file('identity_image') ?? [],
        );

        return $this->responseFormatter(
            config: config('response.delivery_man_added_successfully'),
            content: ['data' => new DeliveryManResource($deliveryMan)],
        );
    }

    #[ApiDoc(
        summary: 'One of the vendor delivery men, with wallet balances',
        tag: 'Delivery Man (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function getDetails(Request $request, string $id): JsonResponse
    {
        $deliveryMan = $this->ownDeliveryMan($request, $id, ['wallet']);

        if ($deliveryMan === null) {
            return $this->notFound();
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => (new DeliveryManResource($deliveryMan))->resolve() + [
                'withdrawable_balance' => $this->deliverymanWalletService->getBalanceSummary(deliverymanId: (int) $id)['withdrawable_balance'],
            ],
            ],
        );
    }

    #[ApiDoc(
        summary: 'Update one of the vendor delivery men',
        tag: 'Delivery Man (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        if ($this->ownDeliveryMan($request, $id) === null) {
            return $this->notFound();
        }

        $deliveryMan = $this->deliveryManService->update(
            id: (int) $id,
            data: $request->validated(),
            image: $request->file('image'),
        );

        return $this->responseFormatter(
            config: config('response.delivery_man_updated_successfully'),
            content: ['data' => new DeliveryManResource($deliveryMan)],
        );
    }

    #[ApiDoc(
        summary: 'Turn one of the vendor delivery men on or off',
        tag: 'Delivery Man (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateStatus(StatusUpdateRequest $request, string $id): JsonResponse
    {
        $updated = $this->deliveryManService->updateBy(
            params: $this->sellerScope($request) + ['id' => (int) $id],
            data: ['is_active' => (int) $request['status']],
        );

        return $updated === null
            ? $this->notFound()
            : $this->responseFormatter(config: config('response.delivery_man_status_updated_successfully'));
    }

    #[ApiDoc(
        summary: 'Delete one of the vendor delivery men',
        tag: 'Delivery Man (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function delete(Request $request, string $id): JsonResponse
    {
        if ($this->ownDeliveryMan($request, $id) === null) {
            return $this->notFound();
        }

        $this->deliveryManService->delete(id: (int) $id);

        return $this->responseFormatter(config: config('response.delivery_man_deleted_successfully'));
    }

    #[ApiDoc(
        summary: 'Earnings of one of the vendor delivery men',
        tag: 'Delivery Man (Vendor)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function getEarnings(Request $request, string $id): JsonResponse
    {
        if ($this->ownDeliveryMan($request, $id) === null) {
            return $this->notFound();
        }

        $args = [

            'search' => $request->query('search'),

        ];

        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);

        $offset = (int) $request->query('offset', 1);
        $balances = $this->deliverymanWalletService->getBalanceSummary(deliverymanId: (int) $id);

        $orders = $this->orderService->getList(
            dataLimit: $limit,
            page: $offset,
            filters: ['delivery_man_id' => (int) $id],
            sortBy: OrderService::SORT_NEWEST,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['total_size' => $orders->total(),
                ...$args,
                'total_earn' => $balances['total_earn'],
                'withdrawable_balance' => $balances['withdrawable_balance'],
                'data' => $orders->items(),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Reviews left for one of the vendor delivery men',
        tag: 'Delivery Man (Vendor)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function getReviews(Request $request, string $id): JsonResponse
    {
        if ($this->ownDeliveryMan($request, $id) === null) {
            return $this->notFound();
        }

        $args = [

            'search' => $request->query('search'),

        ];

        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);

        $offset = (int) $request->query('offset', 1);

        $reviews = $this->reviewService->getList(
            dataLimit: $limit,
            page: $offset,
            relations: ['customer:id,f_name,l_name,image'],
            filters: ['delivery_man_id' => (int) $id],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['total_size' => $reviews->total(),
                ...$args,
                ...$this->reviewService->getRatingSummary(filters: ['delivery_man_id' => (int) $id]),
                'data' => $reviews->items(),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Status history of one order delivered by the vendor delivery men',
        description: 'Scoped to the vendor own orders, so a vendor cannot read the history of an order that is not theirs.',
        tag: 'Delivery Man (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function getOrderStatusHistory(Request $request, string $orderId): JsonResponse
    {
        $order = $this->orderService->findBy(params: [
            'id' => (int) $orderId,
            'seller_is' => OrderService::SELLER_IS_SELLER,
            'seller_id' => $request->attributes->get('seller')->id,
        ]);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => $this->orderStatusHistoryService->getList(
                dataLimit: 'all',
                filters: ['order_id' => (int) $orderId],
            ),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Orders assigned to one of the vendor delivery men',
        tag: 'Delivery Man (Vendor)',
        query: ['limit' => 'nullable|integer', 'offset' => 'nullable|integer'],
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function getOrders(Request $request, string $id): JsonResponse
    {
        if ($this->ownDeliveryMan($request, $id) === null) {
            return $this->notFound();
        }

        $orders = $this->orderService->getList(
            dataLimit: (int) $request->query('limit', DEFAULT_DATA_LIMIT),
            page: (int) $request->query('offset', 1),
            filters: [
                'delivery_man_id' => (int) $id,
                OrderService::FILTER_DELIVERYMAN_SELLER_ID => $request->attributes->get('seller')->id,
            ],
            sortBy: OrderService::SORT_LATEST_UPDATED,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => OrderResource::collection($this->orderService->withResolvedBillingAddress(orders: $orders)),
                'params' => $this->paramsFormatter([]),
                'pagination' => $this->paginateFormatter($orders),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Cash the vendor has collected from one delivery man',
        tag: 'Delivery Man (Vendor)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function getCashCollections(Request $request, string $id): JsonResponse
    {
        if ($this->ownDeliveryMan($request, $id) === null) {
            return $this->notFound();
        }

        $args = [

            'search' => $request->query('search'),

        ];

        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);

        $offset = (int) $request->query('offset', 1);

        $transactions = $this->deliveryManTransactionService->getList(
            dataLimit: $limit,
            page: $offset,
            filters: [
                'delivery_man_id' => (int) $id,
                'transaction_type' => DeliveryManTransactionService::TYPE_CASH_IN_HAND,
            ],
            sortBy: DeliveryManTransactionService::SORT_LATEST,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => DeliveryManTransactionResource::collection($transactions),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($transactions),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Collect cash in hand from one of the vendor delivery men',
        description: 'The amount is converted once and both the check and the deduction use the converted value. The wallet row is locked for the update, so two concurrent collections cannot both pass the balance check.',
        tag: 'Delivery Man (Vendor)',
        status: [201, 401, 403, 404, 422],
        guard: 'seller',
    )]
    public function collectCash(WalletRequest $request, string $id): JsonResponse
    {
        $deliveryMan = $this->ownDeliveryMan($request, $id);

        if ($deliveryMan === null) {
            return $this->notFound();
        }

        $amount = (float) currencyConverter(amount: $request['amount']);
        $sellerId = (int) $request->attributes->get('seller')->id;

        $collected = DB::transaction(function () use ($id, $amount, $sellerId): bool {
            if (! $this->deliverymanWalletService->deductCashInHand(deliverymanId: (int) $id, amount: $amount)) {
                return false;
            }

            $this->deliveryManTransactionService->create(data: [
                'delivery_man_id' => (int) $id,
                'user_id' => $sellerId,
                'user_type' => 'seller',
                'credit' => $amount,
                'transaction_type' => DeliveryManTransactionService::TYPE_CASH_IN_HAND,
            ]);

            return true;
        });

        if (! $collected) {
            return $this->responseFormatter(config: config('response.receive_amount_cannot_be_more_than_cash_in_hand'));
        }

        if (! empty($deliveryMan->fcm_token)) {
            CashCollectEvent::dispatch(
                'cash_collect_by_seller_message',
                'delivery_man',
                $deliveryMan->app_language ?? getDefaultLanguage(),
                $amount,
                $deliveryMan->fcm_token,
            );
        }

        return $this->responseFormatter(config: config('response.amount_received_successfully'));
    }

    /** @return array<string, mixed> */
    private function sellerScope(Request $request): array
    {
        return ['seller_id' => $request->attributes->get('seller')->id];
    }

    private function ownDeliveryMan(Request $request, string $id, array $relations = []): mixed
    {
        return $this->deliveryManService->findBy(
            params: $this->sellerScope($request) + ['id' => (int) $id],
            relations: $relations,
        );
    }

    private function notFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.delivery_man_not_found'));
    }
}
