<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Shared\Order\DeliveryManAssignRequest;
use App\Http\Requests\Shared\Order\DeliveryScheduleUpdateRequest;
use App\Http\Requests\Shared\Order\PaymentStatusUpdateRequest;
use App\Http\Requests\Vendor\Order\ListRequest;
use App\Http\Resources\V6\Vendor\OrderDetailResource;
use App\Http\Resources\V6\Vendor\OrderResource;
use App\Services\New\DeliveryManService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderEditHistoryService;
use App\Services\New\OrderService;
use App\Services\New\OrderSettlementService;
use App\Services\New\ProductRetailService;
use App\Traits\PdfGenerator;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Mpdf\Mpdf;

class OrderController extends BaseApiController
{
    use PdfGenerator;

    private const LIST_RELATIONS = ['customer', 'shipping', 'deliveryMan', 'orderDetails', 'offlinePayments'];

    private const DELIVERY_PARTNER_PANEL = 'vendor';

    private const DIGITAL_PRODUCT_DIRECTORY = 'product/digital-product';

    private const PRODUCT_TYPE_DIGITAL = 'digital';

    private const DIGITAL_PRODUCT_TYPE_READY = 'ready_product';

    private const EXPECTED_DELIVERY_DATE_FIELD = 'expected_delivery_date';

    private const HISTORY_USER_TYPE_SELLER = 'seller';

    private const SOURCE_ALL = 'all';

    private const INVOICE_RELATIONS = ['details.product', 'customer', 'shipping', 'seller', 'latestEditHistory'];

    private const INVOICE_VIEW = 'vendor-views.order.invoice';

    private const INVOICE_PDF_OPTIONS = ['default_font' => 'FreeSerif', 'mode' => 'utf-8', 'format' => [190, 250], 'autoLangToFont' => true];

