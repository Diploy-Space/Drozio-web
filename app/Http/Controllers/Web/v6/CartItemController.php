<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Services\New\CartService;
use App\Services\New\CartShippingService;
use App\Services\New\ColorService;
use App\Services\New\DigitalProductVariationService;
use App\Services\New\ProductRetailService;
use App\Services\New\ShippingTypeService;
use App\Services\New\ShopService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    private const CHECKED_ITEMS_COUPON_SESSION_KEYS = ['coupon_code', 'coupon_type', 'coupon_bearer', 'coupon_discount'];

    private const IN_HOUSE_AUTHOR = 'admin';

    public function __construct(
        private readonly CartService $cartService,
        private readonly CartShippingService $cartShippingService,
        private readonly ProductRetailService $productService,
        private readonly ColorService $colorService,
        private readonly DigitalProductVariationService $digitalProductVariationService,
        private readonly ShopService $shopService,
        private readonly ShippingTypeService $shippingTypeService,
    ) {}

    public function updateVariation(Request $request): array
    {
        $product = $this->productService->find(id: $request['product_id'], relations: ['retailDetail']);

        if ($product === null) {
            return ['status' => 0, 'message' => translate('Product is unavailable')];
        }

        return $product['product_type'] === CartService::PRODUCT_TYPE_DIGITAL
            ? $this->updateDigitalVariation(request: $request, product: $product)
            : $this->updatePhysicalVariation(request: $request, product: $product);
    }

    public function removeAll(): RedirectResponse
    {
        $this->cartService->deleteBy(params: $this->cartOwnerParams());

        return redirect()->back();
    }

    public function updateCheckedItems(Request $request): JsonResponse
    {
        $ownerParams = $this->cartOwnerParams();

        $this->cartService->updateCheckedItems(
            ownerId: $ownerParams['customer_id'],
            isGuest: $ownerParams['is_guest'],
            ids: $request['ids'] ? (array) $request['ids'] : [],
        );

        $cartGroupIds = $this->cartService->getOwnerCartGroupIds(
            ownerId: $ownerParams['customer_id'],
            isGuest: $ownerParams['is_guest'],
        );

        if ($cartGroupIds !== []) {
            $this->cartShippingService->deleteByCartGroupIds(cartGroupIds: $cartGroupIds);
        }

        session()->forget(self::CHECKED_ITEMS_COUPON_SESSION_KEYS);

        return response()->json([
            'message' => translate('Updated successfully'),
            'htmlView' => view(themeViewName(key: 'products_cart_details_partials'), ['request' => $request])->render(),
        ], 200);
    }

    private function updatePhysicalVariation(Request $request, Model $product): array
    {
        $variant = '';
        $variations = [];
        $choices = [];
        $price = 0;
        $discount = 0;

        if ($request->has('color')) {
            $variant = (string) $this->colorService->findBy(params: ['code' => $request['color']])?->name;
            $variations['color'] = $variant;
        }

        foreach (json_decode($product->choice_options ?? '[]') ?? [] as $choice) {
            $choices[$choice->name] = $request[$choice->name];
            $variations[$choice->title] = $request[$choice->name];
            $variant .= ($variant != null ? '-' : '').str_replace(' ', '', (string) $request[$choice->name]);
        }

        $productVariations = json_decode($product->retailDetail?->variation ?? '[]') ?? [];

        if ($variant != null) {
            foreach ($productVariations as $productVariation) {
                if ($productVariation->type == $variant) {
                    $discount = Helpers::getProductDiscount($product, $productVariation->price);
                    $price = $productVariation->price - $discount;
                }
            }
        } else {
            $discount = Helpers::getProductDiscount($product, $product->unit_price);
            $price = $product->unit_price - $discount;
        }

        $ownerParams = $this->cartOwnerParams();

        $existingCart = $this->cartService->findBy(params: [
            'product_id' => $request['product_id'],
            ...$ownerParams,
            'variant' => $variant,
        ]);

        if ($existingCart !== null) {
            return ['status' => 0, 'message' => translate('Already added').'!'];
        }

        $cart = $this->cartService->findBy(params: ['id' => $request['id'], ...$ownerParams]);

        if ($cart === null) {
            return ['status' => 0, 'message' => translate('Product not found in cart')];
        }

        if ($variant != null) {
            foreach ($productVariations as $productVariation) {
                if ($productVariation->type == $variant) {
                    $price = $productVariation->price;

                    if ($productVariation->qty < $request['quantity']) {
                        return ['status' => 0, 'message' => translate('Out of Stock').'!'];
                    }
                }
            }
        } else {
            $price = $product->unit_price;
        }

        $this->cartService->updateWhere(params: ['id' => $cart->id], data: [
            'color' => $request->has('color') ? $request['color'] : null,
            'choices' => json_encode($choices),
            'variations' => json_encode($variations),
            'variant' => $variant,
            'price' => $price,
            'discount' => $discount,
            'quantity' => $request['quantity'],
        ]);

        return [
            'status' => 1,
            'message' => translate('Added successfully').'!',
            'price' => webCurrencyConverter($price),
            'discount' => webCurrencyConverter($discount * $request['quantity']),
            'data' => view(themeViewName(key: 'products_cart_details_partials'), ['request' => $request])->render(),
        ];
    }

    private function updateDigitalVariation(Request $request, Model $product): array
    {
        $price = $product->unit_price;
        $digitalVariation = $this->digitalProductVariationService->findBy(params: [
            'product_id' => $product['id'],
            'variant_key' => $request['variant_key'],
        ]);

        if ($request['variant_key'] && $digitalVariation) {
            $price = $digitalVariation['price'];
        }

        $customerId = auth('customer')->id();
        $productDiscount = Helpers::getProductDiscount($product, $price);
        $ownerParams = $this->cartOwnerParams();

        $cartRow = [
            'customer_id' => $customerId ?? (session('guest_id') ?? ($request['guest_id'] ?? 0)),
            'product_id' => $product['id'],
            'product_type' => $product['product_type'],
            'digital_product_type' => $product->retailDetail?->digital_product_type,
            'choices' => json_encode([]),
            'variations' => json_encode([]),
            'variant' => $request['variant_key'],
            'quantity' => $request['quantity'],
            'price' => $price,
            'discount' => $productDiscount,
            'is_checked' => 1,
            'slug' => $product['slug'],
            'name' => $product['name'],
            'thumbnail' => $product['thumbnail'],
            'seller_id' => $product->added_by == self::IN_HOUSE_AUTHOR ? CartService::IN_HOUSE_CART_SELLER_ID : $product->user_id,
            'seller_is' => $product['added_by'],
            'created_at' => now(),
            'updated_at' => now(),
            'shop_info' => $product->added_by == self::IN_HOUSE_AUTHOR
                ? getInHouseShopConfig(key: 'name')
                : $this->shopService->findBy(params: ['seller_id' => $product->user_id])?->name,
            'shipping_cost' => $product['product_type'] == CartService::PRODUCT_TYPE_PHYSICAL
                ? $this->shippingTypeService->resolveProductShippingCost(product: $product, quantity: $request['quantity'])
                : 0,
            'is_guest' => $ownerParams['is_guest'],
        ];

        $cart = $this->cartService->findBy(params: [
            'id' => $request['id'],
            'product_id' => $request['product_id'],
            ...$ownerParams,
            'variant' => $request['current_variant_key'],
        ]);

        if ($cart !== null) {
            $this->cartService->updateWhere(params: ['id' => $cart['id']], data: $cartRow);
        } else {
            $this->cartService->create(data: $cartRow);
        }

        return [
            'status' => 1,
            'message' => $cart !== null ? translate('Updated successfully') : translate('Added successfully').'!',
            'price' => webCurrencyConverter($price),
            'discount' => webCurrencyConverter($productDiscount),
            'data' => view(themeViewName(key: 'products_cart_details_partials'), ['request' => $request])->render(),
        ];
    }

    private function cartOwnerParams(): array
    {
        $customerId = auth('customer')->id();

        return [
            'customer_id' => $customerId ?? session('guest_id'),
            'is_guest' => $customerId ? CartService::CUSTOMER : CartService::GUEST,
        ];
    }
}
