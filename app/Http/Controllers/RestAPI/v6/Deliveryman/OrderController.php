<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Deliveryman\Order\DeliveryHistoryRequest;
use App\Http\Requests\Deliveryman\Order\LastLocationRequest;
use App\Http\Requests\Deliveryman\Order\ListRequest;
use App\Http\Requests\Deliveryman\Order\SearchRequest;
use App\Http\Resources\V6\Deliveryman\DeliveryHistoryResource;
use App\Http\Resources\V6\Deliveryman\OrderDetailResource;
use App\Http\Resources\V6\Deliveryman\OrderResource;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryHistoryService;
use App\Services\New\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class OrderController extends BaseApiController
{
    private const ORDER_RELATIONS = ['shippingAddress', 'customer', 'seller.shop'];

    private const ITEM_RELATIONS = [
        'details.productAllStatus',
        'details.latestEditHistory',
        'details.order.shippingAddress',
        'details.order.customer',
        'details.order.seller.shop',
    ];

    public function __construct(
        private readonly OrderService $orderService,
        private readonly DeliveryHistoryService $deliveryHistoryService,
        private readonly CustomerService $customerService,
    ) {}

    #[ApiDoc(
        summary: 'Current deliveries of the delivery man',
        description: 'Pending, confirmed, processing and out-for-delivery orders assigned to the delivery man, soonest expected delivery first.',
        tag: 'Delivery-man Orders',
        query: ['limit' => 'nullable|integer', 'offset' => 'nullable|integer'],
        status: [401, 403],
        guard: 'deliveryman',
    )]
    public function getCurrent(Request $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $orders = $this->orderService->getDeliverymanCurrentList(
            deliverymanId: (int) $deliveryman->id,
            dataLimit: $limit,
            page: $offset,
            relations: self::ORDER_RELATIONS,
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
        summary: 'Search the delivery man\'s orders',
        description: 'Matches the order id first; when no order id matches, falls back to customers whose first or last name contains any word of the search.',
        tag: 'Delivery-man Orders',
        query: ['search' => 'nullable|string', 'limit' => 'nullable|integer', 'offset' => 'nullable|integer'],
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function search(SearchRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $searchValue = (string) $request->query('search', '');
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $orders = $this->orderService->getDeliverymanSearchList(
            deliverymanId: (int) $deliveryman->id,
            orderIdSearch: $searchValue,
            dataLimit: $limit,
            page: $offset,
        );

        if ($orders->total() === 0) {
            $orders = $this->orderService->getDeliverymanSearchList(
                deliverymanId: (int) $deliveryman->id,
                customerIds: $this->customerService->getIdsMatchingAnyNameWord(searchValue: $searchValue),
                dataLimit: $limit,
                page: $offset,
            );
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => OrderResource::collection($this->orderService->withResolvedBillingAddress(orders: $orders)),
                'params' => $this->paramsFormatter(['search' => $searchValue]),
                'pagination' => $this->paginateFormatter($orders),
            ],
        );
    }

    #[ApiDoc(
        summary: 'All orders assigned to the delivery man',
        description: 'Newest first. search matches the order id or the customer phone; status, is_pause (0 or 1) and date_type (today, this_week, this_month, this_year, custom_date with start_date and end_date) narrow the list.',
        tag: 'Delivery-man Orders',
        query: [
            'search' => 'nullable|string',
            'status' => 'nullable|string',
            'is_pause' => 'nullable|integer',
            'date_type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function getList(ListRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $args = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'is_pause' => $request->query('is_pause'),
            'date_type' => $request->query('date_type'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $orders = $this->orderService->getDeliverymanOrderList(
            deliverymanId: (int) $deliveryman->id,
            searchValue: $args['search'],
            orderStatus: $args['status'],
            isPause: $request->isPause(),
            dateType: $args['date_type'],
            dateRange: $request->dateRange(),
            dataLimit: $limit,
            page: $offset,
            relations: self::ORDER_RELATIONS,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => OrderResource::collection($this->orderService->withResolvedBillingAddress(orders: $orders)),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($orders),
            ],
        );
    }

    #[ApiDoc(
        summary: 'One order assigned to the delivery man',
        description: 'The order with its shipping address, customer and shop.',
        tag: 'Delivery-man Orders',
        status: [401, 403, 404],
        guard: 'deliveryman',
    )]
    public function find(Request $request, int|string $id): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');

        $order = $this->orderService->findForDeliveryman(
            id: $id,
            deliverymanId: (int) $deliveryman->id,
            relations: self::ORDER_RELATIONS,
        );

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new OrderResource($this->orderService->withResolvedBillingAddress(orders: $order))],
        );
    }

    #[ApiDoc(
        summary: 'Items of an order assigned to the delivery man',
        description: 'Each line carries the order pause flag, the decoded variation and the product snapshot.',
        tag: 'Delivery-man Orders',
        status: [401, 403, 404],
        guard: 'deliveryman',
    )]
    public function getItems(Request $request, int|string $id): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');

        $order = $this->orderService->findForDeliveryman(
            id: $id,
            deliverymanId: (int) $deliveryman->id,
            relations: self::ITEM_RELATIONS,
        );

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $this->orderService->withResolvedBillingAddress(orders: $order->details->pluck('order')->filter());

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => OrderDetailResource::collection($order->details)],
        );
    }

    #[ApiDoc(
        summary: 'Delivery history of an order assigned to the delivery man',
        description: 'The locations the delivery man recorded for the order, oldest first.',
        tag: 'Delivery-man Orders',
        query: ['limit' => 'nullable|integer', 'offset' => 'nullable|integer'],
        status: [401, 403, 404, 422],
        guard: 'deliveryman',
    )]
    public function getDeliveryHistory(DeliveryHistoryRequest $request, int|string $id): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $order = $this->orderService->findForDeliveryman(id: $id, deliverymanId: (int) $deliveryman->id);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $histories = $this->deliveryHistoryService->getList(
            dataLimit: $limit,
            page: $offset,
            filters: ['order_id' => $order->id, 'deliveryman_id' => $deliveryman->id],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => DeliveryHistoryResource::collection($histories),
                'params' => $this->paramsFormatter([]),
                'pagination' => $this->paginateFormatter($histories),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Last recorded location of an order assigned to the delivery man',
        description: 'The newest location the delivery man recorded for the order; data is null when none was recorded.',
        tag: 'Delivery-man Orders',
        status: [401, 403, 404, 422],
        guard: 'deliveryman',
    )]
    public function getLastLocation(LastLocationRequest $request, int|string $id): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');

        $order = $this->orderService->findForDeliveryman(id: $id, deliverymanId: (int) $deliveryman->id);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $lastLocation = $this->deliveryHistoryService->findLatestBy(params: [
            'order_id' => $order->id,
            'deliveryman_id' => $deliveryman->id,
        ]);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => $lastLocation === null ? null : new DeliveryHistoryResource($lastLocation)],
        );
    }
}
