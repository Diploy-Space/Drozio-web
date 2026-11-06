<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\BusinessModule;
use App\Http\Controllers\Controller;
use App\Services\New\CartService;
use App\Services\New\ColorService;
use App\Services\New\CustomerService;
use App\Services\New\DigitalProductVariationService;
use App\Services\New\ProductRetailService;
use App\Services\New\RestockProductService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use App\Services\New\ShopService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private const MODULE_PANEL = 'module';

    private const COUPON_SESSION_KEYS = [
        'coupon_code',
        'coupon_type',
        'coupon_bearer',
        'coupon_discount',
        'coupon_seller_id',
    ];

    private const REMOVE_SESSION_KEYS = [
        ...self::COUPON_SESSION_KEYS,
        'shipping_method_id',
        'order_note',
    ];

    private const REASON_VALUE_KEYS = ['minimum_order_qty', 'minimum_order_amount', 'shop_name'];

    private const THEME_FASHION = 'theme_fashion';

    private const DISCOUNT_TYPE_FLAT = 'flat';

    private const ADDED_BY_ADMIN = 'admin';

    private const NON_PHYSICAL_QUANTITY = 100;

    private const CHECKOUT_DETAILS_ROUTE = 'checkout-details';

    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductRetailService $productService,
        private readonly ShopService $shopService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly ShippingMethodService $shippingMethodService,
        private readonly ColorService $colorService,
        private readonly DigitalProductVariationService $digitalProductVariationService,
        private readonly RestockProductService $restockProductService,
        private readonly CustomerService $customerService,
    ) {}

    public function getVariantPrice(Request $request): array|JsonResponse
    {
        $product = $this->productService->findForCart(id: $request['id']);

        if ($product === null) {
            return response()->json(['message' => translate('Product not found')], 404);
        }

        $colorName = $request->has('color') ? (string) $this->colorService->findBy(params: ['code' => $request['color']])?->name : '';
        $variationCode = $this->buildVariationCode(product: $product, request: $request, colorName: $colorName);
        $cartItems = $this->cartService->getRefreshedCartItemList(
            customerId: $this->resolveCustomerId(),
            sessionGuestId: session('guest_id'),
            requestGuestId: $request['guest_id'],
        );

        $cartState = $this->getInCartState(product: $product, request: $request, variationCode: $variationCode, cartItems: $cartItems);
        $requestQuantity = $cartState['quantity'];
        $pricing = $this->resolveVariantPricing(product: $product, variationCode: $variationCode);

        $digitalVariation = $this->digitalProductVariationService->findBy(params: [
            'product_id' => $product['id'],
            'variant_key' => $request['variant_key'],
        ]);

        if ($product['product_type'] == CartService::PRODUCT_TYPE_DIGITAL && $digitalVariation) {
            $pricing = $this->resolvePricing(product: $product, basePrice: $digitalVariation['price'], quantity: $digitalVariation['price']);

            foreach ($cartItems as $cartItem) {
                if ($cartItem['variant'] == $request['variant_key']) {
                    $variationCode = $cartItem['variant'];
                }
            }
        }

        $deliveryInfo = [];
        $stockLimit = getWebConfig(name: 'stock_limit');

        if (theme_root_path() == self::THEME_FASHION) {
            $deliveryInfo = $this->shippingTypeService->getProductDeliveryCharge(
                product: $product,
                quantity: $requestQuantity,
                orderWiseCostRange: $this->shippingMethodService->getOrderWiseCostRange(product: $product),
            );

            if ($product['added_by'] != self::ADDED_BY_ADMIN) {
                $stockLimit = $product?->seller?->stock_limit ?? 0;
                $stockLimit = $stockLimit <= 0 ? getWebConfig(name: 'stock_limit') : $stockLimit;
            }
        }

        $discountType = getProductPriceByType(product: $product, type: 'discount_type', result: 'string');

        return [
            'price' => webCurrencyConverter($pricing['price'] * $requestQuantity),
            'discount' => $discountType == self::DISCOUNT_TYPE_FLAT
                ? webCurrencyConverter($pricing['discount'])
                : getProductPriceByType(product: $product, type: 'discount', result: 'value').'%',
            'discount_type' => $discountType,
            'discount_amount' => $pricing['discount'],
            'quantity' => $product['product_type'] == CartService::PRODUCT_TYPE_PHYSICAL ? $pricing['quantity'] : self::NON_PHYSICAL_QUANTITY,
            'delivery_cost' => isset($deliveryInfo['delivery_cost']) ? webCurrencyConverter($deliveryInfo['delivery_cost']) : 0,
            'unit_price' => webCurrencyConverter($pricing['price']),
            'total_unit_price' => webCurrencyConverter($pricing['unit_price']),
            'discounted_unit_price' => webCurrencyConverter($pricing['price']),
            'color_name' => $colorName,
            'stock_limit' => $stockLimit,
            'in_cart_status' => $cartState['status'],
            'in_cart_quantity' => $requestQuantity,
            'in_cart_key' => $cartState['key'],
            'variation_code' => $variationCode,
            'product_type' => $product['product_type'],
            'restock_request_status' => $this->getRestockRequestStatus(product: $product, variationCode: $variationCode),
        ];
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $product = $this->productService->findForCart(id: $request['id']);

        $cart = $product === null
            ? $this->toLegacyPayload(result: ['status' => CartService::STATUS_FAILED, 'reason' => CartService::REASON_PRODUCT_UNAVAILABLE])
            : $this->toLegacyPayload(result: $this->addProductToCart(product: $product, request: $request));

        if ($cart['status'] == CartService::STATUS_SHIPPING_METHOD_REQUIRED) {
            $cart['shippingMethodHtmlView'] = view(themeViewName(key: 'product_shipping_method_modal_view_partials'), [
                'shipping_method_list' => $cart['shipping_method_list'],
                'productData' => $request->all(),
            ])->render();
        }

        $this->forgetSessionKeys(keys: self::COUPON_SESSION_KEYS);

        if (($cart['redirect_to'] ?? null) == CartService::REDIRECT_TO_CHECKOUT) {
            $cart['redirect_to_url'] = route(self::CHECKOUT_DETAILS_ROUTE);

            return $request->ajax() ? response()->json($cart) : redirect()->route(self::CHECKOUT_DETAILS_ROUTE);
        }

        if (! $request->ajax() && $cart['status'] == CartService::STATUS_FAILED) {
            ToastMagic::warning($cart['message']);

            return back();
        }

        return response()->json($cart);
    }

    public function remove(Request $request): JsonResponse
    {
        $customerId = auth('customer')->id();
        $ownerId = $customerId ?? session('guest_id');

        $this->cartService->deleteBy(params: [
            'id' => $request['key'],
            'customer_id' => $ownerId,
            'is_guest' => $customerId === null ? CartService::GUEST : CartService::CUSTOMER,
        ]);

        $this->forgetSessionKeys(keys: self::REMOVE_SESSION_KEYS);

        $cartList = $this->cartService->getOwnerVariantList(ownerId: $ownerId);

        return response()->json([
            'data' => view(themeViewName(key: 'products_cart_details_partials'), ['request' => $request])->render(),
            'message' => translate('Item has been removed from cart'),
            'cartList' => $cartList,
        ]);
    }

    public function updateQuantity(Request $request): JsonResponse
    {
        $response = $this->updateCartItemQuantity(request: $request);

        $this->forgetSessionKeys(keys: self::COUPON_SESSION_KEYS);

        if ($response['status'] == CartService::STATUS_FAILED) {
            return response()->json($response);
        }

        return response()->json(view(themeViewName(key: 'products_cart_details_partials'), ['request' => $request])->render());
    }

    public function updateGuestQuantity(Request $request): JsonResponse
    {
        $customerId = $this->resolveCustomerId();
        $response = $this->updateCartItemQuantity(request: $request);
        $cartItems = $this->cartService->getRefreshedCartItemList(
            customerId: $customerId,
            sessionGuestId: session('guest_id'),
            requestGuestId: $request['guest_id'],
        );

        $this->forgetSessionKeys(keys: self::COUPON_SESSION_KEYS);

        $authCustomerId = auth('customer')->id();
        $cartItem = $this->cartService->findBy(params: [
            'id' => $request['key'],
            'customer_id' => $authCustomerId ?? session('guest_id'),
            'is_guest' => $authCustomerId === null ? CartService::GUEST : CartService::CUSTOMER,
        ]);

        if (! $cartItem) {
            return response()->json([
                'status' => CartService::STATUS_FAILED,
                'qty' => $request['quantity'],
                'message' => translate('Product not found in cart'),
            ]);
        }

        $quantityPrice = webCurrencyConverter($cartItem['price'] * (int) $cartItem['quantity']);
        $discountPrice = webCurrencyConverter(($cartItem['price'] - $cartItem['discount']) * (int) $cartItem['quantity']);
        $subTotal = 0;
        $totalDiscount = 0;

        foreach ($cartItems as $listedCartItem) {
            $subTotal += ($listedCartItem['price'] - $listedCartItem['discount']) * $listedCartItem['quantity'];
            $totalDiscount += $listedCartItem['discount'] * $listedCartItem['quantity'];
        }

        if ($response['status'] == CartService::STATUS_FAILED) {
            return response()->json([
                'status' => $response['status'],
                'message' => $response['message'],
                'qty' => $response['qty'],
            ]);
        }

        $freeDeliveryStatus = $this->cartService->getGroupFreeDeliveryOrderAmount(
            customerId: $customerId,
            sessionGuestId: session('guest_id'),
            requestGuestId: $request['guest_id'],
            cartGroupId: $cartItems->first()?->cart_group_id,
        );

        return response()->json([
            'status' => $response['status'],
            'message' => translate('Updated successfully'),
            'qty' => $request->quantity,
            'total_price' => webCurrencyConverter($subTotal),
            'discount_price' => $discountPrice,
            'quantity_price' => $quantityPrice,
            'total_discount_price' => webCurrencyConverter($totalDiscount),
            'free_delivery_status' => $freeDeliveryStatus,
            'in_cart_key' => $cartItem['id'],
        ]);
    }

    public function getNavCart(Request $request): JsonResponse
    {
        $usesModuleCartPanel = $request->input('panel') === self::MODULE_PANEL;
        $cartPartialKey = $usesModuleCartPanel ? 'products_module_cart_partials' : 'products_cart_partials';
        $mobileNavPartialKey = $usesModuleCartPanel ? 'products_module_mobile_nav_partials' : 'products_mobile_nav_partials';

        return response()->json([
            'data' => view(themeViewName(key: $cartPartialKey))->render(),
            'mobile_nav' => view(themeViewName(key: $mobileNavPartialKey), ['usesModuleCartPanel' => $usesModuleCartPanel])->render(),
        ]);
    }

    public function getFloatingNavCart(): JsonResponse
    {
        return response()->json(['floating_nav' => view(themeViewName(key: 'products_floating_nav_partials'))->render()]);
    }

    private function addProductToCart(Model $product, Request $request): array
    {
        $data = $request->all();
        $shippingType = $this->shippingTypeService->resolveTypeForProduct(product: $product);

        return $this->cartService->addToCart(
            product: $product,
            data: $data,
            customerId: $this->resolveCustomerId(),
            guestId: session('guest_id') ?? $request['guest_id'] ?? 0,
            isShopClosed: $this->shopService->isClosedForAuthor(authorType: $product->added_by, shop: $product->seller?->shop),
            shippingType: $shippingType,
            sellerShippingList: $this->shippingMethodService->getCartShippingOptions(product: $product, shippingType: $shippingType),
            colorName: array_key_exists('color', $data) ? $this->colorService->findBy(params: ['code' => $data['color']])?->name : null,
            digitalVariation: $this->digitalProductVariationService->findBy(params: [
                'product_id' => $product->id,
                'variant_key' => $data['variant_key'] ?? null,
            ]),
            shippingMethod: empty($data['shipping_method_id'])
                ? null
                : $this->shippingMethodService->findBy(params: ['id' => $data['shipping_method_id'], 'context' => BusinessModule::RETAIL]),
        );
    }

    private function updateCartItemQuantity(Request $request): array
    {
        $customerId = $this->resolveCustomerId();
        $guestId = session('guest_id') ?? $request['guest_id'] ?? 0;

        $cartItem = $this->cartService->findBy(params: [
            'id' => $request['key'],
            'customer_id' => $customerId ?? $guestId,
            'is_guest' => $customerId === null ? CartService::GUEST : CartService::CUSTOMER,
        ]);
        $product = $cartItem === null ? null : $this->productService->find(id: $cartItem->product_id, relations: ['retailDetail']);

        return $this->toLegacyPayload(result: $this->cartService->updateItemQuantity(
            cartItem: $product === null ? null : $cartItem,
            product: $product,
            quantity: $request['quantity'],
            isBuyNow: $request['buy_now'] == 1,
            customerId: $customerId,
            guestId: $guestId,
        ));
    }

    private function toLegacyPayload(array $result): array
    {
        $payload = [];

        foreach ($result as $key => $value) {
            if (in_array($key, self::REASON_VALUE_KEYS, true)) {
                continue;
            }

            if ($key === 'reason') {
                $payload['message'] = $this->cartService->reasonMessage(result: $result);

                continue;
            }

            $payload[$key] = $value;
        }

        if (array_key_exists('cart_shipping_cost', $payload) && array_key_exists('message', $payload)) {
            $message = $payload['message'];
            unset($payload['message']);
            $payload['message'] = $message;
        }

        return $payload;
    }

    private function buildVariationCode(Model $product, Request $request, string $colorName): string
    {
        $variationCode = $colorName;

        foreach (json_decode($product->choice_options) ?? [] as $choice) {
            $choiceValue = str_replace(' ', '', (string) $request[$choice->name]);
            $variationCode .= $variationCode !== '' ? '-'.$choiceValue : $choiceValue;
        }

        return $variationCode;
    }

    /**
     * @return array{status: int, key: mixed, quantity: mixed}
     */
    private function getInCartState(Model $product, Request $request, string $variationCode, Collection $cartItems): array
    {
        $productVariationCode = $request['product_variation_code'];
        $state = [
            'status' => 0,
            'key' => null,
            'quantity' => $productVariationCode != $variationCode ? $product['minimum_order_qty'] : $request['quantity'],
        ];

        foreach ($cartItems as $cartItem) {
            if ($cartItem['product_id'] == $product['id'] && $cartItem['variant'] == $variationCode) {
                $state = [
                    'status' => 1,
                    'key' => $cartItem['id'],
                    'quantity' => $productVariationCode == $variationCode ? $request['quantity'] : $cartItem['quantity'],
                ];
            }

            if ($product['product_type'] == CartService::PRODUCT_TYPE_DIGITAL && $request['variant_key'] && $cartItem['variant'] == $request['variant_key']) {
                $state = [
                    'status' => 1,
                    'key' => $cartItem['id'],
                    'quantity' => $productVariationCode == $request['variant_key'] ? $request['quantity'] : $cartItem['quantity'],
                ];
            }
        }

        return $state;
    }

    /**
     * @return array{price: float|int, discount: float|int, unit_price: mixed, quantity: mixed}
     */
    private function resolveVariantPricing(Model $product, string $variationCode): array
    {
        if ($variationCode == null) {
            return $this->resolvePricing(product: $product, basePrice: $product->unit_price, quantity: $product->current_stock);
        }

        $pricing = ['price' => 0, 'discount' => 0, 'unit_price' => 0, 'quantity' => 0];

        foreach (json_decode($product->retailDetail?->variation ?? '[]') ?? [] as $variation) {
            if ($variation->type == $variationCode) {
                $pricing = $this->resolvePricing(product: $product, basePrice: $variation->price, quantity: $variation->qty);
            }
        }

        return $pricing;
    }

    /**
     * @return array{price: float|int, discount: float|int, unit_price: mixed, quantity: mixed}
     */
    private function resolvePricing(Model $product, mixed $basePrice, mixed $quantity): array
    {
        $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $basePrice);

        return [
            'price' => $basePrice - $discount,
            'discount' => $discount,
            'unit_price' => $basePrice,
            'quantity' => $quantity,
        ];
    }

    private function getRestockRequestStatus(Model $product, string $variationCode): int
    {
        if (! auth('customer')->check()) {
            return 0;
        }

        $filters = [
            RestockProductService::FILTER_CUSTOMER_ID => auth('customer')->id(),
            'product_id' => $product['id'],
        ];

        if ($variationCode !== '') {
            $filters['variant'] = $variationCode;
        }

        return (int) $this->restockProductService->getList(dataLimit: 'all', filters: $filters)->isNotEmpty();
    }

    private function resolveCustomerId(): ?int
    {
        if (auth('customer')->check()) {
            return auth('customer')->id();
        }

        if (session()->has('customer_id') && ! session('is_guest')) {
            return $this->customerService->find(id: session('customer_id'))?->id;
        }

        return null;
    }

    private function forgetSessionKeys(array $keys): void
    {
        foreach ($keys as $key) {
            session()->forget($key);
        }
    }
}
