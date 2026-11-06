<?php

namespace App\Http\Controllers\Shared\Order;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Shared\OrderEdit\GenerateRequest;
use App\Http\Requests\Shared\OrderEdit\ProductListUpdateRequest;
use App\Http\Requests\Shared\OrderEdit\ProductQuickViewRequest;
use App\Http\Requests\Shared\OrderEdit\ProductRemoveRequest;
use App\Http\Requests\Shared\OrderEdit\ProductSearchRequest;
use App\Http\Requests\Shared\OrderEdit\ProductSelectionRequest;
use App\Services\New\AuthorService;
use App\Services\New\BusinessSettingService;
use App\Services\New\ColorService;
use App\Services\New\CouponService;
use App\Services\New\CustomerService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderDetailsRewardsService;
use App\Services\New\OrderEditHistoryService;
use App\Services\New\OrderEditService;
use App\Services\New\OrderService;
use App\Services\New\OrderTaxService;
use App\Services\New\OrderTransactionService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Traits\OrderEditSessionTrait;
use App\Traits\OrderEditSummaryTrait;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseOrderEditController extends BaseController
{
    use OrderEditSessionTrait;
    use OrderEditSummaryTrait;

    private const EDIT_ORDER_RELATIONS = ['details', 'latestEditHistory'];

    private const GENERATE_ORDER_RELATIONS = ['details.productAllStatus', 'latestEditHistory', 'seller.shop'];

    private const SEARCH_PRODUCT_RELATIONS = ['brand', 'category', 'seller.shop'];

    private const STOCK_PRODUCT_RELATIONS = ['retailDetail'];

    private const PRODUCT_TYPE_PHYSICAL = 'physical';

    private const PRODUCT_TYPE_DIGITAL = 'digital';

    public function __construct(
        protected readonly OrderService $orderService,
        private readonly OrderEditService $orderEditService,
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderEditHistoryService $orderEditHistoryService,
        private readonly OrderTransactionService $orderTransactionService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
        private readonly OrderTaxService $orderTaxService,
        private readonly ProductRetailService $productRetailService,
        private readonly ColorService $colorService,
        private readonly CouponService $couponService,
        private readonly CustomerService $customerService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
    ) {}

    abstract protected function findEditOrder(int|string $orderId, array $relations): ?Model;

    abstract protected function getEditOrderType(): string;

    /** @return array{edit_by: string, edited_user_id: mixed, edited_user_name: string} */
    abstract protected function getEditor(): array;

    abstract protected function getViewPrefix(): string;

    abstract protected function getOrderDetailsRouteName(): string;

    abstract protected function getAddedProductResponseData(): array;

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse|JsonResponse
    {
        return null;
    }

    public function getSearchEditOrderProductsView(ProductSearchRequest $request): JsonResponse
    {
        $order = $this->findEditOrder(orderId: $request['order_id'], relations: self::EDIT_ORDER_RELATIONS);
        $refusal = $this->getEditRefusal(order: $order);

        if ($refusal !== null) {
            return $this->getViewRefusalResponse(order: $order, message: $refusal);
        }

        $products = $this->productRetailService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: self::SEARCH_PRODUCT_RELATIONS,
            filters: $this->orderEditService->getEditableProductFilters(order: $order) + [ProductRetailService::FILTER_ACTIVE => true],
        );

        return response()->json([
            'count' => $products->count(),
            'result' => view($this->getViewPrefix().'.order.partials._search-product', [
                'products' => $this->orderEditService->addOrderedQuantityToStock(order: $order, products: $products),
                'orderId' => $request['order_id'],
            ])->render(),
        ]);
    }

    public function getEditOrderProductModalView(ProductQuickViewRequest $request): JsonResponse
    {
        $order = $this->findEditOrder(orderId: $request['order_id'], relations: self::EDIT_ORDER_RELATIONS);
        $refusal = $this->getEditRefusal(order: $order);

        if ($refusal !== null) {
            return $this->getViewRefusalResponse(order: $order, message: $refusal);
        }

        $product = $this->findQuickViewProduct(productId: $request['product_id']);

        if ($product === null) {
            return $this->getViewRefusalResponse(order: null, message: translate('Not found'));
        }

        $currentVariation = $this->orderEditService->getProductCurrentStockInfo(
            order: $order,
            product: $product,
            productList: $this->orderEditProductList(orderEditService: $this->orderEditService, order: $order),
        );
        $variantRequest = $request->all();
        $variantRequest['quantity'] = $currentVariation['current_quantity'] ?? 1;

        return response()->json([
            'htmlView' => view($this->getViewPrefix().'.order.partials._quick-view', [
                ...$this->getQuickViewDetailsData(orderId: $request['order_id'], product: $product, currentVariation: $currentVariation, variantRequest: $variantRequest),
                'imageSources' => $this->getQuickViewImageSources(product: $product),
                ...$this->getDigitalProductContributorNames(product: $product),
            ])->render(),
        ]);
    }

    public function checkProductVariantPrice(ProductSelectionRequest $request): JsonResponse
    {
        $order = $this->findEditOrder(orderId: $request['order_id'], relations: self::EDIT_ORDER_RELATIONS);
        $refusal = $this->getEditRefusal(order: $order);

        if ($refusal !== null) {
            return $this->getViewRefusalResponse(order: $order, message: $refusal);
        }

        $product = $this->findQuickViewProduct(productId: $request['product_id']);

        if ($product === null) {
            return $this->getViewRefusalResponse(order: null, message: translate('Not found'));
        }

        $productList = $this->orderEditProductList(orderEditService: $this->orderEditService, order: $order);
        $currentVariation = $this->orderEditService->getProductCurrentStockInfo(
            order: $order,
            product: $product,
            productList: $productList,
            colorName: $this->findColor(code: $request['color'])?->name,
            choiceValues: $request->getChoiceValues(),
        );

        return response()->json([
            'currentVariation' => $currentVariation,
            'product_quick_view_details' => view(
                $this->getViewPrefix().'.order.partials._quick-view-details',
                $this->getQuickViewDetailsData(orderId: $request['order_id'], product: $product, currentVariation: $currentVariation, variantRequest: $request->all()),
            )->render(),
            'product_list_view' => $this->renderEditProductList(order: $order, productList: $productList),
            'edit_order_total_amount' => $this->formatEditOrderTotal(order: $order, productList: $productList),
        ]);
    }

    public function addEditOrderProduct(ProductSelectionRequest $request): JsonResponse
    {
        $order = $this->findEditOrder(orderId: $request['order_id'], relations: self::EDIT_ORDER_RELATIONS);
        $refusal = $this->getEditRefusal(order: $order);

        if ($refusal !== null) {
            return $this->getWriteRefusalResponse(message: $refusal);
        }

        $product = $this->productRetailService->find(id: $request['product_id'], relations: $this->orderEditService->getEditableProductRelations());

        if ($product === null) {
            return $this->getWriteRefusalResponse(message: translate('Not found'));
        }

        $result = $this->orderEditService->addOrUpdateProductInOrderSession(
            order: $order,
            product: $product,
            productList: $this->orderEditProductList(orderEditService: $this->orderEditService, order: $order),
            color: $this->findColor(code: $request['color']),
            orderId: $request['order_id'],
            variant: $request['variant'],
            quantity: $request['quantity'],
            choiceValues: $request->getChoiceValues(),
        );
        $this->storeOrderEditProductList(orderEditService: $this->orderEditService, order: $order, productList: $result['product_list']);

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'],
            ...$this->getAddedProductResponseData(),
            'product_list_view' => $this->renderEditProductList(order: $order, productList: $result['product_list']),
            'edit_order_total_amount' => $this->formatEditOrderTotal(order: $order, productList: $result['product_list']),
        ]);
    }

    public function removeEditOrderProduct(ProductRemoveRequest $request): JsonResponse
    {
        $order = $this->findEditOrder(orderId: $request['order_id'], relations: self::EDIT_ORDER_RELATIONS);
        $refusal = $this->getEditRefusal(order: $order);

        if ($refusal !== null) {
            return $this->getWriteRefusalResponse(message: $refusal);
        }

        $result = $this->orderEditService->removeProductInOrderSession(
            productList: $this->orderEditProductList(orderEditService: $this->orderEditService, order: $order),
            productId: $request['product_id'],
            variant: $request['variant'],
        );

        return $this->storeProductListResponse(order: $order, result: $result);
    }

    public function updateEditOrderProductList(ProductListUpdateRequest $request): JsonResponse
    {
        $order = $this->findEditOrder(orderId: $request['order_id'], relations: self::EDIT_ORDER_RELATIONS);
        $refusal = $this->getEditRefusal(order: $order);

        if ($refusal !== null) {
            return $this->getWriteRefusalResponse(message: $refusal);
        }

        $result = $this->orderEditService->updateProductListInOrderSession(
            productList: $this->orderEditProductList(orderEditService: $this->orderEditService, order: $order),
            requestedProducts: $request['products'],
        );

        return $this->storeProductListResponse(order: $order, result: $result);
    }

    public function generateEditOrderByProductList(GenerateRequest $request): JsonResponse
    {
        $order = $this->findEditOrder(orderId: $request['order_id'], relations: self::GENERATE_ORDER_RELATIONS);
        $refusal = $this->getEditRefusal(order: $order);

        if ($refusal !== null) {
            return $this->getGenerateResponse(orderId: $request['order_id'], status: OrderEditService::STATUS_ERROR, message: $refusal);
        }

        $editor = $this->getEditor();
        $productList = $this->orderEditProductList(orderEditService: $this->orderEditService, order: $order);

        if ($this->orderEditService->hasOutOfStockEditedProduct(order: $order, editedProducts: $productList, products: $this->getEditedProducts(productList: $productList))) {
            return $this->getGenerateResponse(orderId: $order['id'], status: OrderEditService::STATUS_ERROR, message: translate('Out of Stock'));
        }

        $summary = $this->getEditOrderSummary(order: $order, editedProducts: $productList, editor: $editor);
        $seller = $order['seller_is'] === OrderService::SELLER_IS_SELLER ? $order->seller : null;
        $minimumOrderAmount = $this->orderEditService->checkEditMinimumOrderAmount(
            order: $order,
            totalCartAmount: $summary['coupon_summary']['total_cart_amount'],
            seller: $seller,
        );

        if ($minimumOrderAmount['status'] === OrderEditService::STATUS_ERROR) {
            return $this->getGenerateResponse(
                orderId: $order['id'],
                status: OrderEditService::STATUS_ERROR,
                message: translate('The minimum order amount for :shop is :amount', ['amount' => setCurrencySymbol(amount: usdToDefaultCurrency(amount: $minimumOrderAmount['minimum_order_amount'])), 'shop' => $minimumOrderAmount['shop_name']]),
            );
        }

        $deliveredLoyaltyPoints = $this->getDeliveredLoyaltyPoints(order: $order);
        $customer = $order['is_guest'] ? null : $this->customerService->find(id: $order['customer_id']);

        if ($this->orderEditService->hasRedeemedLoyaltyPointsBeyondBalance(order: $order, customer: $customer, deliveredLoyaltyPoints: $deliveredLoyaltyPoints)) {
            return $this->getGenerateResponse(
                orderId: $order['id'],
                status: OrderEditService::STATUS_ERROR,
                message: translate('This order cannot be modified because').number_format($deliveredLoyaltyPoints, 2).translate('Loyalty points have already been redeemed.').' '.translate('Need the redeemed points before making any changes to the order.'),
            );
        }

        DB::transaction(function () use ($order, $summary, $productList, $editor): void {
            $this->orderTaxService->deleteOrderTaxesForOrder(orderId: $order['id']);
            $this->orderTaxService->createEditedOrderTaxes(order: $order, taxConfig: OrderEditService::getTaxSystemType(), taxSummary: $summary['tax_summary']);
            $this->orderService->updateWhere(params: ['id' => $order['id']], data: $summary['order_data']);
            $this->replaceOrderLines(order: $order, editedProducts: $productList);
            $this->orderEditHistoryService->create(data: $this->orderEditService->buildEditHistoryData(order: $order, editSummary: $summary, editor: $editor));
            $this->orderTransactionService->updateForEditedOrder(order: $order, orderAmount: $summary['order_amount'], orderData: $summary['order_data']);
        });

        $this->orderService->dispatchEditNotifications(order: $order, seller: $seller, editedBy: $editor['edit_by']);

        return $this->getGenerateResponse(orderId: $order['id'], status: OrderEditService::STATUS_SUCCESS, message: translate('Order edited successfully'));
    }

    private function getGenerateResponse(int|string $orderId, string $status, string $message): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'redirect_url' => route($this->getOrderDetailsRouteName(), ['id' => $orderId]),
        ]);
    }

    private function getEditRefusal(?Model $order): ?string
    {
        if ($order === null) {
            return translate('Not found');
        }

        $editability = $this->orderEditService->checkIsOrderEditable(order: $order, type: $this->getEditOrderType());

        return $editability['status'] ? null : $editability['message'];
    }

    private function getViewRefusalResponse(?Model $order, string $message): JsonResponse
    {
        return response()->json(
            ['status' => OrderEditService::STATUS_ERROR, 'message' => $message],
            $order === null ? Response::HTTP_NOT_FOUND : Response::HTTP_FORBIDDEN,
        );
    }

    private function getWriteRefusalResponse(string $message): JsonResponse
    {
        return response()->json(['status' => OrderEditService::STATUS_ERROR, 'message' => $message]);
    }

    private function findQuickViewProduct(int|string $productId): ?Model
    {
        return $this->productRetailService->find(
            id: $productId,
            relations: ['brand', 'category', 'rating', 'tags', 'digitalVariation', 'clearanceSale' => fn ($query) => $query->active()],
            withCount: ['reviews'],
        );
    }

    private function findColor(?string $code): ?Model
    {
        return $code === null ? null : $this->colorService->findBy(params: ['code' => $code]);
    }

    private function getQuickViewDetailsData(int|string $orderId, Model $product, array $currentVariation, array $variantRequest): array
    {
        return [
            'orderId' => $orderId,
            'product' => $product,
            'variantRequest' => $variantRequest,
            'currentVariation' => $currentVariation,
            'quantityBounds' => $this->orderEditService->getQuantityBounds(
                minimum: $product['minimum_order_qty'] ?? 1,
                maximum: $currentVariation['current_stock'],
                requested: $variantRequest['quantity'] ?? null,
            ),
        ];
    }

    private function getQuickViewImageSources(Model $product): mixed
    {
        $hasColorImages = $product['product_type'] === self::PRODUCT_TYPE_PHYSICAL
            && ! empty($product->retailDetail?->color_image)
            && count($product->color_images_full_url) > 0;

        return $hasColorImages ? $product->color_images_full_url : $product->images_full_url;
    }

    /** @return array{selectedAuthorNames: array<int, string>, selectedPublisherNames: array<int, string>} */
    private function getDigitalProductContributorNames(Model $product): array
    {
        if ($product['product_type'] !== self::PRODUCT_TYPE_DIGITAL) {
            return ['selectedAuthorNames' => [], 'selectedPublisherNames' => []];
        }

        return [
            'selectedAuthorNames' => $this->authorService->getList(dataLimit: 'all')
                ->whereIn('id', $this->productRetailService->getAuthorsInfo(product: $product)['ids'])
                ->pluck('name')
                ->toArray(),
            'selectedPublisherNames' => $this->publishingHouseService->getList(dataLimit: 'all')
                ->whereIn('id', $this->productRetailService->getPublishingHouseInfo(product: $product)['ids'])
                ->pluck('name')
                ->toArray(),
        ];
    }

    /** @param  array<string, array<string, mixed>>  $productList */
    private function renderEditProductList(Model $order, array $productList): string
    {
        return view($this->getViewPrefix().'.order.partials.offcanvas._edit-order-products-list', [
            'order' => $order,
            'orderProductsSession' => ['product_list' => $productList],
            'editProductQuantities' => $this->orderEditService->getProductListQuantityBounds(productList: $productList),
        ])->render();
    }

    /** @param  array<string, array<string, mixed>>  $productList */
    private function formatEditOrderTotal(Model $order, array $productList): string
    {
        $summary = $this->getEditOrderSummary(order: $order, editedProducts: $productList, editor: $this->getEditor());

        return setCurrencySymbol(amount: usdToDefaultCurrency(amount: $summary['order_amount']), currencyCode: getCurrencyCode());
    }

    /** @param  array{status: string, message: string, product_list: array<string, array<string, mixed>>}  $result */
    private function storeProductListResponse(Model $order, array $result): JsonResponse
    {
        $this->storeOrderEditProductList(orderEditService: $this->orderEditService, order: $order, productList: $result['product_list']);

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'],
            'product_list_view' => $this->renderEditProductList(order: $order, productList: $result['product_list']),
            'edit_order_total_amount' => $this->formatEditOrderTotal(order: $order, productList: $result['product_list']),
        ]);
    }

    /** @param  array<string, array<string, mixed>>  $productList */
    private function getEditedProducts(array $productList): Collection
    {
        return $this->productRetailService->getList(
            dataLimit: 'all',
            relations: self::STOCK_PRODUCT_RELATIONS,
            filters: ['id' => collect($productList)->pluck('product_id')->unique()->values()->all()],
        );
    }

    /** @param  array<string, array<string, mixed>>  $editedProducts */
    private function replaceOrderLines(Model $order, array $editedProducts): void
    {
        if ($editedProducts === []) {
            return;
        }

        foreach ($order->details as $orderDetail) {
            $this->productRetailService->adjustStockForEditedLine(productId: $orderDetail['product_id'], variant: $orderDetail['variant'], quantityChange: (int) $orderDetail['qty']);
        }

        $editedAt = Carbon::now();
        $keptLines = [];
        $totalPrice = 0;

        foreach ($editedProducts as $editedProduct) {
            if (empty($editedProduct['product_id'])) {
                continue;
            }

            $orderDetail = $this->orderDetailService->upsertEditedLine(orderId: $order['id'], item: $editedProduct, appliedTaxAmount: 0, editedAt: $editedAt);
            $lineAmount = $this->orderEditService->calculateEditedLineAmount(editedProduct: $editedProduct);
            $keptLines[] = ['order_details_id' => $orderDetail->id, 'price' => $lineAmount];
            $totalPrice += $lineAmount;
        }

        $keptLineIds = array_column($keptLines, 'order_details_id');

        if ($keptLineIds !== []) {
            $this->orderDetailsRewardsService->deleteExceptOrderDetails(orderId: $order['id'], keepIds: $keptLineIds);
            $this->orderDetailService->deleteLinesExcept(orderId: $order['id'], keepIds: $keptLineIds);
        }

        foreach ($this->orderDetailService->getList(dataLimit: 'all', filters: ['order_id' => $order['id']]) as $orderDetail) {
            $this->productRetailService->adjustStockForEditedLine(productId: $orderDetail['product_id'], variant: $orderDetail['variant'], quantityChange: -(int) $orderDetail['qty']);
        }

        $this->createLineRewards(order: $order, keptLines: $keptLines, totalPrice: $totalPrice);
    }

    /** @param  array<int, array{order_details_id: int, price: float|int}>  $keptLines */
    private function createLineRewards(Model $order, array $keptLines, float|int $totalPrice): void
    {
        $coupon = empty($order['coupon_code'])
            ? null
            : $this->couponService->findBy(params: ['code' => $order['coupon_code'], 'status' => CouponService::STATUS_ACTIVE]);
        $loyaltyPointSettings = $this->businessSettingService->getValuesByTypes(types: OrderDetailsRewardsService::LOYALTY_POINT_SETTING_KEYS);

        foreach ($keptLines as $keptLine) {
            $this->orderDetailsRewardsService->createForOrderDetail(
                orderId: $order['id'],
                orderDetailsId: $keptLine['order_details_id'],
                price: $keptLine['price'],
                totalPrice: $totalPrice,
                couponCode: $order['coupon_code'],
                couponDiscount: $order['discount_amount'] ?? 0,
                coupon: $coupon,
                loyaltyPointSettings: $loyaltyPointSettings,
            );
        }
    }

    private function getDeliveredLoyaltyPoints(Model $order): float|int
    {
        if ($order['is_guest']) {
            return 0;
        }

        return $this->orderDetailsRewardsService->getList(
            dataLimit: 'all',
            filters: ['order_id' => $order['id'], 'reward_delivered' => 1, 'reward_type' => OrderDetailsRewardsService::REWARD_TYPE_LOYALTY_POINT],
        )->sum('reward_amount');
    }
}
