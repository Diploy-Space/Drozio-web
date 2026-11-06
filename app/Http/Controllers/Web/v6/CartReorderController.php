<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Order\AgainRequest;
use App\Services\New\CartService;
use App\Services\New\ColorService;
use App\Services\New\DigitalProductVariationService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use App\Services\New\ShopService;
use App\Services\New\WishlistService;
use Illuminate\Http\JsonResponse;

class CartReorderController extends Controller
{
    private const DEFAULT_WISHLIST_QUANTITY = 1;

    private const REORDER_SESSION_KEYS = [
        'coupon_code',
        'coupon_type',
        'coupon_bearer',
        'coupon_discount',
        'coupon_seller_id',
        'shipping_method_id',
        'order_note',
    ];

    public function __construct(
        private readonly CartService $cartService,
        private readonly WishlistService $wishlistService,
        private readonly ProductRetailService $productRetailService,
        private readonly ShopService $shopService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly ShippingMethodService $shippingMethodService,
        private readonly DigitalProductVariationService $digitalProductVariationService,
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly ColorService $colorService,
    ) {}

    public function addWishlistToCart(): JsonResponse
    {
        $customerId = auth('customer')->id();
        $guestId = session('guest_id') ?? 0;

        foreach ($this->wishlistService->getProductIds(customerId: $customerId) as $productId) {
            $this->addWishlistProductToCart(productId: $productId, customerId: $customerId, guestId: $guestId);
        }

        return response()->json([
            'success' => true,
            'message' => translate('All wishlist items have been added to cart'),
        ]);
    }

    public function orderAgain(AgainRequest $request): JsonResponse
    {
        $order = $this->orderService->findBy(
            params: ['id' => $request['order_id'], 'customer_id' => auth('customer')->id(), 'is_guest' => '0'],
            relations: ['seller.shop'],
        );

        if ($this->shopService->isClosedForAuthor(authorType: $order?->seller_is, shop: $order?->seller?->shop)) {
            return response()->json([
                'status' => 0,
                'message' => translate('This shop is temporarily closed or on vacation').' '.translate('You cannot add products to the cart from this shop for now'),
            ]);
        }

        $orderDetails = $order === null
            ? collect()
            : $this->orderDetailService->getList(dataLimit: 'all', filters: ['order_id' => $order->id]);

        $result = $this->cartService->addOrderItemsAgain(
            orderDetails: $orderDetails,
            products: $this->productRetailService->getActiveCartProducts(ids: $orderDetails->pluck('product_id')->all()),
            colorCodesByName: $this->colorService->getCodeMapByName(),
            customerId: auth('customer')->id(),
        );

        if ($result['add_to_cart_count'] === 0 && $result['order_product_count'] !== 0) {
            return response()->json([
                'status' => 0,
                'message' => translate('Some items are unavailable and were not added to your cart.'),
            ]);
        }

        if ($result['order_product_count'] === $result['add_to_cart_count']) {
            session()->forget(self::REORDER_SESSION_KEYS);
        }

        return response()->json([
            'status' => 1,
            'redirect_url' => route('shop-cart'),
            'message' => $this->getReorderMessage(result: $result),
            'errors' => $this->getReorderErrorMessages(errorReasons: $result['errorReasons']),
        ]);
    }

    private function addWishlistProductToCart(int $productId, ?int $customerId, int|string $guestId): void
    {
        $product = $this->productRetailService->findForCart(id: $productId);

        if ($product === null) {
            return;
        }

        $shippingType = $this->shippingTypeService->resolveTypeForProduct(product: $product);

        $this->cartService->addToCart(
            product: $product,
            data: [
                'id' => $productId,
                'quantity' => $product['minimum_order_qty'] ?? self::DEFAULT_WISHLIST_QUANTITY,
                'product_variation_code' => $productId,
                'key' => null,
            ],
            customerId: $customerId,
            guestId: $guestId,
            isShopClosed: $this->shopService->isClosedForAuthor(authorType: $product->added_by, shop: $product->seller?->shop),
            shippingType: $shippingType,
            sellerShippingList: $this->shippingMethodService->getCartShippingOptions(product: $product, shippingType: $shippingType),
            digitalVariation: $this->digitalProductVariationService->findBy(params: ['product_id' => $product->id, 'variant_key' => null]),
        );
    }

    private function getReorderMessage(array $result): string
    {
        if ($result['failedAddToCartCount'] !== 0) {
            return translate('Some items are unavailable and were not added to your cart.');
        }

        return $result['updated_cart_count'] > 0
            ? translate('Updated successfully')
            : translate('Added to cart successfully');
    }

    private function getReorderErrorMessages(array $errorReasons): array
    {
        return array_map(static fn (array $error): string => match ($error['reason']) {
            CartService::REASON_PRODUCT_NOT_AVAILABLE => $error['name'].translate('Currently not available'),
            CartService::REASON_NOT_ENOUGH_STOCK => $error['name'].translate('Not enough stock, or below the minimum order quantity.'),
            CartService::REASON_VARIATION_NOT_ENOUGH_STOCK => $error['name'].translate('Variation does not have enough stock to fulfill the requested quantity.'),
            CartService::REASON_DIGITAL_FILE_MISSING => translate('This ready product is missing its file. Upload it before proceeding.'),
        }, $errorReasons);
    }
}
