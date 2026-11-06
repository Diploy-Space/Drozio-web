<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Order\DetailsRequest;
use App\Http\Requests\Customer\Order\FindByIdRequest;
use App\Http\Requests\Customer\Order\ListRequest;
use App\Http\Resources\V6\Customer\OrderDetailResource;
use App\Http\Resources\V6\Customer\OrderResource;
use App\Services\New\GuestUserService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewService;
use App\Traits\CustomerOwnerTrait;
use App\Traits\PdfGenerator;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Mpdf\Mpdf;

class OrderController extends BaseApiController
{
    use CustomerOwnerTrait, PdfGenerator;

    private const LIST_RELATIONS = ['details.product', 'deliveryMan', 'seller.shop', 'deliveryManReview'];

    private const FIND_RELATIONS = ['deliveryMan', 'offlinePayments', 'verificationImages', 'latestEditHistory', 'orderEditHistory'];

    private const FIND_WITH_COUNT = ['orderDetails'];

    private const ITEM_RELATIONS = ['productAllStatus', 'order.offlinePayments', 'order.deliveryMan', 'order.deliveryManReview', 'verificationImages', 'seller.shop', 'latestEditHistory'];

    private const INVOICE_RELATIONS = ['seller', 'shipping'];

    private const INVOICE_PDF_OPTIONS = ['default_font' => 'FreeSerif', 'mode' => 'utf-8', 'format' => [190, 250], 'autoLangToFont' => true];

    private const PDF_REQUEST_FROM_WEB = 'web';

    private const DIGITAL_PRODUCT_DIRECTORY = 'product/digital-product';

    private const PRODUCT_TYPE_DIGITAL = 'digital';

    private const DIGITAL_PRODUCT_TYPE_READY = 'ready_product';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly ProductRetailService $productRetailService,
        private readonly ReviewService $reviewService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'Order history of the authenticated customer',
        tag: 'Order',
        query: [
            'status' => 'nullable|string',
            'type' => 'nullable|string',
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [401, 422],
    )]
    public function getList(ListRequest $request): JsonResponse
    {
        $args = [
            'status' => $request->query('status'),
            'type' => $request->query('type'),
        ];

        $orders = $this->orderService->getCustomerAppOrderList(
            customerId: $request->user('api')->id,
            statusGroup: $args['status'],
            listType: $args['type'],
            dataLimit: (int) $request->query('limit', DEFAULT_DATA_LIMIT),
            page: (int) $request->query('offset', 1),
            relations: self::LIST_RELATIONS,
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
        summary: 'One order of the customer or guest',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Order',
        query: ['guest_id' => 'nullable|integer'],
        status: [401, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function find(FindByIdRequest $request, int|string $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $order = $this->orderService->findForCustomerOwner(
            id: $id,
            customerId: $owner['id'],
            isGuest: $owner['is_guest'],
            relations: self::FIND_RELATIONS,
            withCount: self::FIND_WITH_COUNT,
        );

        if ($order === null) {
            return $this->orderNotFound();
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new OrderResource($this->orderService->withResolvedBillingAddress(orders: $order))],
        );
    }

    #[ApiDoc(
        summary: 'Line items of an order, with the delivery-partner shipment',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Order',
        query: ['guest_id' => 'nullable|integer'],
        status: [401, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function getItems(DetailsRequest $request, int|string $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $lines = $this->orderDetailService->getOwnerOrderLines(
            orderId: $id,
            customerId: $owner['id'],
            isGuest: $owner['is_guest'],
            relations: self::ITEM_RELATIONS,
        );

        if ($lines->isEmpty()) {
            return $this->orderNotFound();
        }

        $productIds = $lines->pluck('product_id')->unique()->values()->all();
        $thumbnailUrls = $this->getThumbnailUrls(productIds: $productIds);
        $reviewsByProduct = $owner['is_guest']
            ? collect()
            : $this->getProductReviewsByProduct(customerId: $owner['id'], productIds: $productIds);
        $courierShipment = deliveryPartnerTrackingInfo($lines->first()->order);

        foreach ($lines as $line) {
            $line->setAttribute('courier_shipment', $courierShipment);
            $line->setAttribute('product_details', $this->formatProductDetails(line: $line, thumbnailUrl: $thumbnailUrls->get($line->product_id)));
            $line->setAttribute('reviewData', $reviewsByProduct->get($line->product_id, collect())
                ->last(fn (Model $review): bool => $review->order_id == $line->order_id));
        }

        $this->orderService->withResolvedBillingAddress(orders: $lines->pluck('order')->filter());

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => OrderDetailResource::collection($lines)],
        );
    }

    #[ApiDoc(
        summary: 'Invoice PDF of an order as a byte array',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Order',
        query: ['guest_id' => 'nullable|integer'],
        status: [401, 404],
        auth: false,
        guard: 'api',
    )]
    public function getInvoice(Request $request, int|string $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $order = $this->orderService->findForCustomerOwner(
            id: $id,
            customerId: $owner['id'],
            isGuest: $owner['is_guest'],
            relations: self::INVOICE_RELATIONS,
        );

        if ($order === null) {
            return $this->orderNotFound();
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => $this->renderInvoiceBytes(order: $order)],
        );
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function getThumbnailUrls(array $productIds): Collection
    {
        return $this->productRetailService
            ->getList(dataLimit: 'all', filters: ['id' => $productIds])
            ->mapWithKeys(static fn (Model $product): array => [$product->id => $product->thumbnail_full_url]);
    }

    private function getProductReviewsByProduct(int|string $customerId, array $productIds): Collection
    {
        return $this->reviewService
            ->getList(
                dataLimit: 'all',
                filters: ['customer_id' => $customerId, 'product_id' => $productIds, 'delivery_man_id' => null],
                sortBy: ReviewService::SORT_OLDEST_ID,
            )
            ->groupBy('product_id');
    }

    private function formatProductDetails(Model $line, mixed $thumbnailUrl): mixed
    {
        $product = json_decode((string) $line->product_details, true) ?? [];
        $product['thumbnail_full_url'] = $thumbnailUrl;

        if ($this->isReadyDigitalProduct(product: $product)) {
            $product['digital_file_ready_full_url'] = storageLink(self::DIGITAL_PRODUCT_DIRECTORY, $product['digital_file_ready'], $product['storage_path'] ?? 'public');
        }

        return Helpers::product_data_formatting_for_json_data($product);
    }

    private function isReadyDigitalProduct(array $product): bool
    {
        return ($product['product_type'] ?? null) == self::PRODUCT_TYPE_DIGITAL
            && ($product['digital_product_type'] ?? null) == self::DIGITAL_PRODUCT_TYPE_READY
            && ! empty($product['digital_file_ready']);
    }

    /** @return array<int, int> */
    private function renderInvoiceBytes(Model $order): array
    {
        $invoiceHtml = view(themeViewName(key: 'order_invoice'), [
            'order' => $order,
            'invoiceSettings' => getWebConfig(name: 'invoice_settings'),
            'direction' => getLanguageDirection(code: App::getLocale()),
        ])->render();

        $pdf = new Mpdf(self::INVOICE_PDF_OPTIONS);
        $pdf->autoScriptToLang = true;
        $pdf->autoLangToFont = true;
        $pdf->SetHTMLFooter($this->footerHtml(requestFrom: self::PDF_REQUEST_FROM_WEB));
        $pdf->WriteHTML($invoiceHtml);

        return array_values(unpack('C*', $pdf->Output('', 'S')));
    }

    private function orderNotFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.order_not_found'));
    }
}
