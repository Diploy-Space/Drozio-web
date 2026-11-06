<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Enums\UserType;
use App\Events\CustomerRegistrationEvent;
use App\Events\DigitalProductDownloadEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\Pos\CustomerStoreRequest;
use App\Http\Requests\Vendor\Pos\OrderPlaceRequest;
use App\Http\Requests\Vendor\Pos\TaxAmountRequest;
use App\Http\Resources\V6\Customer\CategoryResource;
use App\Http\Resources\V6\Vendor\CustomerResource;
use App\Http\Resources\V6\Vendor\OrderResource;
use App\Http\Resources\V6\Vendor\PosProductResource;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CartService;
use App\Services\New\CategoryService;
use App\Services\New\ColorService;
use App\Services\New\CustomerService;
use App\Services\New\DigitalProductVariationService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\StorageService;
use App\Services\New\WalletTransactionService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class PosController extends BaseApiController
{
    private const GENERATED_PASSWORD_LENGTH = 32;

    private const PAYMENT_METHOD_WALLET = 'wallet';

    private const SELLER_POS_SETTING = 'seller_pos';

    private const WALLET_ORDER_PLACE_REFERENCE = 'order_place_in_pos';

    private const NO_CATEGORY = 0;

    private const SNAPSHOT_SELLER_SECRET_ATTRIBUTES = [
        'password', 'auth_token', 'remember_token', 'cm_firebase_token', 'cm_firebase_token_web',
        'bank_name', 'branch', 'account_no', 'holder_name',
    ];

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly PasswordResetService $passwordResetService,
        private readonly CategoryService $categoryService,
        private readonly ProductRetailService $productService,
        private readonly ColorService $colorService,
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly WalletTransactionService $walletTransactionService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly DigitalProductVariationService $digitalProductVariationService,
        private readonly StorageService $storageService,
    ) {}

    #[ApiDoc(
        summary: 'Search customers for the POS counter',
        tag: 'POS',
        query: [
            'search' => 'nullable|string',
            'limit' => 'nullable|string',
            'offset' => 'nullable|integer',
            'include_walking_customer' => 'nullable|boolean',
        ],
        status: [401, 403],
        guard: 'seller',
    )]
    public function getCustomers(Request $request): JsonResponse
    {
        $args = ['search' => $request->query('search')];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $customers = $this->customerService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            filters: [
                CustomerService::FILTER_EXCLUDE_WALKING_CUSTOMER => ! $request->boolean('include_walking_customer'),
            ],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => CustomerResource::collection($customers),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($customers),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Register a customer from the POS counter',
        description: 'Creates the customer with an unguessable password and issues a reset token, so the welcome mail is the only way in.',
        tag: 'POS',
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function storeCustomer(CustomerStoreRequest $request): JsonResponse
    {
        $customer = DB::transaction(function () use ($request) {
            $customer = $this->customerService->create(data: $request->validated() + [
                'password' => Str::random(self::GENERATED_PASSWORD_LENGTH),
            ]);

            $this->passwordResetService->create(data: [
                'identity' => $request['phone'],
                'user_type' => UserType::CUSTOMER,
            ]);

            return $customer;
        });

        event(new CustomerRegistrationEvent(email: $request['email'], data: $this->welcomeMailData($request)));

        return $this->responseFormatter(
            config: config('response.customer_added_successfully'),
            content: ['data' => new CustomerResource($customer)],
        );
    }

    #[ApiDoc(
        summary: 'Retail category tree for the POS counter',
        description: 'Top-level categories by priority, each with its sub and sub-sub categories.',
        tag: 'POS',
        status: [401, 403],
        guard: 'seller',
    )]
    public function getCategories(): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => CategoryResource::collection($this->categoryService->getTree())->resolve()],
        );
    }

    #[ApiDoc(
        summary: 'Active products of the authenticated seller for the POS counter',
        description: 'category_ids accepts a list or a JSON string of category, sub category or sub-sub category ids; 0 means every category. search matches every word of the product name.',
        tag: 'POS',
        query: [
            'search' => 'nullable|string',
            'category_ids' => 'nullable|string',
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [401, 403],
        guard: 'seller',
    )]
    public function getProducts(Request $request): JsonResponse
    {
        $args = $request->only(['search', 'category_ids', 'limit', 'offset']);

        $products = $this->productService->getPosList(
            sellerId: (int) $request->attributes->get('seller')->id,
            categoryIds: $this->categoryIds($args['category_ids'] ?? null),
            searchValue: $args['search'] ?? null,
            dataLimit: max(1, (int) ($args['limit'] ?? DEFAULT_DATA_LIMIT)),
            page: max(1, (int) ($args['offset'] ?? 1)),
            relations: ['retailDetail'],
        );

        $this->withFormattedColors(products: $products->getCollection()->all());

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => PosProductResource::collection($products->getCollection())->resolve(),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Find a product of the authenticated seller by its barcode',
        tag: 'POS',
        query: [
            'code' => 'required|string',
        ],
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function findProductByBarcode(Request $request): JsonResponse
    {
        $code = (string) $request->query('code', '');

        $product = $code === '' ? null : $this->productService->findSellerProductBy(
            sellerId: (int) $request->attributes->get('seller')->id,
            filters: ['code' => $code],
            relations: ['clearanceSale' => fn ($query) => $query->active()->with(['setup'])],
            withCount: ['reviews'],
        );

        if ($product === null) {
            return $this->notFoundResponse(errorCode: 'product', message: translate('Product not found'));
        }

        $this->withFormattedColors(products: [$product]);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => PosProductResource::make($product)->resolve()],
        );
    }

    #[ApiDoc(
        summary: 'Calculate the tax, coupon share and extra discount share of each POS basket line',
        description: 'Returns the posted basket with total_tax_amount and modified_cart, which is posted back when placing the order. Every product must belong to the authenticated seller.',
        tag: 'POS',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function calculateTax(TaxAmountRequest $request): JsonResponse
    {
        $data = $request->validated();
        $cartItems = $request->cartItems();

        if ($cartItems === []) {
            return $this->responseFormatter(config: config('response.data_fetched'), content: ['data' => $data]);
        }

        $products = $this->productService->getPosTaxProducts(
            sellerId: (int) $request->attributes->get('seller')->id,
            productIds: $request->cartProductIds(),
        );

        if ($products->count() !== count($request->cartProductIds())) {
            return $this->notFoundResponse(errorCode: 'product', message: translate('Product not found'));
        }

        $taxedCart = $this->cartService->calculatePosCartTax(
            cartItems: $cartItems,
            products: $products,
            couponDiscountAmount: $data['coupon_discount_amount'] ?? null,
            extraDiscount: $data['extra_discount'] ?? null,
            extraDiscountType: $data['extra_discount_type'] ?? null,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => [
                ...$data,
                'total_tax_amount' => $taxedCart['total_tax_amount'],
                'modified_cart' => $taxedCart['modified_cart']->map(fn (array $cartItem): array => $this->taxedCartItemData($cartItem))->values()->all(),
            ]],
        );
    }

    #[ApiDoc(
        summary: 'Place a POS order for the authenticated seller',
        description: 'modified_cart is the basket returned by tax-amount. The wallet debit, stock deduction, order lines and order share one transaction. Every product must belong to the authenticated seller.',
        tag: 'POS',
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function placeOrder(OrderPlaceRequest $request): JsonResponse
    {
        $sellerId = (int) $request->attributes->get('seller')->id;
        $paidAmount = currencyConverter(amount: (float) ($request->validated('paid_amount') ?? 0));
        $lastCartProductId = $request->lastCartProductId();
        $hasDigitalProduct = $lastCartProductId !== null && $this->productService->hasDigitalProduct(productIds: [$lastCartProductId]);

        if ($request->isWalkingCustomer() && $hasDigitalProduct) {
            return $this->responseFormatter(config: config('response.pos_order_digital_product_requires_customer'));
        }

        $isWalletPayment = $request->validated('payment_method') == self::PAYMENT_METHOD_WALLET && ! $request->isWalkingCustomer();

        if ($isWalletPayment && ! $this->hasWalletBalance(customerId: $request->customerId(), amount: $paidAmount)) {
            return $this->responseFormatter(config: config('response.pos_order_wallet_insufficient_balance'));
        }

        $cartItems = $request->modifiedCartItems();
        $products = [];

        foreach ($cartItems as $index => $cartItem) {
            $product = $this->productService->findSellerProductBy(
                sellerId: $sellerId,
                filters: ['id' => $cartItem['id']],
                relations: ['retailDetail', 'digitalVariation', 'clearanceSale' => fn ($query) => $query->active()],
                withCount: ['reviews'],
            );

            if ($product === null || $cartItem['quantity'] > $product['current_stock']) {
                return $this->responseFormatter(
                    config: config('response.pos_order_product_unavailable'),
                    errors: [['error_code' => 'product', 'message' => trim(($product?->name ?? '').' '.translate('Not available for order place'))]],
                );
            }

            $products[$index] = $product;
        }

        $orderId = DB::transaction(function () use ($request, $sellerId, $paidAmount, $isWalletPayment, $cartItems, $products): int {
            if ($isWalletPayment) {
                $this->debitCustomerWallet(customerId: $request->customerId(), amount: floatval($paidAmount));
            }

            $orderId = $this->orderService->generatePosOrderId();

            foreach ($cartItems as $index => $cartItem) {
                $this->createOrderLine(orderId: $orderId, cartItem: $cartItem, product: $products[$index]);
            }

            $this->orderService->createSellerAppPosOrder(
                orderId: $orderId,
                sellerId: $sellerId,
                input: $request->orderInput(),
                orderAmount: $this->orderService->calculatePosCartTotal(modifiedCart: $cartItems),
                paidAmount: $paidAmount,
            );

            return $orderId;
        });

        if ($hasDigitalProduct) {
            $this->dispatchDigitalProductDownloadMail(orderId: $orderId);
        }

        return $this->responseFormatter(config: config('response.pos_order_placed'), content: ['data' => ['order_id' => $orderId]]);
    }

    #[ApiDoc(
        summary: 'Invoice data of a POS order of the authenticated seller',
        description: 'Denied when POS is off for the business or for the seller.',
        tag: 'POS',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function getInvoice(Request $request, int $id): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        if ((int) $seller->pos_status === 0 || (int) $this->businessSettingService->getValueByType(type: self::SELLER_POS_SETTING) === 0) {
            return $this->responseFormatter(config: config('response.pos_invoice_access_denied'));
        }

        $order = $this->orderService->findForSeller(id: $id, sellerId: (int) $seller->id, relations: ['details', 'shipping']);

        if ($order === null) {
            return $this->notFoundResponse(errorCode: 'order', message: translate('Order not found'));
        }

        foreach ($order->details as $detail) {
            $detail->setAttribute('product_details', $this->invoiceProductDetails(productDetails: $detail->getRawOriginal('product_details')));
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => OrderResource::make($this->orderService->withResolvedBillingAddress(orders: $order))->resolve()],
        );
    }

    /** @return array<string, string> */
    private function welcomeMailData(CustomerStoreRequest $request): array
    {
        $companyName = (string) getWebConfig(name: 'company_name');

        return [
            'userName' => $request['f_name'],
            'userType' => UserType::CUSTOMER,
            'templateName' => 'registration-from-pos',
            'subject' => translate('Customer Registration Successfully Completed'),
            'title' => translate('Welcome to').' '.$companyName.'!',
            'resetPassword' => route('customer.auth.recover-password'),
            'message' => translate('Reset your password using this phone number to sign in').': '.$request['phone'],
        ];
    }

    private function categoryIds(mixed $categoryIds): array
    {
        $decoded = is_string($categoryIds) ? json_decode($categoryIds, true) : $categoryIds;

        if ($decoded === null || $decoded == self::NO_CATEGORY) {
            return [];
        }

        return array_values(array_map('intval', array_filter((array) $decoded, 'is_numeric')));
    }

    /** @param  array<int, Model>  $products */
    private function withFormattedColors(array $products): void
    {
        $colorNames = $this->colorService->getNameMap();

        foreach ($products as $product) {
            $product->setAttribute('colors_formatted', $this->productService->getFormattedColors(product: $product, colorNames: $colorNames));
        }
    }

    private function taxedCartItemData(array $cartItem): array
    {
        $category = $cartItem['category'];

        return [
            'id' => $cartItem['id'],
            'price' => $cartItem['price'],
            'discounted_price' => $cartItem['discounted_price'],
            'discount' => $cartItem['discount'],
            'discount_type' => $cartItem['discount_type'],
            'quantity' => $cartItem['quantity'],
            'variant' => $cartItem['variant'],
            'variant_key' => $cartItem['variant_key'],
            'digital_variation_price' => $cartItem['digital_variation_price'],
            'variation' => $cartItem['variation'],
            'taxVats' => $this->taxVatData(taxVats: $cartItem['taxVats']),
            'category' => $category === null ? null : [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'icon_full_url' => $category->icon_full_url,
                'parent_id' => $category->parent_id,
                'position' => $category->position,
                'business_module' => $category->business_module,
                'home_status' => $category->home_status,
                'priority' => $category->priority,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
                'tax_vats' => $this->taxVatData(taxVats: $category->relationLoaded('taxVats') ? $category->taxVats : null),
            ],
            'coupon_discount' => $cartItem['coupon_discount'],
            'extra_discount' => $cartItem['extra_discount'],
            'applied_tax_amount' => $cartItem['applied_tax_amount'],
        ];
    }

    private function taxVatData(?iterable $taxVats): array
    {
        return collect($taxVats ?? [])->map(static fn ($taxVat): array => [
            'id' => $taxVat->id,
            'taxable_type' => $taxVat->taxable_type,
            'taxable_id' => $taxVat->taxable_id,
            'tax_id' => $taxVat->tax_id,
            'system_tax_setup_id' => $taxVat->system_tax_setup_id,
            'tax_for' => $taxVat->tax_for,
            'created_at' => $taxVat->created_at,
            'updated_at' => $taxVat->updated_at,
            'tax' => $taxVat->tax === null ? null : [
                'id' => $taxVat->tax->id,
                'name' => $taxVat->tax->name,
                'tax_rate' => $taxVat->tax->tax_rate,
                'country_code' => $taxVat->tax->country_code,
                'business_module' => $taxVat->tax->business_module,
                'is_default' => $taxVat->tax->is_default,
                'is_active' => $taxVat->tax->is_active,
            ],
        ])->values()->all();
    }

    private function hasWalletBalance(int $customerId, float|int $amount): bool
    {
        $customer = $this->customerService->find(id: $customerId);

        return $customer !== null && $customer['wallet_balance'] >= $amount;
    }

    private function debitCustomerWallet(int $customerId, float $amount): void
    {
        if ($this->businessSettingService->getValueByType(type: BusinessSettingService::WALLET_STATUS) != 1) {
            return;
        }

        $customer = $this->customerService->find(id: $customerId);

        DB::transaction(function () use ($customer, $amount): void {
            $walletTransaction = $this->walletTransactionService->createWalletEntry(
                customerId: $customer->id,
                currentBalance: (float) $customer->wallet_balance,
                amount: $amount,
                transactionType: WalletTransactionService::TRANSACTION_TYPE_ORDER_PLACE,
                reference: self::WALLET_ORDER_PLACE_REFERENCE,
            );

            if ($walletTransaction !== null) {
                $this->customerService->updateWalletBalance(
                    id: $customer->id,
                    walletBalance: $walletTransaction->balance + $walletTransaction->admin_bonus,
                );
            }
        });
    }

    private function createOrderLine(int $orderId, array $cartItem, Model $product): void
    {
        $digitalVariation = null;
        $variationStorage = null;

        if ($product->product_type === ProductRetailService::PRODUCT_TYPE_DIGITAL && ! empty($cartItem['variant_key'])) {
            $digitalVariation = $this->digitalProductVariationService->findBy(
                params: ['product_id' => $cartItem['id'], 'variant_key' => $cartItem['variant_key']],
                relations: ['storage'],
            );
            $variationStorage = $digitalVariation === null ? null : $this->storageService->findBy(params: [
                'data_id' => $digitalVariation->id,
                'data_type' => StorageService::DATA_TYPE_DIGITAL_PRODUCT_VARIATION,
            ]);
        }

        $snapshot = $this->orderDetailService->buildPosProductSnapshot(
            cartItem: $cartItem,
            product: $product,
            digitalVariation: $digitalVariation,
            variationStorage: $variationStorage,
        );

        $this->productService->deductPosStock(product: $product, variant: $cartItem['variant'], quantity: $cartItem['quantity']);

        $this->orderDetailService->createPosOrderLine(
            orderId: $orderId,
            cartItem: $cartItem,
            productSnapshot: $snapshot['product'],
            variant: $snapshot['variant'],
            sellerId: $product->user_id,
            withoutSellerSecrets: true,
        );
    }

    private function dispatchDigitalProductDownloadMail(int $orderId): void
    {
        $order = $this->orderService->find(id: $orderId, relations: ['details.productAllStatus', 'customer']);

        if ($order?->customer === null) {
            return;
        }

        event(new DigitalProductDownloadEvent(email: $order->customer['email'], data: [
            'userName' => $order->customer->f_name,
            'userType' => UserType::CUSTOMER,
            'templateName' => 'digital-product-download',
            'order' => $order,
            'subject' => translate('Download Digital Product'),
            'title' => translate('Congratulations').'!',
            'emailId' => $order->customer['email'],
        ]));
    }

    private function invoiceProductDetails(?string $productDetails): ?array
    {
        $formatted = Helpers::product_data_formatting(json_decode((string) $productDetails, true));

        if (! is_array($formatted)) {
            return null;
        }

        if (is_array($formatted['seller'] ?? null)) {
            $formatted['seller'] = array_diff_key($formatted['seller'], array_flip(self::SNAPSHOT_SELLER_SECRET_ATTRIBUTES));
        }

        return $formatted;
    }

    private function notFoundResponse(string $errorCode, string $message): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_not_found'),
            errors: [['error_code' => $errorCode, 'message' => $message]],
        );
    }
}
