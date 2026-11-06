<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Enums\BusinessModule;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\OrderEdit\CashOnDeliveryRequest;
use App\Http\Requests\Vendor\OrderEdit\SubmitRequest;
use App\Http\Requests\Vendor\OrderEdit\ValidateRequest;
use App\Http\Resources\V6\Vendor\OrderResource;
use App\Http\Resources\V6\Vendor\ProductListResource;
use App\Services\New\AuthorService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CartService;
use App\Services\New\CategoryService;
use App\Services\New\Clearance\StockClearanceProductService;
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
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class OrderEditController extends BaseApiController
{
    private const EDIT_ORDER_RELATIONS = ['details.productAllStatus', 'verificationImages', 'shipping', 'seller.shop', 'offlinePayments', 'deliveryMan', 'latestEditHistory', 'customer'];

    private const EDITED_ORDER_RELATIONS = ['details', 'latestEditHistory'];

    private const PRODUCT_TYPE_PHYSICAL = 'physical';

    private const SHIPPING_TYPE_ORDER_WISE = 'order_wise';

    private const ORDER_EDIT_ERROR_CODE = 'order_edit';

    private const CATALOGUE_QUERY_FIELDS = [
        'limit', 'offset', 'search', 'offer_type', 'product_type', 'product_types', 'brand_ids', 'category_ids',
        'filter_category_ids', 'filter_sub_category_ids', 'filter_sub_sub_category_ids', 'status', 'product_status',
        'request_status', 'filter_sort_by', 'min_price', 'max_price', 'currency_code', 'start_date', 'end_date',
        'publishing_house_ids', 'author_ids',
    ];

    private const CATALOGUE_LIST_FIELDS = [
        'product_types', 'brand_ids', 'category_ids', 'filter_category_ids', 'filter_sub_category_ids',
        'filter_sub_sub_category_ids', 'product_status', 'publishing_house_ids', 'author_ids',
    ];

    private const CATALOGUE_QUERY_DOC = [
        'limit' => 'nullable|integer',
        'offset' => 'nullable|integer',
        'search' => 'nullable|string',
        'offer_type' => 'nullable|string',
        'product_type' => 'nullable|string',
        'product_types' => 'nullable|json',
        'brand_ids' => 'nullable|json',
        'category_ids' => 'nullable|json',
        'filter_category_ids' => 'nullable|json',
        'filter_sub_category_ids' => 'nullable|json',
        'filter_sub_sub_category_ids' => 'nullable|json',
        'status' => 'nullable|integer',
        'product_status' => 'nullable|json',
        'request_status' => 'nullable|integer',
        'filter_sort_by' => 'nullable|string',
        'min_price' => 'nullable|numeric',
        'max_price' => 'nullable|numeric',
        'currency_code' => 'nullable|string',
        'start_date' => 'nullable|string',
        'end_date' => 'nullable|string',
        'publishing_house_ids' => 'nullable|json',
        'author_ids' => 'nullable|json',
    ];

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderEditService $orderEditService,
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderEditHistoryService $orderEditHistoryService,
        private readonly OrderTransactionService $orderTransactionService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
        private readonly OrderTaxService $orderTaxService,
        private readonly ProductRetailService $productService,
        private readonly ColorService $colorService,
        private readonly CouponService $couponService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly ShippingMethodService $shippingMethodService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly CartService $cartService,
        private readonly CustomerService $customerService,
        private readonly CategoryService $categoryService,
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly StockClearanceProductService $stockClearanceProductService,
    ) {}

    #[ApiDoc(
        summary: 'Recalculate a seller order for an edited item list without saving it',
        description: 'products is the full item list after the edit: id, quantity, variant, color and choice per line. Returns the order columns, due or return amount, tax and coupon summaries that submitting the same list would write.',
        tag: 'Order Edit (Seller)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function validateEdit(ValidateRequest $request, string $id): JsonResponse
    {
        $seller = $request->attributes->get('seller');
        $order = $this->orderService->findForSeller(id: (int) $id, sellerId: (int) $seller->id, relations: self::EDIT_ORDER_RELATIONS);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $editability = $this->orderEditService->checkIsOrderEditable(order: $order, type: OrderEditService::TYPE_VENDOR);

        if (! $editability['status']) {
            return $this->editNotAllowed(message: $editability['message']);
        }

        $calculation = $this->calculateEdit(order: $order, requestedProducts: $request['products'], editor: $this->getEditor(seller: $seller));

        if ($calculation['reason'] !== null) {
            return $this->editRefused(reason: $calculation['reason']);
        }

        $summary = $calculation['summary'];

        return $this->responseFormatter(
            config: config('response.order_edit_calculated'),
            content: [
                'data' => [
                    'order_data' => $summary['order_data'],
                    'order_amount' => $summary['order_amount'],
                    'edit_due_amount' => $summary['edit_due_amount'],
                    'order_return_amount' => $summary['order_return_amount'],
                    'tax_summary' => $summary['tax_summary'],
                    'coupon_summary' => $summary['coupon_summary'],
                ],
            ],
        );
    }

    #[ApiDoc(
        summary: 'Save an edited item list on a seller order',
        description: 'Recalculates the order exactly as the validate endpoint does, then rewrites the items, stock, taxes, rewards and transaction, records the edit history with the due or return amount and notifies the customer.',
        tag: 'Order Edit (Seller)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function submitEdit(SubmitRequest $request, string $id): JsonResponse
    {
        $seller = $request->attributes->get('seller');
        $sellerId = (int) $seller->id;
        $order = $this->orderService->findForSeller(id: (int) $id, sellerId: $sellerId, relations: self::EDIT_ORDER_RELATIONS);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $editability = $this->orderEditService->checkIsOrderEditable(order: $order, type: OrderEditService::TYPE_VENDOR);

        if (! $editability['status']) {
            return $this->editNotAllowed(message: $editability['message']);
        }

        $editor = $this->getEditor(seller: $seller);
        $calculation = $this->calculateEdit(order: $order, requestedProducts: $request['products'], editor: $editor);

        if ($calculation['reason'] !== null) {
            return $this->editRefused(reason: $calculation['reason']);
        }

        $summary = $calculation['summary'];
        $minimumOrderAmount = $this->orderEditService->checkEditMinimumOrderAmount(
            order: $order,
            totalCartAmount: $summary['coupon_summary']['total_cart_amount'],
            seller: $order->seller,
        );

        if ($minimumOrderAmount['status'] === OrderEditService::STATUS_ERROR) {
            return $this->responseFormatter(
                config: config('response.order_edit_minimum_order_amount_not_met'),
                errors: [['error_code' => self::ORDER_EDIT_ERROR_CODE, 'message' => translate('Please complete the minimum order amount for :shop', ['shop' => $minimumOrderAmount['shop_name']])]],
            );
        }

        $deliveredLoyaltyPoints = $this->getDeliveredLoyaltyPoints(order: $order);
        $customer = $order['is_guest'] ? null : $this->customerService->find(id: $order['customer_id']);

        if ($this->orderEditService->hasRedeemedLoyaltyPointsBeyondBalance(order: $order, customer: $customer, deliveredLoyaltyPoints: $deliveredLoyaltyPoints)) {
            return $this->responseFormatter(
                config: config('response.order_edit_loyalty_points_redeemed'),
                errors: [['error_code' => self::ORDER_EDIT_ERROR_CODE, 'message' => translate('This order cannot be modified because :points loyalty points have already been redeemed', ['points' => number_format($deliveredLoyaltyPoints, 2)])]],
            );
        }

        DB::transaction(function () use ($order, $summary, $calculation, $editor): void {
            $this->orderTaxService->deleteOrderTaxesForOrder(orderId: $order->id);
            $this->orderTaxService->createEditedOrderTaxes(order: $order, taxConfig: $this->orderEditService::getTaxSystemType(), taxSummary: $summary['tax_summary']);

            $this->orderService->updateWhere(params: ['id' => $order->id], data: $summary['order_data']);

            $this->replaceOrderLines(order: $order, editedProducts: $calculation['edited_products']);

            $this->orderEditHistoryService->create(data: $this->orderEditService->buildEditHistoryData(order: $order, editSummary: $summary, editor: $editor));
            $this->orderTransactionService->updateForEditedOrder(order: $order, orderAmount: $summary['order_amount'], orderData: $summary['order_data']);
            $this->orderService->dispatchEditNotifications(order: $order, seller: $order->seller, editedBy: OrderEditService::EDITOR_SELLER);
        });

        $editedOrder = $this->orderService->findForSeller(id: $order->id, sellerId: $sellerId, relations: self::EDITED_ORDER_RELATIONS);

        return $this->responseFormatter(
            config: config('response.order_edit_updated'),
            content: ['data' => OrderResource::make($this->orderService->withResolvedBillingAddress(orders: $editedOrder))->resolve()],
        );
    }

    #[ApiDoc(
        summary: 'Collect the due amount of an edited seller order as cash on delivery',
        description: 'Switches the latest edit only, and only while its due amount is unpaid, not already cash on delivery and the order is not delivered.',
        tag: 'Order Edit (Seller)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function switchDueToCashOnDelivery(CashOnDeliveryRequest $request, string $id): JsonResponse
    {
        $order = $this->orderService->findForSeller(id: (int) $id, sellerId: (int) $request->attributes->get('seller')->id);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $latestEditHistory = $this->orderEditHistoryService->findLatestForOrder(orderId: $order->id);
        $reason = $this->orderService->getEditDueCashOnDeliverySwitchBlocker(
            order: $order,
            latestEditHistory: $latestEditHistory,
            dueAmount: $order['edit_due_amount'],
        );

        if ($reason !== null) {
            return $this->editNotAllowed(message: match ($reason) {
                OrderService::REASON_ALREADY_DELIVERED => translate('Order is already delivered.'),
                OrderService::REASON_EDIT_HISTORY_ABSENT => translate('No edit history found'),
                OrderService::REASON_EDIT_DUE_ALREADY_CASH_ON_DELIVERY => translate('The due amount is already set to cash on delivery'),
                default => translate('Order already paid'),
            });
        }

        $this->orderEditHistoryService->updateDuePaymentMethod(
            id: $latestEditHistory->id,
            paymentMethod: OrderEditHistoryService::DUE_PAYMENT_METHOD_CASH_ON_DELIVERY,
        );

        return $this->responseFormatter(config: config('response.order_edit_due_switched_to_cash_on_delivery'));
    }

    #[ApiDoc(
        summary: 'Products of the authenticated seller to pick from while editing one of their orders',
        description: 'Same list and filters as all products; current_stock adds back the quantity this order already holds of each product. JSON list parameters accept a JSON array string.',
        tag: 'Order Edit (Seller)',
        query: self::CATALOGUE_QUERY_DOC,
        status: [401, 404],
        guard: 'seller',
    )]
    public function getEditableProducts(Request $request, string $id): JsonResponse
    {
        $sellerId = (int) $request->attributes->get('seller')->id;
        $order = $this->orderService->findForSeller(id: (int) $id, sellerId: $sellerId, relations: ['details']);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $orderedQuantities = collect($order->details)
            ->groupBy('product_id')
            ->map(fn ($lines) => $lines->sum('qty'))
            ->all();

        return $this->catalogueResponse(request: $request, sellerId: $sellerId, orderedQuantities: $orderedQuantities);
    }

    #[ApiDoc(
        summary: 'Every product of the authenticated seller with stock, variations and tax, filtered and paginated',
        description: 'JSON list parameters accept a JSON array string.',
        tag: 'Order Edit (Seller)',
        query: self::CATALOGUE_QUERY_DOC,
        status: [401],
        guard: 'seller',
    )]
    public function getAllProducts(Request $request): JsonResponse
    {
        return $this->catalogueResponse(request: $request, sellerId: (int) $request->attributes->get('seller')->id, orderedQuantities: null);
    }

    /** @return array{reason: ?string, summary: ?array<string, mixed>, edited_products: array<string, array<string, mixed>>} */
    private function calculateEdit(Model $order, array $requestedProducts, array $editor): array
    {
        $products = $this->getEditProducts(requestedProducts: $requestedProducts);
        $colors = $this->colorService->getList(filters: ['code' => collect($requestedProducts)->pluck('color')->filter()->values()->all()]);

        $built = $this->orderEditService->buildEditedProductList(requestedProducts: $requestedProducts, order: $order, products: $products, colors: $colors);
        $editedProducts = $built['product_list'];

        if ($built['reason'] === null && $this->orderEditService->hasOutOfStockEditedProduct(order: $order, editedProducts: $editedProducts, products: $products)) {
            $built['reason'] = OrderEditService::REASON_OUT_OF_STOCK;
        }

        if ($built['reason'] !== null) {
            return ['reason' => $built['reason'], 'summary' => null, 'edited_products' => $editedProducts];
        }

        $coupon = $this->couponService->findApplicableForEditedOrder(order: $order);
        $shippingCost = $this->calculateShippingCost(order: $order, editedProducts: $editedProducts);
        $seller = $order->seller;
        $inHouseFreeDeliveryStatus = $this->businessSettingService
            ->findBy(params: ['type' => BusinessSettingService::IN_HOUSE_FREE_DELIVERY_SETTINGS[BusinessModule::RETAIL]['status']])?->value;

        $couponSummary = $this->orderEditService->calculateEditCouponSummary(order: $order, editedProducts: $editedProducts, coupon: $coupon, shippingCost: $shippingCost);
        $freeDelivery = $this->orderEditService->calculateEditFreeDelivery(order: $order, editedProducts: $editedProducts, shippingCost: $shippingCost, seller: $seller, inHouseFreeDeliveryStatus: $inHouseFreeDeliveryStatus);
        $amountData = $this->orderEditService->calculateEditAmountData(order: $order, couponSummary: $couponSummary, freeDelivery: $freeDelivery, shippingCost: $shippingCost);
        $taxLineBases = $this->orderEditService->getEditTaxLineBases(editedProducts: $editedProducts, amountData: $amountData);

        $taxSummary = $this->orderEditService->buildEditTaxSummary(
            editedProducts: $editedProducts,
            summaryData: $editor + $amountData,
            taxLineBases: $taxLineBases,
            lineTaxes: $this->calculateLineTaxes(editedProducts: $editedProducts, taxLineBases: $taxLineBases),
        );

        $summary = $this->orderEditService->buildEditOrderSummary(
            order: $order,
            editedProducts: $editedProducts,
            couponSummary: $couponSummary,
            freeDelivery: $freeDelivery,
            taxSummary: $taxSummary,
            adminCommission: $this->orderEditService->calculateEditAdminCommission(order: $order, seller: $seller, totalCartAmount: $couponSummary['total_cart_amount']),
        );

        return ['reason' => null, 'summary' => $summary, 'edited_products' => $editedProducts];
    }

    private function getEditProducts(array $requestedProducts): Collection
    {
        return $this->productService->getList(
            dataLimit: 'all',
            relations: $this->orderEditService->getEditOrderProductRelations(),
            filters: ['id' => collect($requestedProducts)->pluck('id')->unique()->values()->all()],
        );
    }

    /** @param array<string, array<string, mixed>> $editedProducts */
    private function calculateShippingCost(Model $order, array $editedProducts): float|int
    {
        $shippingSellerId = $this->orderEditService->getEditShippingTypeSellerId(order: $order);
        $shippingType = $shippingSellerId === null ? null : $this->shippingTypeService->resolveTypeForSeller(sellerId: $shippingSellerId);

        $productShippingCosts = [];
        foreach ($editedProducts as $index => $editedProduct) {
            $productShippingCosts[$index] = $editedProduct['product_type'] == self::PRODUCT_TYPE_PHYSICAL
                ? $this->shippingTypeService->resolveProductShippingCost(product: $editedProduct['active_product'], quantity: $editedProduct['qty'])
                : 0;
        }

        return $this->orderEditService->calculateEditShippingCost(
            shippingType: $shippingType,
            orderWiseMethodCost: $shippingType === self::SHIPPING_TYPE_ORDER_WISE ? $this->shippingMethodService->find(id: $order['shipping_method_id'])?->cost : null,
            order: $order,
            productShippingCosts: $productShippingCosts,
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $editedProducts
     * @param  array<string, array<string, mixed>>  $taxLineBases
     * @return array<string, array<string, mixed>>
     */
    private function calculateLineTaxes(array $editedProducts, array $taxLineBases): array
    {
        $taxConfig = $this->orderEditService::getTaxSystemType();
        $lineTaxes = [];

        foreach ($taxLineBases as $index => $taxLineBase) {
            $product = $editedProducts[$index]['active_product'];

            $lineTaxes[$index] = [
                'applied_tax_amount' => $this->cartService->getAppliedTaxAmount(
                    product: $product,
                    taxConfig: $taxConfig,
                    totalDiscountedPrice: $taxLineBase['total_discounted_price'],
                    appliedDiscountedAmount: $taxLineBase['applied_discounted_amount'],
                ),
                'applied_tax_ids' => $this->cartService->getAppliedTaxIds(product: $product, taxConfig: $taxConfig),
                'applied_shipping_tax_amount' => $this->cartService->getAppliedShippingTaxAmount(
                    taxConfig: $taxConfig,
                    totalShippingCost: $taxLineBase['total_shipping_cost'],
                    totalDiscountedPrice: $taxLineBase['total_discounted_price'],
                    applicableShippingAmount: $taxLineBase['applicable_shipping_amount'],
                ),
                'applied_shipping_tax_ids' => $this->cartService->getAppliedShippingTaxIds(taxConfig: $taxConfig),
            ];
        }

        return $lineTaxes;
    }

    /** @param array<string, array<string, mixed>> $editedProducts */
    private function replaceOrderLines(Model $order, array $editedProducts): void
    {
        foreach ($order->details as $orderDetail) {
            $this->productService->adjustStockForEditedLine(productId: $orderDetail['product_id'], variant: $orderDetail['variant'], quantityChange: (int) $orderDetail['qty']);
        }

        $editedAt = Carbon::now();
        $keptLineIds = [];
        $keptLines = [];
        $totalPrice = 0;

        foreach ($editedProducts as $editedProduct) {
            if (empty($editedProduct['product_id'])) {
                continue;
            }

            $orderDetail = $this->orderDetailService->upsertEditedLine(orderId: $order->id, item: $editedProduct, appliedTaxAmount: 0, editedAt: $editedAt);
            $lineAmount = $this->orderEditService->calculateEditedLineAmount(editedProduct: $editedProduct);

            $keptLineIds[] = $orderDetail->id;
            $keptLines[] = ['order_details_id' => $orderDetail->id, 'price' => $lineAmount];
            $totalPrice += $lineAmount;
        }

        if ($keptLineIds !== []) {
            $this->orderDetailsRewardsService->deleteExceptOrderDetails(orderId: $order->id, keepIds: $keptLineIds);
            $this->orderDetailService->deleteLinesExcept(orderId: $order->id, keepIds: $keptLineIds);
        }

        foreach ($this->orderDetailService->getList(dataLimit: 'all', filters: ['order_id' => $order->id]) as $orderDetail) {
            $this->productService->adjustStockForEditedLine(productId: $orderDetail['product_id'], variant: $orderDetail['variant'], quantityChange: -(int) $orderDetail['qty']);
        }

        $this->createLineRewards(order: $order, keptLines: $keptLines, totalPrice: $totalPrice);
    }

    /** @param array<int, array{order_details_id: int, price: float|int}> $keptLines */
    private function createLineRewards(Model $order, array $keptLines, float|int $totalPrice): void
    {
        $coupon = empty($order['coupon_code'])
            ? null
            : $this->couponService->findBy(params: ['code' => $order['coupon_code'], 'status' => CouponService::STATUS_ACTIVE]);
        $loyaltyPointSettings = $this->businessSettingService->getValuesByTypes(types: OrderDetailsRewardsService::LOYALTY_POINT_SETTING_KEYS);

        foreach ($keptLines as $keptLine) {
            $this->orderDetailsRewardsService->createForOrderDetail(
                orderId: $order->id,
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
            filters: ['order_id' => $order->id, 'reward_delivered' => 1, 'reward_type' => OrderDetailsRewardsService::REWARD_TYPE_LOYALTY_POINT],
        )->sum('reward_amount');
    }

    private function catalogueResponse(Request $request, int $sellerId, ?array $orderedQuantities): JsonResponse
    {
        $args = $request->only(self::CATALOGUE_QUERY_FIELDS);

        foreach (self::CATALOGUE_LIST_FIELDS as $field) {
            if (array_key_exists($field, $args)) {
                $args[$field] = is_string($args[$field]) ? json_decode($args[$field], true) : $args[$field];
            }
        }

        $products = $this->productService->getSellerCatalogueList(
            sellerId: $sellerId,
            filters: $this->getCatalogueFilters(args: $args),
            dataLimit: max(1, (int) ($args['limit'] ?? DEFAULT_DATA_LIMIT)),
            page: max(1, (int) ($args['offset'] ?? 1)),
            orderedQuantities: $orderedQuantities,
        );

        foreach ($products as $product) {
            $product->digital_product_authors_names = $this->productService->getAuthorsInfo(product: $product)['names'];
            $product->digital_product_publishing_house_names = $this->productService->getPublishingHouseInfo(product: $product)['names'];
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductListResource::collection($products->getCollection())->resolve(),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function getCatalogueFilters(array $args): array
    {
        $isClearanceSearch = ! empty($args['search']) && ($args['offer_type'] ?? null) === ProductRetailService::OFFER_CLEARANCE_SALE;

        $filters = [
            ProductRetailService::FILTER_SEARCH => $args['search'] ?? null,
            ProductRetailService::FILTER_OFFER_TYPE => $args['offer_type'] ?? null,
            ProductRetailService::FILTER_CLEARANCE_PRODUCT_IDS => $isClearanceSearch ? $this->stockClearanceProductService->getProductIds() : [],
            ProductRetailService::FILTER_PRODUCT_TYPE => $args['product_type'] ?? null,
            ProductRetailService::FILTER_PRODUCT_TYPES => $this->getListArgument(args: $args, field: 'product_types'),
            ProductRetailService::FILTER_BRAND_IDS => $this->getListArgument(args: $args, field: 'brand_ids'),
            ProductRetailService::FILTER_CATEGORY_IDS => $this->getListArgument(args: $args, field: 'category_ids') ?? [],
            ProductRetailService::FILTER_STATUS => $args['status'] ?? null,
            ProductRetailService::FILTER_STATUSES => $this->getListArgument(args: $args, field: 'product_status'),
            ProductRetailService::FILTER_REQUEST_STATUS => $args['request_status'] ?? null,
            ProductRetailService::FILTER_CATALOGUE_SORT => $args['filter_sort_by'] ?? null,
            ProductRetailService::FILTER_MIN_PRICE => $args['min_price'] ?? null,
            ProductRetailService::FILTER_MAX_PRICE => $args['max_price'] ?? null,
            ProductRetailService::FILTER_CURRENCY_CODE => $args['currency_code'] ?? null,
            ProductRetailService::FILTER_START_DATE => $args['start_date'] ?? null,
            ProductRetailService::FILTER_END_DATE => $args['end_date'] ?? null,
        ];

        $filterCategoryIds = $this->getListArgument(args: $args, field: 'filter_category_ids');

        if (! empty($filterCategoryIds)) {
            $treeCategoryIds = $this->categoryService->getCategoryIdsForFilter(
                categoryIds: $filterCategoryIds,
                subCategoryIds: $this->getListArgument(args: $args, field: 'filter_sub_category_ids') ?? [],
                subSubCategoryIds: $this->getListArgument(args: $args, field: 'filter_sub_sub_category_ids') ?? [],
            );
            $filters[ProductRetailService::FILTER_TREE_CATEGORY_IDS] = $treeCategoryIds['category_ids'];
            $filters[ProductRetailService::FILTER_TREE_SUB_CATEGORY_IDS] = $treeCategoryIds['sub_category_ids'];
            $filters[ProductRetailService::FILTER_TREE_SUB_SUB_CATEGORY_IDS] = $treeCategoryIds['sub_sub_category_ids'];
        }

        $publishingHouseIds = $this->getListArgument(args: $args, field: 'publishing_house_ids');

        if (! empty($publishingHouseIds)) {
            $filters[ProductRetailService::FILTER_PUBLISHING_HOUSE_IDS] = $publishingHouseIds;
            $filters[ProductRetailService::FILTER_SELECTED_PUBLISHER_PRODUCT_IDS] = $this->publishingHouseService->getAttachedProductIds(ownerIds: $publishingHouseIds);
            $filters[ProductRetailService::FILTER_ATTACHED_PUBLISHER_PRODUCT_IDS] = $this->publishingHouseService->getAttachedProductIds();
        }

        $authorIds = $this->getListArgument(args: $args, field: 'author_ids');

        if (! empty($authorIds)) {
            $filters[ProductRetailService::FILTER_AUTHOR_IDS] = $authorIds;
            $filters[ProductRetailService::FILTER_SELECTED_AUTHOR_PRODUCT_IDS] = $this->authorService->getAttachedProductIds(ownerIds: $authorIds);
            $filters[ProductRetailService::FILTER_ATTACHED_AUTHOR_PRODUCT_IDS] = $this->authorService->getAttachedProductIds();
        }

        return $filters;
    }

    private function getListArgument(array $args, string $field): ?array
    {
        return is_array($args[$field] ?? null) ? $args[$field] : null;
    }

    /** @return array{edit_by: string, edited_user_id: int, edited_user_name: string} */
    private function getEditor(Model $seller): array
    {
        return [
            'edit_by' => OrderEditService::EDITOR_SELLER,
            'edited_user_id' => $seller->id,
            'edited_user_name' => $seller->f_name.' '.$seller->l_name,
        ];
    }

    private function editNotAllowed(string $message): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.order_edit_not_allowed'),
            errors: [['error_code' => self::ORDER_EDIT_ERROR_CODE, 'message' => $message]],
        );
    }

    private function editRefused(string $reason): JsonResponse
    {
        return $this->responseFormatter(config: config(
            $reason === OrderEditService::REASON_PRODUCT_NOT_FOUND ? 'response.product_not_found' : 'response.order_edit_out_of_stock',
        ));
    }
}
