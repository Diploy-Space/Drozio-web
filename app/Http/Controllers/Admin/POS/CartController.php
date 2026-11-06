<?php

namespace App\Http\Controllers\Admin\POS;

use App\Http\Requests\Admin\Pos\Cart\AddRequest;
use App\Http\Requests\Admin\Pos\Cart\QuantityUpdateRequest;
use App\Http\Requests\Admin\Pos\Cart\RemoveRequest;
use App\Http\Requests\Admin\Pos\HoldOrder\ResumeRequest;
use App\Services\New\ColorService;
use App\Services\New\CustomerService;
use App\Services\New\Pos\PosCartService;
use App\Services\New\Pos\PosHoldOrderService;
use App\Services\New\ProductRetailService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CartController extends PosBaseController
{
    public function __construct(
        ProductRetailService $retailProductService,
        CustomerService $customerService,
        PosHoldOrderService $posHoldOrderService,
        private readonly ColorService $colorService,
    ) {
        parent::__construct(retailProductService: $retailProductService, customerService: $customerService, posHoldOrderService: $posHoldOrderService);
    }

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse {}

    public function getVariantPrice(Request $request): JsonResponse
    {
        $cartItems = $this->getCartSummary(cartId: $this->posHoldOrderService->getCurrentCartId());
        $productSubtotal = $cartItems['productSubtotal'] ?? 0;
        $product = $this->retailProductService->find(id: $request['id'], relations: ['digitalVariation', 'clearanceSale' => function ($query) {
            return $query->active();
        }]);
        $colorName = $this->colorService->findBy(params: ['code' => $request['color']])->name ?? null;
        $data = $this->posHoldOrderService->getVariantData(input: $request->all(), product: $product, colorName: $colorName);
        $data['productSubtotal'] = setCurrencySymbol(amount: usdToDefaultCurrency(amount: $productSubtotal));

        return response()->json($data);
    }

    public function updateQuantity(QuantityUpdateRequest $request): JsonResponse
    {
        $cartId = $this->posHoldOrderService->getCurrentCartId();
        $product = $this->retailProductService->find(id: $request['key'], relations: ['clearanceSale' => function ($query) {
            return $query->active();
        }]);
        $remainingStock = $this->posHoldOrderService->updateLineQuantity(
            productId: (int) $request['key'],
            variant: $request['variant'],
            quantity: (int) $request['quantity'],
            product: $product,
        );
        $isStockShort = $product['product_type'] == PosCartService::PRODUCT_TYPE_PHYSICAL && $remainingStock < 0;
        $adjustments = $isStockShort ? [] : $this->posHoldOrderService->refreshCartAdjustments(cartId: $cartId);
        $cartItems = $this->getCartSummary(cartId: $cartId);
        if ($isStockShort) {
            return response()->json([
                'qty' => $remainingStock,
                'productSubtotal' => setCurrencySymbol(amount: usdToDefaultCurrency(amount: $cartItems['productSubtotal'])),
                'productType' => $product['product_type'],
                'productCounts' => $this->posHoldOrderService->getProductCounts(),
                'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }

        return response()->json([
            'quantityUpdate' => 1,
            'productSubtotal' => setCurrencySymbol(amount: usdToDefaultCurrency(amount: $cartItems['productSubtotal'])),
            'productCounts' => $this->posHoldOrderService->getProductCounts(),
            'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            'adjustmentNotices' => $this->adjustmentNoticeMessages(adjustments: $adjustments),
        ]);
    }

    public function addToCart(AddRequest $request): JsonResponse
    {
        $variations = [];
        $cartId = $this->posHoldOrderService->getCurrentCartId();
        $product = $this->retailProductService->find(id: $request['id'], relations: ['digitalVariation', 'clearanceSale' => function ($query) {
            return $query->active();
        }]);
        $colorName = $this->colorService->findBy(params: ['code' => $request['color']])->name ?? null;
        if (isset($colorName)) {
            $variations['color'] = $colorName;
        }
        $variant = $this->posHoldOrderService->makeVariation(
            input: $request->all(),
            colorName: $colorName,
            choiceOptions: json_decode($product['choice_options'])
        );
        $digitalVariation = $this->findRequestedDigitalVariation(request: $request, product: $product);
        if ($digitalVariation !== null) {
            $variant = $digitalVariation['variant_key'];
        }
        if ($this->posHoldOrderService->isVariationMissing(product: $product, variant: $variant)) {
            return response()->json([
                'data' => 'custom-error',
                'title' => translate('Cart'),
                'text' => translate('This option combination is not available'),
                'productCounts' => $this->posHoldOrderService->getProductCounts(),
            ]);
        }
        foreach (json_decode($product['choice_options']) as $choice) {
            $variations[$choice->title] = $request[$choice->name];
        }

        $price = $product['unit_price'];
        $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $price);
        if ($variant != null) {
            $price = $this->posHoldOrderService->getVariationPrice(variation: json_decode($product->retailDetail?->variation ?? '[]'), variant: $variant, productPrice: $product['unit_price']);
            $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $price);
        }
        if ($digitalVariation !== null) {
            $price = $digitalVariation['price'];
            $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $price);
        }

        $existingLineKey = $this->posHoldOrderService->findCartLineKey(productId: (int) $request['id'], variant: $variant);
        $quantity = (int) ($existingLineKey === null ? $request['quantity'] : ($request['quantity_in_cart'] ?? $request['quantity']));

        $remainingStock = $this->posHoldOrderService->getRemainingStock(variant: $variant, variation: json_decode($product->retailDetail?->variation ?? '[]'), productQty: $product['current_stock'], quantity: $quantity);
        if ($product['product_type'] == PosCartService::PRODUCT_TYPE_PHYSICAL && $remainingStock < 0) {
            $cartItems = $this->getCartSummary(cartId: $cartId);

            return response()->json([
                'data' => 0,
                'productCounts' => $this->posHoldOrderService->getProductCounts(),
                'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }

        if ($existingLineKey !== null) {
            $this->posHoldOrderService->replaceLine(
                lineKey: $existingLineKey,
                product: $product,
                quantity: $quantity,
                price: $price,
                discount: $discount,
                variant: $variant,
                variations: $variations,
            );
            $adjustments = $this->posHoldOrderService->refreshCartAdjustments(cartId: $cartId);
            $summaryData = array_merge($this->posHoldOrderService->getSummaryData(), $this->getCurrentCustomerSummary());
            $cartItems = $this->getCartSummary(cartId: $cartId);

            return response()->json([
                'data' => 1,
                'inCartData' => 1,
                'productCounts' => $this->posHoldOrderService->getProductCounts(),
                'requestQuantity' => $request['quantity_in_cart'],
                'view' => view('admin-views.pos.partials._cart', compact('summaryData', 'cartItems'))->render(),
                'adjustmentNotices' => $this->adjustmentNoticeMessages(adjustments: $adjustments),
            ]);
        }

        $sessionData = $this->posHoldOrderService->addLine(
            product: $product,
            quantity: $quantity,
            price: $price,
            discount: $discount,
            variant: $variant,
            variations: $variations,
        );
        $adjustments = $this->posHoldOrderService->refreshCartAdjustments(cartId: $cartId);
        $cartItems = $this->getCartSummary(cartId: $cartId);

        return response()->json([
            'data' => $sessionData,
            'productCounts' => $this->posHoldOrderService->getProductCounts(),
            'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            'adjustmentNotices' => $this->adjustmentNoticeMessages(adjustments: $adjustments),
        ]);
    }

    public function removeCart(RemoveRequest $request): JsonResponse
    {
        $cartId = $this->posHoldOrderService->getCurrentCartId();
        $this->posHoldOrderService->removeLine(productId: (int) $request['id'], variant: $request['variant']);
        $adjustments = $this->posHoldOrderService->refreshCartAdjustments(cartId: $cartId);
        $cartItems = $this->getCartSummary(cartId: $cartId);

        return response()->json([
            'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            'productCounts' => $this->posHoldOrderService->getProductCounts(),
            'adjustmentNotices' => $this->adjustmentNoticeMessages(adjustments: $adjustments),
        ]);
    }

    public function clearSessionCartIds(): RedirectResponse
    {
        if ($this->posHoldOrderService->clearAllCarts()) {
            ToastMagic::success(translate('Cart has been cleared'));
        } else {
            ToastMagic::warning(translate('Cart is already empty'));
        }

        return redirect()->route('admin.retail.pos.index');
    }

    public function getCartIds(): JsonResponse
    {
        $this->posHoldOrderService->normalizeCurrentCart();
        $summaryData = array_merge($this->posHoldOrderService->getSummaryData(), $this->getCurrentCustomerSummary());
        $cartItems = $this->getCartSummary(cartId: $this->posHoldOrderService->getCurrentCartId());

        return response()->json([
            'view' => view('admin-views.pos.partials._cart-summary', compact('summaryData', 'cartItems'))->render(),
        ]);
    }

    public function emptyCart(): JsonResponse
    {
        $cartId = $this->posHoldOrderService->emptyCurrentCart();
        $summaryData = array_merge($this->posHoldOrderService->getSummaryData(), $this->getCurrentCustomerSummary());
        $cartItems = $this->getCartSummary(cartId: $cartId);

        return response()->json([
            'view' => view('admin-views.pos.partials._cart-summary', compact('summaryData', 'cartItems'))->render(),
        ]);
    }

    public function changeCart(ResumeRequest $request): RedirectResponse
    {
        $this->posHoldOrderService->resumeCart(cartId: $request['cart_id']);
        ToastMagic::success(translate('Order for :customer resumed', ['customer' => $this->getCartCustomerName(cartId: $request['cart_id'])]));

        return redirect()->route('admin.retail.pos.index');
    }

    public function addNewCartId(): RedirectResponse
    {
        if ($this->posHoldOrderService->holdCurrentCart()) {
            ToastMagic::success(translate('This order is now on hold'));
        } else {
            ToastMagic::error(translate('Your cart is empty.'));
        }

        return redirect()->route('admin.retail.pos.index');
    }

    private function findRequestedDigitalVariation(Request $request, object $product): ?object
    {
        if ($product['product_type'] != PosCartService::PRODUCT_TYPE_DIGITAL || ! $request->has('variant_key')) {
            return null;
        }

        return collect($product['digitalVariation'])->last(fn ($digitalVariation) => $digitalVariation['variant_key'] == $request['variant_key']);
    }

    private function getCartCustomerName(string $cartId): string
    {
        $customerId = $this->posHoldOrderService->getCartCustomerId(cartId: $cartId);
        if ($customerId === null) {
            return translate('Walk-in customer');
        }

        $customer = $this->customerService->findBy(params: ['id' => $customerId]);

        return $customer ? trim($customer['f_name'].' '.$customer['l_name']) : translate('Customer');
    }

    private function adjustmentNoticeMessages(array $adjustments): array
    {
        return array_map(fn (string $adjustment): string => match ($adjustment) {
            PosCartService::ADJUSTMENT_COUPON_REMOVED_MIN_PURCHASE => translate('Coupon removed because the cart no longer meets its minimum purchase'),
            PosCartService::ADJUSTMENT_COUPON_REMOVED_EXCEEDS_TOTAL => translate('Coupon removed because its discount is more than the cart total'),
            PosCartService::ADJUSTMENT_EXTRA_DISCOUNT_REMOVED => translate('Extra discount removed because it is more than the cart total'),
        }, $adjustments);
    }
}