    private const PDF_REQUEST_FROM_VENDOR = 'vendor';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderEditHistoryService $orderEditHistoryService,
        private readonly OrderSettlementService $orderSettlementService,
        private readonly ProductRetailService $productRetailService,
        private readonly DeliveryManService $deliveryManService,
    ) {}

    #[ApiDoc(
        summary: 'Orders of the authenticated seller',
        description: 'order_current_status, payment_status, order_types and order_amount_settlement accept a list or a JSON-encoded list.',
        tag: 'Orders (Seller)',
        query: [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
            'search_value' => 'nullable|string',
            'filter' => 'nullable|string',
            'date_type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'delivery_man_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'order_current_status' => 'nullable|string',
            'payment_status' => 'nullable|string',
            'order_types' => 'nullable|string',
            'order_amount_settlement' => 'nullable|string',
        ],
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function getList(ListRequest $request): JsonResponse
    {
        $input = $request->validated();

        $orders = $this->orderService->getSellerOrderList(
            sellerId: $this->sellerId($request),
            searchValue: $input['search_value'] ?? null,
            filters: $this->getSellerOrderFilters(input: $input),
            dataLimit: (int) ($input['limit'] ?? DEFAULT_DATA_LIMIT),
            page: (int) ($input['offset'] ?? 1),
            relations: self::LIST_RELATIONS,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => OrderResource::collection($this->orderService->withResolvedBillingAddress(orders: $orders)),
                'params' => $this->paramsFormatter(array_diff_key($input, array_flip(['limit', 'offset']))),
                'pagination' => $this->paginateFormatter($orders),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Line items of one seller order, with stock, price and the delivery-partner shipment',
        tag: 'Orders (Seller)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function find(Request $request, string $id): JsonResponse
    {
        $lines = $this->orderDetailService->getSellerOrderLines(
            orderId: (int) $id,
            sellerId: $this->sellerId($request),
            relations: $this->getOrderLineRelations(),
        );

        if ($lines->isEmpty()) {
            return $this->orderNotFound();
        }

        $order = $lines->first()->order;
        $this->orderService->syncInitialOrderAmount(order: $order);

        $products = $this->getProductsById(productIds: $lines->pluck('product_id')->filter()->unique()->values()->all());
        $deliveryPartnerAvailable = deliveryPartnerAvailable(order: $order, panel: self::DELIVERY_PARTNER_PANEL);
        $courierShipment = $this->getCourierShipment(order: $order);
        $paymentHistories = $this->orderEditHistoryService->getPaymentHistory(orderId: (int) $id);

        foreach ($lines as $line) {
            $snapshot = $this->getProductSnapshot(line: $line);
            $modifiedVariation = $this->getModifiedVariation(line: $line);
            $stockAndPrice = $this->orderDetailService->getCurrentStockAndPrice(
                orderDetail: $line,
                activeProduct: $products->get($line->product_id) ?? $snapshot,
            );

            $line->setAttribute('delivery_partner_available', $deliveryPartnerAvailable);
            $line->setAttribute('courier_shipment', $courierShipment);
            $line->setAttribute('product_details', Helpers::product_data_formatting_for_json_data($snapshot));
            $line->setAttribute('modified_variation', $modifiedVariation);
            $line->setAttribute('current_stock', $stockAndPrice['current_stock']);
            $line->setAttribute('current_price', $stockAndPrice['current_price']);
            $line->setAttribute('edit_order_payment_histories', $paymentHistories);
        }

        $this->orderService->withResolvedBillingAddress(orders: $lines->pluck('order')->filter());

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => OrderDetailResource::collection($lines)],
        );
    }

    #[ApiDoc(
        summary: 'Assign one of the seller delivery men to a seller order',
        tag: 'Orders (Seller)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function assignDeliveryMan(DeliveryManAssignRequest $request, string $id): JsonResponse
    {
        $sellerId = $this->sellerId($request);
        $order = $this->orderService->findForSeller(id: (int) $id, sellerId: $sellerId);

        if ($order === null) {
            return $this->orderNotFound();
        }

        $deliveryManId = (int) $request['delivery_man_id'];

        if (! $this->deliveryManService->exists(params: ['id' => $deliveryManId, 'seller_id' => $sellerId])) {
            return $this->responseFormatter(config: config('response.delivery_man_not_found'));
        }

        if ($this->orderService->isAlreadyDelivered(order: $order)) {
            return $this->responseFormatter(config: config('response.order_delivery_man_assign_not_allowed'));
        }

        $order = $this->orderService->assignDeliveryMan(orderId: $order->id, sellerId: $sellerId, deliveryManId: $deliveryManId);

        $this->orderService->dispatchStatusNotification(
            key: OrderService::NOTIFICATION_NEW_ORDER_ASSIGNED,
            type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN,
            order: $order,
        );

        return $this->responseFormatter(config: config('response.order_delivery_man_assigned'));
    }

    #[ApiDoc(
        summary: 'Set the deliveryman charge and the expected delivery date of a seller order',
        description: 'Both columns are written on every call. A changed expected delivery date is recorded in its history and notified to the delivery man.',
        tag: 'Orders (Seller)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateDeliverySchedule(DeliveryScheduleUpdateRequest $request, string $id): JsonResponse
    {
        $sellerId = $this->sellerId($request);
        $order = $this->orderService->findForSeller(id: (int) $id, sellerId: $sellerId, relations: ['deliveryMan']);

        if ($order === null) {
            return $this->orderNotFound();
        }

        $expectedDeliveryDate = $request['expected_delivery_date'];
        $isExpectedDeliveryDateChanged = $this->orderService->hasExpectedDeliveryDateChanged(order: $order, expectedDeliveryDate: $expectedDeliveryDate);

        $isUpdated = DB::transaction(function () use ($order, $request, $sellerId, $expectedDeliveryDate, $isExpectedDeliveryDateChanged): bool {
            if ($isExpectedDeliveryDateChanged && ! $this->recordExpectedDeliveryDate(orderId: $order->id, sellerId: $sellerId, expectedDeliveryDate: $expectedDeliveryDate)) {
                return false;
            }

            return $this->orderService->updateDeliverySchedule(
                order: $order,
                deliverymanCharge: $request['deliveryman_charge'],
                expectedDeliveryDate: $expectedDeliveryDate,
            );
        });

        if (! $isUpdated) {
            return $this->responseFormatter(config: config('response.order_delivery_schedule_update_failed'));
        }

        if ($isExpectedDeliveryDateChanged) {
            $this->orderService->dispatchStatusNotification(
                key: OrderService::NOTIFICATION_EXPECTED_DELIVERY_DATE,
                type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN,
                order: $order,
            );
        }

        return $this->responseFormatter(config: config('response.order_delivery_schedule_updated'));
    }

    #[ApiDoc(
        summary: 'Mark a seller order as paid',
        description: 'A payment status can only move to paid. A cash on delivery order must be delivered first.',
        tag: 'Orders (Seller)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updatePaymentStatus(PaymentStatusUpdateRequest $request, string $id): JsonResponse
    {
        $paymentStatus = $request['payment_status'];

        if ($paymentStatus != OrderService::PAYMENT_STATUS_PAID) {
            return $this->responseFormatter(config: config('response.order_payment_status_cannot_revert'));
        }

        $order = $this->orderService->findForSeller(id: (int) $id, sellerId: $this->sellerId($request), relations: ['customer']);

        if ($order === null) {
            return $this->orderNotFound();
        }

        $blocker = $this->orderService->getPaymentStatusUpdateBlocker(order: $order, paymentStatus: $paymentStatus);

        if ($blocker !== null) {
            return $this->responseFormatter(config: config(match ($blocker) {
                OrderService::REASON_CUSTOMER_ACCOUNT_DELETED => 'response.order_payment_status_customer_deleted',
                default => 'response.order_payment_status_before_delivery',
            }));
        }

        $this->orderService->update(id: $order->id, data: ['payment_status' => $paymentStatus]);

        return $this->responseFormatter(config: config('response.order_payment_status_updated'));
    }

    #[ApiDoc(
        summary: 'Invoice PDF of a seller order as a byte array',
        description: 'data is the same invoice the vendor panel downloads, as an array of PDF bytes. The lang header sets the language and the text direction. Another seller\'s order id is order_not_found.',
        tag: 'Orders (Seller)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function getInvoice(Request $request, string $id): JsonResponse
    {
        $order = $this->orderService->findForSeller(id: (int) $id, sellerId: $this->sellerId($request), relations: self::INVOICE_RELATIONS);

        if ($order === null) {
            return $this->orderNotFound();
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => $this->renderInvoiceBytes(order: $order)],
        );
    }

    private function sellerId(Request $request): int
    {
        return (int) $request->attributes->get('seller')->id;
    }

    /** @return array<string, mixed> */
    private function getSellerOrderFilters(array $input): array
    {
        $filters = [
            OrderService::FILTER_SOURCE => $input['filter'] ?? self::SOURCE_ALL,
            OrderService::FILTER_DATE_TYPE => $input['date_type'] ?? null,
        ];

        if (isset($input['start_date'], $input['end_date'])) {
            $filters[OrderService::FILTER_DATE_RANGE] = [$input['start_date'], $input['end_date']];
        }

        foreach (['delivery_man_id', 'customer_id'] as $column) {
            if (isset($input[$column])) {
                $filters[$column] = (int) $input[$column];
            }
        }

        if (isset($input['order_current_status'])) {
            $filters['order_status'] = $input['order_current_status'];
        }

        foreach (['payment_status' => 'payment_status', 'order_types' => 'order_type'] as $field => $column) {
            if (! empty($input[$field])) {
                $filters[$column] = $input[$field];
            }
        }

        if (! empty($input['order_amount_settlement'])) {
            $filters[OrderService::FILTER_SETTLEMENT] = $input['order_amount_settlement'];
        }

        return $filters;
    }

    /** @return array<int|string, mixed> */
    private function getOrderLineRelations(): array
    {
        return [
            'order.offlinePayments',
            'order.customer',
            'order.deliveryMan',
            'productAllStatus',
            'verificationImages',
            'latestEditHistory',
            'orderEditHistory' => fn ($query) => $query->orderBy('updated_at', 'desc'),
        ];
    }

    private function getProductsById(array $productIds): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        return $this->productRetailService
            ->getList(dataLimit: 'all', filters: ['id' => $productIds])
            ->keyBy('id');
    }

    /** @return array<string, mixed> */
    private function getProductSnapshot(Model $line): array
    {
        $product = json_decode((string) $line->product_details, true) ?? [];
        $product['digital_variation'] ??= [];
        $product['thumbnail_full_url'] = $line->productAllStatus?->thumbnail_full_url;

        if ($this->isReadyDigitalProduct(product: $product)) {
            $product['digital_file_ready_full_url'] = storageLink(self::DIGITAL_PRODUCT_DIRECTORY, $product['digital_file_ready'], $product['storage_path'] ?? 'public');
        }

        return $product;
    }

    private function isReadyDigitalProduct(array $product): bool
    {
        return ($product['product_type'] ?? null) == self::PRODUCT_TYPE_DIGITAL
            && ($product['digital_product_type'] ?? null) == self::DIGITAL_PRODUCT_TYPE_READY
            && ! empty($product['digital_file_ready']);
    }

    /** @return array<int, array{key: int|string, value: mixed}> */
    private function getModifiedVariation(Model $line): array
    {
        $variation = is_array($line->variation) ? $line->variation : (json_decode((string) $line->variation, true) ?? []);
        $modifiedVariation = [];

        foreach ($variation as $variationKey => $variationValue) {
            $modifiedVariation[] = ['key' => $variationKey, 'value' => $variationValue];
        }

        return $modifiedVariation;
    }

    private function getCourierShipment(Model $order): ?array
    {
        $shipment = deliveryPartnerShipmentDetails($order->id);

        if ($shipment !== null) {
            return $shipment;
        }

        if (blank($order->delivery_service_name) && blank($order->third_party_delivery_tracking_id)) {
            return null;
        }

        return [
            'provider' => null,
            'delivery_partner' => $order->delivery_service_name,
            'consignment_id' => null,
            'tracking_code' => $order->third_party_delivery_tracking_id,
            'tracking_number' => $order->third_party_delivery_tracking_id,
            'shipment_status' => null,
            'shipment_status_label' => null,
            'status_tone' => 'neutral',
            'delivery_fee' => null,
            'tracking_url' => null,
            'dispatched_at' => null,
            'can_track' => false,
            'dispatch_details' => null,
            'can_manage' => false,
        ];
    }

    private function recordExpectedDeliveryDate(int $orderId, int $sellerId, string $expectedDeliveryDate): bool
    {
        return $this->orderSettlementService->updateAmountDate(
            request: new Fluent([
                'order_id' => $orderId,
                'field_name' => self::EXPECTED_DELIVERY_DATE_FIELD,
                'field_val' => $expectedDeliveryDate,
            ]),
            userId: $sellerId,
            userType: self::HISTORY_USER_TYPE_SELLER,
        );
    }

    /** @return array<int, int> */
    private function renderInvoiceBytes(Model $order): array
    {
        $invoiceHtml = view(self::INVOICE_VIEW, [
            ...$this->orderService->getSellerInvoiceViewData(order: $order),
            'direction' => getLanguageDirection(code: App::getLocale()),
            'lang' => getDefaultLanguage(),
        ])->render();

        $pdf = new Mpdf(self::INVOICE_PDF_OPTIONS);
        $pdf->autoScriptToLang = true;
        $pdf->autoLangToFont = true;
        $pdf->SetHTMLFooter($this->footerHtml(requestFrom: self::PDF_REQUEST_FROM_VENDOR));
        $pdf->WriteHTML($invoiceHtml);

        return array_values(unpack('C*', $pdf->Output('', 'S')));
    }

    private function orderNotFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.order_not_found'));
    }
}
