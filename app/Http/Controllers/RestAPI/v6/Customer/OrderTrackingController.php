<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Order\TrackHistoryRequest;
use App\Http\Requests\Customer\Order\TrackRequest;
use App\Http\Resources\V6\Customer\OrderDetailResource;
use App\Http\Resources\V6\Customer\OrderResource;
use App\Services\New\CustomerService;
use App\Services\New\GuestUserService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderService;
use App\Services\New\OrderStatusHistoryService;
use App\Traits\CustomerOwnerTrait;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class OrderTrackingController extends BaseApiController
{
    use CustomerOwnerTrait;

    private const PHONE_MATCH_RELATIONS = ['shippingAddress', 'billingAddress'];

    private const TRACK_LINE_RELATIONS = ['order.deliveryMan', 'order.latestEditHistory', 'order.orderEditHistory', 'verificationImages', 'seller.shop', 'product', 'latestEditHistory'];

    private const DIGITAL_PRODUCT_DIRECTORY = 'product/digital-product';

    private const PRODUCT_TYPE_DIGITAL = 'digital';

    private const DIGITAL_PRODUCT_TYPE_READY = 'ready_product';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderStatusHistoryService $orderStatusHistoryService,
        private readonly CustomerService $customerService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'Track an order by its id and phone number',
        description: 'The owner (bearer token or guest_id) may omit phone_number; anyone else must send the phone on the order.',
        tag: 'Order',
        status: [404, 422],
        auth: false,
        guard: 'api',
    )]
    public function track(TrackRequest $request): JsonResponse
    {
        $orderParams = $this->getTrackableOrderParams(
            request: $request,
            id: $request->input('order_id'),
            scope: ['order_type' => OrderService::ORDER_TYPE_DEFAULT],
        );

        $order = $orderParams === null ? null : $this->orderService->findBy(params: $orderParams, relations: []);

        if ($order === null) {
            return $this->orderTrackingNotFound();
        }

        $lines = $this->orderDetailService->getList(
            dataLimit: 'all',
            relations: self::TRACK_LINE_RELATIONS,
            filters: ['order_id' => $order->id],
        );

        $courierShipment = deliveryPartnerTrackingInfo($order);

        foreach ($lines as $line) {
            $line->setAttribute('courier_shipment', $courierShipment);
            $line->setAttribute('courier_tracking_url', $courierShipment['tracking_url'] ?? null);
            $line->setAttribute('product_details', $this->formatProductDetails(line: $line));
        }

        $this->orderService->withResolvedBillingAddress(orders: $lines->pluck('order')->filter());

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => OrderDetailResource::collection($lines)],
        );
    }

    #[ApiDoc(
        summary: 'Order with its delivery man and status history, newest first',
        description: 'The owner (bearer token or guest_id) may omit phone_number; anyone else must send the phone on the order.',
        tag: 'Order',
        query: ['guest_id' => 'nullable|integer', 'phone_number' => 'nullable|string'],
        status: [404, 422],
        auth: false,
        guard: 'api',
    )]
    public function getTracking(TrackHistoryRequest $request, int|string $id): JsonResponse
    {
        $orderParams = $this->getTrackableOrderParams(request: $request, id: $id);

        $order = $orderParams === null ? null : $this->orderService->findTrackingBy(params: $orderParams);

        if ($order === null) {
            return $this->orderTrackingNotFound();
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new OrderResource($this->orderService->withResolvedBillingAddress(orders: $order))],
        );
    }

    #[ApiDoc(
        summary: 'Tracking timeline of an order',
        description: 'The owner (bearer token or guest_id) may omit phone_number; anyone else must send the phone on the order.',
        tag: 'Order',
        query: ['guest_id' => 'nullable|integer', 'phone_number' => 'nullable|string'],
        status: [404, 422],
        auth: false,
        guard: 'api',
    )]
    public function getStatusHistory(TrackHistoryRequest $request, int|string $id): JsonResponse
    {
        $orderParams = $this->getTrackableOrderParams(request: $request, id: $id);

        $order = $orderParams === null ? null : $this->orderService->findBy(params: $orderParams, relations: ['orderDetails']);

        if ($order === null) {
            return $this->orderTrackingNotFound();
        }

        $timeline = $this->orderStatusHistoryService->getTrackingTimeline(
            orderId: $order->id,
            orderPlacedAt: $order->created_at,
            isOrderOnlyDigital: $this->orderService->isOnlyDigital(order: $order),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => [
                'history' => $this->addTrackingStepLabels(history: $timeline['history']),
                'is_digital_order' => $timeline['is_digital_order'],
            ]],
        );
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function getTrackableOrderParams(Request $request, mixed $id, array $scope = []): ?array
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner !== null) {
            $ownerParams = ['id' => $id, ...$scope, ...$this->customerOwnerFilters($owner)];

            if ($this->orderService->exists(params: $ownerParams)) {
                return $ownerParams;
            }
        }

        $order = $this->orderService->findBy(params: ['id' => $id, ...$scope], relations: self::PHONE_MATCH_RELATIONS);

        if ($order === null || ! $this->matchesOrderPhone(order: $order, phoneNumber: $request->input('phone_number'))) {
            return null;
        }

        return ['id' => $order->id];
    }

    private function matchesOrderPhone(Model $order, mixed $phoneNumber): bool
    {
        $phoneNumber = is_scalar($phoneNumber) ? (string) $phoneNumber : null;

        if ($order->is_guest) {
            return $this->orderService->matchesGuestPhoneExactly(order: $order, phoneNumber: $phoneNumber);
        }

        if ($phoneNumber === null || $phoneNumber === '') {
            return false;
        }

        $customer = $this->customerService->findBy(params: ['phone' => $phoneNumber]);

        return $customer !== null && (int) $customer->id === (int) $order->customer_id;
    }

    private function formatProductDetails(Model $line): mixed
    {
        $product = json_decode((string) $line->product_details, true) ?? [];

        if ($this->isReadyDigitalProduct(product: $product)) {
            $product['digital_file_ready_full_url'] = storageLink(self::DIGITAL_PRODUCT_DIRECTORY, $product['digital_file_ready'], $product['storage_path'] ?? 'public');
        }

        if (! isset($product['thumbnail_full_url']) && isset($line->product?->thumbnail_full_url)) {
            $product['thumbnail_full_url'] = $line->product->thumbnail_full_url;
        }

        return Helpers::product_data_formatting($product);
    }

    private function isReadyDigitalProduct(array $product): bool
    {
        return ($product['product_type'] ?? null) == self::PRODUCT_TYPE_DIGITAL
            && ($product['digital_product_type'] ?? null) == self::DIGITAL_PRODUCT_TYPE_READY
            && ! empty($product['digital_file_ready']);
    }

    private function addTrackingStepLabels(array $history): array
    {
        $labels = $this->getTrackingStepLabels();

        return array_map(static fn (array $step): array => [
            'key' => $step['key'],
            'label' => $labels[$step['key']] ?? null,
            'status' => $step['status'],
            'date_time' => $step['date_time'],
        ], $history);
    }

    private function getTrackingStepLabels(): array
    {
        return [
            'order_placed' => translate('Order Placed'),
            'order_confirmed' => translate('Order confirmed'),
            'preparing_for_shipment' => translate('Preparing for shipment'),
            'order_is_on_the_way' => translate('Order is on the way'),
            'order_delivered' => translate('Order Delivered'),
            'order_returned' => translate('Order returned'),
            'order_failed' => translate('Order failed'),
            'order_canceled' => translate('Order canceled'),
        ];
    }

    private function orderTrackingNotFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.order_tracking_not_found'));
    }
}
