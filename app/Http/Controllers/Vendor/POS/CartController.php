<?php

namespace App\Http\Controllers\Vendor\POS;

use App\Enums\SessionKey;
use App\Http\Controllers\BaseController;
use App\Services\CartService;
use App\Services\New\ColorService;
use App\Services\New\CustomerService;
use App\Services\New\ProductRetailService;
use App\Services\POSService;
use App\Traits\CalculatorTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class CartController extends BaseController
{
    use CalculatorTrait;

    public function __construct(
        private readonly ProductRetailService $retailProductService,
        private readonly ColorService $colorService,
        private readonly CustomerService $customerService,
        private readonly CartService $cartService,
        private readonly POSService $POSService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse {}

    public function getVariantPrice(Request $request): JsonResponse
    {
        $product = $this->retailProductService->find(id: $request['id'], relations: ['digitalVariation', 'clearanceSale' => function ($query) {
            return $query->active();
        }]);
        $colorName = $this->colorService->findBy(params: ['code' => $request['color']])->name ?? null;
        $data = $this->cartService->getVariantData(
            request: $request, product: $product, colorName: $colorName
        );

        return response()->json($data);
    }

    public function updateQuantity(Request $request): JsonResponse
    {
        $cartId = $this->cartService->getCurrentCartId();
        if ($request['quantity'] > 0) {
            $product = $this->retailProductService->find(id: $request['key'], relations: ['clearanceSale' => function ($query) {
                return $query->active();
            }]);
            $quantity = $this->cartService->getQuantityAndUpdateTime(request: $request, product: $product);
            $cartItems = $this->getCartData(cartName: $cartId);
            if ($product['product_type'] == 'physical' && $quantity < 0) {
                return response()->json([
                    'qty' => $quantity,
                    'productCounts' => $this->cartService->getProductCounts(),
                    'productType' => $product['product_type'],
                    'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
                ]);
            } else {
                return response()->json([
                    'quantityUpdate' => 1,
                    'productCounts' => $this->cartService->getProductCounts(),
                    'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
                ]);
            }
        } else {
            $cartItems = $this->getCartData(cartName: $cartId);

            return response()->json([
                'upQty' => 'zeroNegative',
                'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }
    }

    public function addToCart(Request $request): JsonResponse
    {
        $cartId = $this->cartService->getCurrentCartId();
        $product = $this->retailProductService->find(id: $request['id'], relations: ['digitalVariation', 'clearanceSale' => function ($query) {
            return $query->active();
        }]);
        $colorName = $this->colorService->findBy(params: ['code' => $request['color']])->name ?? null;
        $variations['color'] = $colorName;
        $variant = $this->cartService->makeVariation(
            request: $request,
            colorName: $colorName,
            choiceOptions: json_decode($product['choice_options'])
        );
        if ($product['product_type'] == 'digital' && $request->has('variant_key')) {
            foreach ($product['digitalVariation'] as $digitalVariation) {
                if ($digitalVariation['variant_key'] == $request['variant_key']) {
                    $variant = $digitalVariation['variant_key'];
                }
            }
        }
        foreach (json_decode($product['choice_options']) as $choice) {
            $variations[$choice->title] = $request[$choice->name];
        }
        $price = $product['unit_price'];
        $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $price);
        $cartData = session($cartId);
        if ($cartId && session()->has($cartId) && count($cartData) > 0) {
            foreach ($cartData as $key => $cartItem) {
                if (is_array($cartItem) && $cartItem['id'] == $request['id'] && $cartItem['variant'] == $variant) {
                    if ($variant != null) {
                        $price = $this->cartService->getVariationPrice(variation: json_decode($product->retailDetail?->variation ?? '[]'), variant: $variant, productPrice: $product['unit_price']);
                        $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $price);
                    }
                    if ($product['product_type'] == 'digital' && $request->has('variant_key')) {
                        foreach ($product['digitalVariation'] as $digitalVariation) {
                            if ($digitalVariation['variant_key'] == $request['variant_key']) {
                                $variant = $digitalVariation['variant_key'];
                                $price = $digitalVariation['price'];
                                $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $price);
                            }
                        }
                    }

                    if ($request['quantity'] <= 0 || $request['quantity_in_cart'] <= 0) {
                        $cartItems = $this->getCartData(cartName: $cartId);

                        return response()->json([
                            'data' => 'custom-error',
                            'title' => translate('Invalid quantity'),
                            'text' => translate('Please provide a valid quantity'),
                            'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
                        ]);
                    }

                    $currentQty = $this->cartService->checkCurrentStock(variant: $variant, variation: json_decode($product->retailDetail?->variation ?? '[]'), productQty: $product['current_stock'], quantity: $request['quantity_in_cart']);
                    if ($product['product_type'] == 'physical' && $currentQty < 0) {
                        $cartItems = $this->getCartData(cartName: $cartId);

                        return response()->json([
                            'data' => 0,
                            'productCounts' => $this->cartService->getProductCounts(),
                            'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
                        ]);
                    }
                    $cartItem = $this->cartService->addCartDataOnSession(
                        product: $product,
                        quantity: $request['quantity_in_cart'],
                        price: $price,
                        discount: $discount,
                        variant: $variant,
                        variations: $variations
                    );
                    unset($cartData[$key]);
                    $cartData[] = $cartItem;
                    session([$cartId => $cartData]);
                    $getCurrentCustomerData = $this->getCustomerDataFromSessionForPOS();
                    $summaryData = array_merge($this->POSService->getSummaryData(), $getCurrentCustomerData);
                    $cartItems = $this->getCartData(cartName: $cartId);

                    return response()->json([
                        'data' => 1,
                        'productCounts' => $this->cartService->getProductCounts(),
                        'inCartData' => 1,
                        'requestQuantity' => $request['quantity_in_cart'],
                        'view' => view('vendor-views.pos.partials._cart', compact('summaryData', 'cartItems'))->render(),
                    ]);
                }
            }
        }
        if ($variant != null) {
            $price = $this->cartService->getVariationPrice(variation: json_decode($product->retailDetail?->variation ?? '[]'), variant: $variant, productPrice: $product['unit_price']);
            $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $price);
        }

        if ($product['product_type'] == 'digital' && $request->has('variant_key')) {
            foreach ($product['digitalVariation'] as $digitalVariation) {
                if ($digitalVariation['variant_key'] == $request['variant_key']) {
                    $variant = $digitalVariation['variant_key'];
                    $price = $digitalVariation['price'];
                    $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $price);
                }
            }
        }

        $currentQty = $this->cartService->checkCurrentStock(variant: $variant, variation: json_decode($product->retailDetail?->variation ?? '[]'), productQty: $product['current_stock'], quantity: $request['quantity']);
        if ($product['product_type'] == 'physical' && $currentQty < 0) {
            $cartItems = $this->getCartData(cartName: $cartId);

            return response()->json([
                'data' => 0,
                'productCounts' => $this->cartService->getProductCounts(),
                'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }
        $sessionData = $this->cartService->addCartDataOnSession(
            product: $product,
            quantity: $request['quantity'],
            price: $price,
            discount: $discount,
            variant: $variant,
            variations: $variations
        );
        $cartItems = $this->getCartData(cartName: $cartId);

        return response()->json([
            'data' => $sessionData,
            'productCounts' => $this->cartService->getProductCounts(),
            'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
        ]);
    }

    public function removeCart(Request $request): JsonResponse
    {
        $cartId = $this->cartService->getCurrentCartId();
        $cart = session($cartId);
        $cartKeeper = [];
        if (session()->has($cartId) && count($cart) > 0) {
            foreach ($cart as $cartItem) {
                if (is_array($cartItem)) {
                    if ($cartItem['id'] != $request['id']) {
                        $cartKeeper[] = $cartItem;
                    } else {
                        if ($cartItem['variant'] != $request['variant']) {
                            $cartKeeper[] = $cartItem;
                        }
                    }
                }
            }
        }
        session()->put($cartId, $cartKeeper);
        $cartItems = $this->getCartData(cartName: $cartId);

        return response()->json([
            'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            'productCounts' => $this->cartService->getProductCounts(),
        ]);
    }

    public function clearSessionCartIds(): RedirectResponse
    {
        session()->forget(SessionKey::CART_NAME);
        session()->forget($this->cartService->getCurrentCartId());
        session()->forget(SessionKey::CURRENT_USER);

        return redirect()->route('vendor.retail.pos.index');
    }

    public function getCartIds(): JsonResponse
    {
        $this->cartService->getCartKeeper();
        $getCurrentCustomerData = $this->getCustomerDataFromSessionForPOS();
        $summaryData = array_merge($this->POSService->getSummaryData(), $getCurrentCustomerData);
        $cartItems = $this->getCartData(cartName: $this->cartService->getCurrentCartId());

        return response()->json([
            'view' => view('vendor-views.pos.partials._cart', compact('summaryData', 'cartItems'))->render(),
        ]);
    }

    public function emptyCart(): JsonResponse
    {
        $cartId = $this->cartService->getCurrentCartId();
        session()->forget($cartId);
        $this->cartService->getNewCartSession(cartId: $cartId);
        $getCurrentCustomerData = $this->getCustomerDataFromSessionForPOS();
        $summaryData = array_merge($this->POSService->getSummaryData(), $getCurrentCustomerData);
        $cartItems = $this->getCartData(cartName: $cartId);

        return response()->json([
            'view' => view('vendor-views.pos.partials._cart', compact('summaryData', 'cartItems'))->render(),
        ]);
    }

    public function changeCart(Request $request): RedirectResponse
    {
        $this->cartService->customerOnHoldStatus(status: true);
        session()->put(SessionKey::CURRENT_USER, $request['cart_id']);
        $this->cartService->customerOnHoldStatus(status: false);
        ToastMagic::success($request['cart_id'].' '.translate('Order is now resumed'));

        return redirect()->route('vendor.retail.pos.index');
    }

    public function addNewCartId(): RedirectResponse
    {
        $cart = $this->cartService->getCurrentCartItems();

        $cartItems = collect($this->cartService->getCurrentCartItems())->filter(function ($cartItem) {
            return is_array($cartItem);
        });

        if (is_null($cart) || count($cartItems) <= 0) {
            ToastMagic::error(translate('Your cart is empty.'));
        } else {
            if (session()->has($this->cartService->getCurrentCartId()) && count($cart) > 0) {
                ToastMagic::success(translate('This order is now on hold'));
            }
            $this->cartService->customerOnHoldStatus(status: true);
            $this->cartService->getNewCartId();
        }

        return redirect()->route('vendor.retail.pos.index');
    }

    protected function getCustomerDataFromSessionForPOS(): array
    {
        if (Str::contains($this->cartService->getCurrentCartId(), 'walk-in-customer')) {
            $currentCustomer = 'Walk-In Customer';
            $currentCustomerData = $this->customerService->findBy(params: ['id' => '0']);
        } else {
            $userId = explode('-', $this->cartService->getCurrentCartId())[2];
            $currentCustomerData = $this->customerService->findBy(params: ['id' => $userId]);
            $currentCustomer = $currentCustomerData['f_name'].' '.$currentCustomerData['l_name'].' ('.$currentCustomerData['phone'].')';
        }

        return [
            'currentCustomer' => $currentCustomer,
            'currentCustomerData' => $currentCustomerData,
        ];
    }

    protected function getCustomerCartData(string $cartName): array
    {
        $customerCartData = [];
        if (Str::contains($cartName, 'walk-in-customer')) {
            $currentCustomerInfo = [
                'customerName' => 'Walk-In Customer',
                'customerPhone' => '',
            ];
            $customerId = 0;
        } else {
            $customerId = explode('-', $cartName)[2];
            $currentCustomerData = $this->customerService->findBy(params: ['id' => $customerId]);
            $currentCustomerInfo = $this->cartService->getCustomerInfo(currentCustomerData: $currentCustomerData, customerId: $customerId);

        }
        $customerCartData[$cartName] = [
            'customerName' => $currentCustomerInfo['customerName'],
            'customerPhone' => $currentCustomerInfo['customerPhone'],
            'customerId' => $customerId,
        ];

        return $customerCartData;
    }

    protected function calculateCartItemsData(string $cartName, array $customerCartData): array
    {
        $cartItemValue = [];
        $subTotalCalculation = [
            'countItem' => 0,
            'totalQuantity' => 0,
            'totalTax' => 0,
            'subtotal' => 0,
            'discountOnProduct' => 0,
            'productSubtotal' => 0,
        ];

        $cartListSession = $this->cartService->filterSessionCartList(cart: session($cartName));

        if (count($cartListSession) > 0) {
            $products = $this->retailProductService->getList(
                dataLimit: 'all',
                relations: ['clearanceSale' => fn ($query) => $query->active()],
                filters: ['id' => $cartListSession->pluck('id')->toArray()],
            );
            $totalDiscountedPrice = $this->cartService->getCartTotalDiscountPrice(session($cartName), $products, $customerCartData[$cartName]['customerId']);

            foreach ($cartListSession as $cartItem) {
                if (is_array($cartItem)) {
                    $product = $products->firstWhere('id', $cartItem['id']);
                    if ($product) {
                        $cartSubTotalCalculation = $this->cartService->getCartSubtotalCalculation(
                            product: $product,
                            cartItem: $cartItem,
                            totalDiscountedPrice: $totalDiscountedPrice,
                            cartName: $cartName,
                        );
                        if ($cartItem['customerId'] == $customerCartData[$cartName]['customerId']) {
                            $cartItem['productSubtotal'] = $cartSubTotalCalculation['productSubtotal'];
                            $subTotalCalculation['customerOnHold'] = $cartItem['customerOnHold'];
                            $cartItemValue[] = $cartItem;

                            $subTotalCalculation['countItem'] += $cartSubTotalCalculation['countItem'];
                            $subTotalCalculation['totalQuantity'] += $cartSubTotalCalculation['totalQuantity'];
                            $subTotalCalculation['totalTax'] += $cartSubTotalCalculation['appliedTaxAmount'];
                            $subTotalCalculation['productSubtotal'] += $cartSubTotalCalculation['productSubtotal'];
                            $subTotalCalculation['subtotal'] += $cartSubTotalCalculation['subtotal'];
                            $subTotalCalculation['discountOnProduct'] += $cartSubTotalCalculation['discountOnProduct'];
                        }
                    }
                }
            }
        }
        $totalCalculation = $this->cartService->getTotalCalculation(
            subTotalCalculation: $subTotalCalculation, cartName: $cartName
        );

        return [
            'countItem' => $subTotalCalculation['countItem'],
            'total' => $totalCalculation['total'],
            'subtotal' => $subTotalCalculation['subtotal'],
            'totalTax' => $subTotalCalculation['totalTax'],
            'discountOnProduct' => $subTotalCalculation['discountOnProduct'],
            'productSubtotal' => $subTotalCalculation['productSubtotal'],
            'cartItemValue' => $cartItemValue,
            'couponDiscount' => $totalCalculation['couponDiscount'],
            'extraDiscount' => $totalCalculation['extraDiscount'],
            'customerOnHold' => $subTotalCalculation['customerOnHold'] ?? false,
        ];
    }

    protected function getCartData(string $cartName): array
    {
        $customerCartData = $this->getCustomerCartData(cartName: $cartName);
        $cartItemData = $this->calculateCartItemsData(cartName: $cartName, customerCartData: $customerCartData);

        return array_merge($customerCartData[$cartName], $cartItemData);
    }
}
