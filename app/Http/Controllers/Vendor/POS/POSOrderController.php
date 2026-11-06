<?php

namespace App\Http\Controllers\Vendor\POS;

use App\Enums\SessionKey;
use App\Events\DigitalProductDownloadEvent;
use App\Http\Controllers\BaseController;
use App\Services\CartService;
use App\Services\New\CustomerService;
use App\Services\New\DigitalProductVariationService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\StorageService;
use App\Services\New\VendorService;
use App\Services\OrderDetailsService;
use App\Services\POSService;
use App\Traits\CalculatorTrait;
use App\Traits\CustomerTrait;
use App\Utils\CartManager;
use App\Utils\OrderManager;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class POSOrderController extends BaseController
{
    use CalculatorTrait;
    use CustomerTrait;
    use VatTaxManagement;

    public function __construct(
        private readonly ProductRetailService $retailProductService,
        private readonly CustomerService $customerService,
        private readonly OrderDetailService $orderDetailService,
        private readonly VendorService $vendorService,
        private readonly DigitalProductVariationService $digitalProductVariationService,
        private readonly StorageService $storageService,
        private readonly POSService $POSService,
        private readonly CartService $cartService,
        private readonly OrderDetailsService $orderDetailsService,
        private readonly OrderService $orderService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return $this->getOrderDetails(id: $type);
    }

    public function getOrderDetails(string $id): View|RedirectResponse
    {
        $vendorId = auth('seller')->id();
        $vendor = $this->vendorService->findBy(params: ['id' => $vendorId]);
        $getPOSStatus = getWebConfig('seller_pos');
        if ($vendor['pos_status'] == 0 || $getPOSStatus == 0) {
            ToastMagic::warning(translate('Access denied!!'));

            return redirect()->back();
        }
        $order = $this->orderService->findBy(params: ['id' => $id], relations: ['details', 'shipping', 'seller']);

        return view('vendor-views.pos.order.order-details', compact('order'));
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $amount = $request['amount'];
        $paidAmount = $request['type'] == 'cash' ? ($request['paid_amount'] ?? 0) : null;
        $cartId = session(SessionKey::CURRENT_USER);
        $condition = $this->POSService->checkConditions(amount: $amount, paidAmount: $paidAmount);
        if ($condition == 'true') {
            return response()->json();
        }
        $userId = $this->cartService->getUserId();
        if ($request['type'] == 'wallet' && $userId != 0) {
            $customerBalance = $this->customerService->findBy(params: ['id' => $userId]) ?? 0;
            if ($customerBalance['wallet_balance'] >= currencyConverter(amount: $amount)) {
                $this->createWalletTransaction(user_id: $userId, amount: floatval($amount), transaction_type: 'order_place', reference: 'order_place_in_pos');
            } else {
                ToastMagic::error(translate('Need Sufficient Amount Balance'));

                return response()->json();
            }
        }
        $checkProductTypeDigital = $this->cartService->checkProductTypeDigital(cartId: $cartId);
        if ($userId == 0 && $checkProductTypeDigital) {
            return response()->json(['checkProductTypeForWalkingCustomer' => true, 'message' => translate('To order a digital product, kindly fill up the customer form')]);
        }

        $taxConfig = self::getTaxSystemType();
        $cart = session($cartId);

        $cartListSession = $this->cartService->filterSessionCartList(cart: session($cartId));

        if (count($cartListSession) > 0) {
            $products = $this->retailProductService->getList(
                dataLimit: 'all',
                relations: ['clearanceSale' => fn ($query) => $query->active()],
                filters: ['id' => $cartListSession->pluck('id')->toArray()],
            );
            $totalDiscountedPrice = $this->cartService->getCartTotalDiscountPrice(session($cartId), $products, $userId);

            $orderId = $this->orderService->generatePosOrderId();
            $totalTaxAmount = 0;
            foreach ($cartListSession as $item) {
                if (is_array($item)) {
                    $product = $products->firstWhere('id', $item['id']);
                    if ($product) {
                        $price = $item['price'];
                        $digitalProductVariation = $this->digitalProductVariationService->findBy(params: ['product_id' => $item['id'], 'variant_key' => $item['variant']], relations: ['storage']);
                        if ($product['product_type'] == 'digital' && $digitalProductVariation) {
                            $price = $digitalProductVariation['price'];

                            if ($product->retailDetail?->digital_product_type == 'ready_product') {
                                $getStoragePath = $this->storageService->findBy(params: [
                                    'data_id' => $digitalProductVariation['id'],
                                    'data_type' => "App\Models\DigitalProductVariation",
                                ]);
                                $product['digital_file_ready'] = $digitalProductVariation['file'];
                                $product['storage_path'] = $getStoragePath ? $getStoragePath['value'] : 'public';
                            }
                        } elseif ($product->retailDetail?->digital_product_type == 'ready_product' && ! empty($product->retailDetail?->digital_file_ready)) {
                            $product['storage_path'] = $product->retailDetail?->digital_file_ready_storage_type ?? 'public';
                        }

                        $cartSubTotalCalculation = $this->cartService->getCartSubtotalCalculation(
                            product: $product,
                            cartItem: $item,
                            totalDiscountedPrice: $totalDiscountedPrice,
                            cartName: $cartId,
                        );

                        $orderDetail = $this->orderDetailsService->getPOSOrderDetailsData(
                            orderId: $orderId, item: $item,
                            product: $product, price: $price, tax: $cartSubTotalCalculation['appliedTaxAmount']
                        );
                        $totalTaxAmount += $cartSubTotalCalculation['appliedTaxAmount'];
                        $this->retailProductService->deductPosStock(product: $product, variant: $item['variant'], quantity: $item['quantity']);
                        $this->orderDetailService->create(data: $orderDetail);

                        $appliedTaxIds = CartManager::getAppliedTaxIds(
                            product: $product,
                            taxConfig: $taxConfig
                        );

                        $appliedTaxRate = collect($appliedTaxIds)->sum('tax_rate');
                        $finalAmount = ($item['price'] - $item['discount']) * $item['quantity'];

                        foreach ($appliedTaxIds as $taxItem) {
                            OrderManager::getAddOrderTaxDetails(
                                systemTaxVat: $taxConfig['SystemTaxVat'],
                                taxRate: $taxItem,
                                orderId: $orderId,
                                data: [
                                    'tax_amount' => ($cartSubTotalCalculation['appliedTaxAmount'] > 0 && $appliedTaxRate > 0) ? ($cartSubTotalCalculation['appliedTaxAmount'] * $taxItem['tax_rate']) / $appliedTaxRate : 0,
                                    'before_tax_amount' => $finalAmount,
                                    'after_tax_amount' => $finalAmount + $cartSubTotalCalculation['appliedTaxAmount'],
                                    'quantity' => $item['quantity'],
                                    'seller_id' => $product['seller_id'],
                                    'seller_type' => $product['seller_is'],
                                ]
                            );
                        }
                    }
                }
            }

            $order = $this->orderService->createPosOrder(
                orderId: $orderId,
                cart: $cart,
                amount: $amount,
                totalTaxAmount: $totalTaxAmount,
                paidAmount: $request['type'] == 'cash' ? $paidAmount : $amount,
                paymentType: $request['type'],
                addedBy: 'seller',
                userId: $userId,
                sellerId: auth('seller')->id(),
            );
            if ($checkProductTypeDigital) {
                $order = $this->orderService->findBy(params: ['id' => $orderId], relations: ['details.productAllStatus']);
                $data = [
                    'userName' => $order->customer->f_name,
                    'userType' => 'customer',
                    'templateName' => 'digital-product-download',
                    'order' => $order,
                    'subject' => translate('Download Digital Product'),
                    'title' => translate('Congratulations').'!',
                    'emailId' => $order->customer['email'],

                ];
                event(new DigitalProductDownloadEvent(email: $order->customer['email'], data: $data));
            }
            session()->forget($cartId);
            session(['last_order' => $orderId]);
            $this->cartService->getNewCartId();
            ToastMagic::success(translate('Order placed successfully'));
        }

        return response()->json();
    }

    public function cancelOrder(Request $request): JsonResponse
    {
        session()->remove($request['cart_id']);
        $totalHoldOrders = $this->POSService->getTotalHoldOrders();
        $cartNames = $this->POSService->getCartNames();
        $cartItems = $this->getHoldOrderCalculationData(cartNames: $cartNames);

        return response()->json([
            'message' => $request['cart_id'].' '.translate('Order is cancel'),
            'status' => 'success',
            'view' => view('vendor-views.pos.partials._view-hold-orders', compact('cartItems', 'totalHoldOrders'))->render(),
        ]);
    }

    public function getAllHoldOrdersView(Request $request): JsonResponse
    {
        $cartNames = $this->POSService->getCartNames();
        $cartItems = $this->getHoldOrderCalculationData(cartNames: $cartNames);
        $totalHoldOrders = $this->POSService->getTotalHoldOrders();
        if (! empty($request['customer'])) {
            $searchValue = strtolower($request['customer']);
            $filteredItems = collect($cartItems)->filter(function ($item) use ($searchValue) {
                return str_contains(strtolower($item['customerName']), $searchValue) !== false;
            });
            $cartItems = $filteredItems->all();
        }

        return response()->json([
            'flag' => 'inactive',
            'totalHoldOrders' => $totalHoldOrders,
            'view' => view('vendor-views.pos.partials._view-hold-orders', compact('totalHoldOrders', 'cartItems'))->render(),
        ]);
    }

    protected function getHoldOrderCalculationData(array $cartNames): array
    {
        $CustomerCartData = [];
        foreach ($cartNames as $cartId) {
            $CustomerData = $this->getCustomerCartData(cartName: $cartId);
            $CartItemData = $this->calculateCartItemsData(cartName: $cartId, customerCartData: $CustomerData);
            $CustomerCartData[$cartId] = array_merge($CustomerData[$cartId], $CartItemData);
        }

        return $CustomerCartData;
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
                            $subTotalCalculation['productSubtotal'] += $cartSubTotalCalculation['productSubtotal'];
                            $subTotalCalculation['subtotal'] += $cartSubTotalCalculation['subtotal'];
                            $subTotalCalculation['discountOnProduct'] += $cartSubTotalCalculation['discountOnProduct'];
                            $subTotalCalculation['totalTax'] += $cartSubTotalCalculation['appliedTaxAmount'];
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
}
