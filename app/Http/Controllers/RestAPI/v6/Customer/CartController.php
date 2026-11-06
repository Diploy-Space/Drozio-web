<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Enums\BusinessModule;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Cart\AddToCartRequest;
use App\Http\Requests\Customer\Cart\MergeGuestRequest;
use App\Http\Requests\Customer\Cart\RemoveAllFromCartRequest;
use App\Http\Requests\Customer\Cart\RemoveFromCartRequest;
use App\Http\Requests\Customer\Cart\SelectionUpdateRequest;
use App\Http\Requests\Customer\Cart\UpdateCartRequest;
use App\Http\Resources\V6\Customer\CartItemResource;
use App\Http\Resources\V6\Customer\ShippingMethodResource;
use App\Services\New\CartService;
use App\Services\New\ColorService;
use App\Services\New\CouponService;
use App\Services\New\DigitalProductVariationService;
use App\Services\New\GuestUserService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReferralCustomerService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use App\Services\New\ShopService;
use App\Traits\CustomerOwnerTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class CartController extends BaseApiController
{
    use CustomerOwnerTrait;

    private const ADD_REASON_RESPONSE_KEYS = [
        CartService::REASON_ADDED => 'cart_item_added',
        CartService::REASON_ADDED_FOR_CHECKOUT => 'cart_item_added_for_checkout',
        CartService::REASON_SHIPPING_METHOD_REQUIRED => 'cart_shipping_method_required',
        CartService::REASON_PRODUCT_UNAVAILABLE => 'cart_product_unavailable',
        CartService::REASON_SHOP_UNAVAILABLE => 'cart_shop_unavailable',
        CartService::REASON_OUT_OF_STOCK => 'cart_out_of_stock',
        CartService::REASON_BELOW_MINIMUM_ORDER_QUANTITY => 'cart_below_minimum_order_quantity',
        CartService::REASON_BELOW_MINIMUM_ORDER_AMOUNT => 'cart_below_minimum_order_amount',
        CartService::REASON_SHIPPING_METHOD_NOT_FOUND => 'cart_shipping_method_not_found',
        CartService::REASON_SHIPPING_UNAVAILABLE => 'cart_shipping_unavailable',
    ];

    private const COMPLETED_ORDER_FILTERS = [
        'order_status' => 'delivered',
        'payment_status' => 'paid',
        'order_type' => 'default_type',
    ];

    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductRetailService $productService,
        private readonly ShopService $shopService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly ShippingMethodService $shippingMethodService,
        private readonly ColorService $colorService,
        private readonly DigitalProductVariationService $digitalProductVariationService,
        private readonly CouponService $couponService,
        private readonly OrderService $orderService,
        private readonly ReferralCustomerService $referralCustomerService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'Cart items of the customer or guest, with tax, minimum order and free delivery details',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Cart',
        query: ['guest_id' => 'nullable|integer', 'coupon_code' => 'nullable|string'],
        status: [401],
        auth: false,
        guard: 'api',
    )]
    public function getList(Request $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $customerId = $this->ownerCustomerId(owner: $owner);
        $guestId = $this->ownerGuestId(owner: $owner);
        $couponCode = is_string($request['coupon_code']) ? $request['coupon_code'] : '';

        $cartItems = $this->cartService->getOwnerCartItemList(customerId: $customerId, guestId: $guestId);

        $cartItems = $this->cartService->applyCartListDetails(
            cartItems: $cartItems,
            orderGenerateCartList: $this->getOrderGenerateCartList(customerId: $customerId, guestId: $guestId, couponCode: $couponCode),
            customerId: $customerId,
            guestId: $guestId,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => CartItemResource::collection($cartItems),
                'params' => $this->paramsFormatter(['coupon_code' => $couponCode]),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Add a product to the cart',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper. Variant choices are posted as color and choice_* fields, digital variants as variant_key.',
        tag: 'Cart',
        status: [401, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function addItem(AddToCartRequest $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $product = $this->productService->findForCart(id: $request['id']);

        if ($product === null) {
            return $this->responseFormatter(config: config('response.cart_product_not_found'));
        }

        $result = $this->addProductToCart(product: $product, request: $request, owner: $owner);

        return $this->responseFormatter(
            config: config('response.'.self::ADD_REASON_RESPONSE_KEYS[$result['reason']]),
            content: ['data' => $this->formatAddResult(result: $result)],
        );
    }

    #[ApiDoc(
        summary: 'Change the quantity of a cart item',
        description: 'The cart item must belong to the caller.',
        tag: 'Cart',
        status: [401, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function updateItem(UpdateCartRequest $request, string|int $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $customerId = $this->ownerCustomerId(owner: $owner);
        $cartItem = $this->cartService->findBy(params: ['id' => $id] + $this->customerOwnerFilters($owner));
        $product = $cartItem === null ? null : $this->productService->find(id: $cartItem->product_id, relations: ['retailDetail']);

        if ($product === null) {
            return $this->responseFormatter(config: config('response.cart_item_not_found'));
        }

        $result = $this->cartService->updateItemQuantity(
            cartItem: $cartItem,
            product: $product,
            quantity: $request['quantity'],
            isBuyNow: $request['buy_now'] == 1,
            customerId: $customerId,
            guestId: $this->ownerGuestId(owner: $owner),
        );

        return $this->responseFormatter(
            config: config($result['status'] == CartService::STATUS_SUCCESS ? 'response.cart_quantity_updated' : 'response.cart_stock_limited'),
            content: ['data' => ['status' => $result['status'], 'qty' => $result['qty']]],
        );
    }

    #[ApiDoc(
        summary: 'Remove one item from the cart',
        description: 'The cart item must belong to the caller.',
        tag: 'Cart',
        query: ['guest_id' => 'nullable|integer'],
        status: [401, 404],
        auth: false,
        guard: 'api',
    )]
    public function deleteItem(RemoveFromCartRequest $request, string|int $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $deletedCount = $this->cartService->deleteBy(params: ['id' => $id] + $this->customerOwnerFilters($owner));

        return $this->responseFormatter(
            config: config($deletedCount === 0 ? 'response.cart_item_not_found' : 'response.cart_item_removed'),
        );
    }

    #[ApiDoc(
        summary: 'Remove every item from the cart',
        tag: 'Cart',
        status: [401, 422],
        auth: false,
        guard: 'api',
    )]
    public function clear(RemoveAllFromCartRequest $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $this->cartService->deleteBy(params: $this->customerOwnerFilters($owner));

        return $this->responseFormatter(config: config('response.cart_cleared'));
    }

    #[ApiDoc(
        summary: 'Select or unselect cart items for checkout',
        tag: 'Cart',
        status: [401, 422],
        auth: false,
        guard: 'api',
    )]
    public function updateSelection(SelectionUpdateRequest $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $this->cartService->updateItemsCheckedStatus(
            ownerId: $owner['id'],
            isGuest: (int) $owner['is_guest'],
            ids: $request['ids'],
            isChecked: $request->isChecked(),
        );

        return $this->responseFormatter(config: config('response.cart_selection_updated'));
    }

    #[ApiDoc(
        summary: 'Move a guest cart into the signed-in customer cart',
        description: 'Call once after sign-in, before reading the cart. The guest id travels as cart_guest_id, not guest_id.',
        tag: 'Cart',
        status: [401, 422],
    )]
    public function mergeGuestCart(MergeGuestRequest $request): JsonResponse
    {
        $this->cartService->moveGuestCartToCustomer(guestId: $request['cart_guest_id'], customerId: (int) auth('api')->id());

        return $this->responseFormatter(config: config('response.cart_guest_merged'));
    }

    #[ApiDoc(
        summary: 'Referral discount the customer can redeem on the selected cart items',
        tag: 'Cart',
        query: ['coupon_discount' => 'nullable|numeric'],
        status: [401],
    )]
    public function getReferralDiscount(Request $request): JsonResponse
    {
        $customerId = (int) auth('api')->id();
        $cartItems = $this->cartService->getRefreshedCartItemList(
            customerId: $customerId,
            sessionGuestId: null,
            requestGuestId: null,
            type: CartService::TYPE_CHECKED,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => [
                'amount' => $this->referralCustomerService->calculateCartReferralDiscount(
                    customerId: $customerId,
                    cartAmount: $this->cartService->getDiscountedSubtotal(cartItems: $cartItems),
                    couponDiscount: $request['coupon_discount'],
                ),
            ]],
        );
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function ownerCustomerId(array $owner): ?int
    {
        return $owner['is_guest'] ? null : (int) $owner['id'];
    }

    private function ownerGuestId(array $owner): ?string
    {
        return $owner['is_guest'] ? $owner['id'] : null;
    }

    private function addProductToCart(Model $product, Request $request, array $owner): array
    {
        $guestId = $this->ownerGuestId(owner: $owner);
        $data = ['guest_id' => $guestId] + $request->all();
        $shippingType = $this->shippingTypeService->resolveTypeForProduct(product: $product);

        return $this->cartService->addToCart(
            product: $product,
            data: $data,
            customerId: $this->ownerCustomerId(owner: $owner),
            guestId: $guestId,
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

    private function formatAddResult(array $result): array
    {
        $data = [];

        foreach ($result as $key => $value) {
            $data[$key] = match ($key) {
                'cart' => new CartItemResource($value),
                'shipping_method_list' => $value === null ? null : ShippingMethodResource::collection($value),
                default => $value,
            };
        }

        unset($data['reason']);

        return $data;
    }

    private function getOrderGenerateCartList(?int $customerId, ?string $guestId, string $couponCode): array
    {
        $cartItems = $this->cartService->getRefreshedCartItemList(
            customerId: $customerId,
            sessionGuestId: null,
            requestGuestId: $guestId,
            type: CartService::TYPE_CHECKED,
        );
        $coupon = $couponCode === '' ? null : $this->couponService->findBy(params: ['code' => $couponCode, 'status' => 1, CouponService::FILTER_RUNNING => true]);
        $groupShippingCosts = $coupon?->coupon_type === CouponService::TYPE_FREE_DELIVERY
            ? $this->cartService->getGroupShippingCosts(customerId: $customerId, requestGuestId: $guestId, cartGroupIds: $cartItems->pluck('cart_group_id')->unique()->values()->all())
            : [];
        $couponOrderGroupCount = $customerId ? $this->orderService->countOrderGroups(filters: ['customer_id' => $customerId, 'coupon_code' => $couponCode]) : 0;
        $cartCoupon = $this->couponService->calculateCartCouponDiscount(
            couponCode: $couponCode,
            customerId: $customerId,
            customerCouponOrderGroupCount: $couponOrderGroupCount,
            customerOrderCount: $customerId ? $this->orderService->count(filters: ['customer_id' => $customerId]) : 0,
            cartItems: $cartItems,
            groupShippingCosts: $groupShippingCosts,
        );

        $groupCouponDiscounts = [];

        foreach ($cartItems->groupBy('cart_group_id') as $cartGroupId => $groupCartItems) {
            $groupCouponDiscounts[$cartGroupId] = $this->couponService->calculateGroupCouponDiscount(
                couponCode: $couponCode,
                customerId: $customerId,
                customerCouponOrderGroupCount: $couponOrderGroupCount,
                applicableAmount: $cartCoupon['total_cart_amount'],
                discountAmount: $cartCoupon['discount'],
                groupCartItems: $groupCartItems,
                groupShippingCost: (float) ($groupShippingCosts[$cartGroupId] ?? 0),
            );
        }

        $referralCustomer = $this->referralCustomerService->findRedeemableForCustomer(
            customerId: $customerId,
            completedOrderCount: $customerId ? $this->orderService->count(filters: ['customer_id' => $customerId] + self::COMPLETED_ORDER_FILTERS) : 0,
        );
        $referralBases = $this->cartService->getReferralDiscountBases(
            cartItems: $cartItems,
            totalCouponDiscount: $cartCoupon['discount'],
            groupCouponDiscounts: $groupCouponDiscounts,
        );

        $groupReferralDiscounts = [];

        foreach ($referralBases['group_amounts'] as $cartGroupId => $groupAmount) {
            $groupReferralDiscounts[$cartGroupId] = $this->referralCustomerService->calculateReferralDiscount(
                referralCustomer: $referralCustomer,
                totalAmount: $referralBases['total_amount'],
                groupAmount: $groupAmount,
            );
        }

        return $this->cartService->getOrderGenerateCartList(
            customerId: $customerId,
            sessionGuestId: null,
            requestGuestId: $guestId,
            groupCouponDiscounts: $groupCouponDiscounts,
            groupReferralDiscounts: $groupReferralDiscounts,
            shippingAddressId: 0,
            billingAddressId: 0,
        );
    }
}
