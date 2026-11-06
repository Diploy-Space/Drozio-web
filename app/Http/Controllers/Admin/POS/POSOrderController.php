<?php

namespace App\Http\Controllers\Admin\POS;

use App\Events\DigitalProductDownloadEvent;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\Admin\Pos\HoldOrder\CancelRequest;
use App\Http\Requests\Admin\Pos\Order\PlaceRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\CustomerService;
use App\Services\New\DigitalProductVariationService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderService;
use App\Services\New\Pos\PosCartService;
use App\Services\New\Pos\PosHoldOrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\StorageService;
use App\Services\OrderDetailsService;
use App\Traits\CustomerTrait;
use App\Utils\CartManager;
use App\Utils\OrderManager;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TaxModule\app\Traits\VatTaxManagement;
use RuntimeException;
use Throwable;

class POSOrderController extends PosBaseController
{
    use CustomerTrait;
    use VatTaxManagement;

    public function __construct(
        ProductRetailService $retailProductService,
        CustomerService $customerService,
        PosHoldOrderService $posHoldOrderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly DigitalProductVariationService $digitalProductVariationService,
        private readonly StorageService $storageService,
        private readonly OrderDetailsService $orderDetailsService,
        private readonly OrderService $orderService,
        private readonly BusinessSettingService $businessSettingService,
    ) {
        parent::__construct(retailProductService: $retailProductService, customerService: $customerService, posHoldOrderService: $posHoldOrderService);
    }

    public function index(?Request $request, ?string $type = null): RedirectResponse
    {
        return redirect()->route('admin.retail.orders.details', ['id' => $type]);
    }

    public function placeOrder(PlaceRequest $request): JsonResponse
    {
        $cartId = $this->posHoldOrderService->findCurrentCartId();
        $cart = $this->posHoldOrderService->getCart(cartId: $cartId);

        if ($this->posHoldOrderService->getUniqueCartLines(cart: $cart)->isEmpty()) {
            return $this->orderPlacementFailure(message: translate('Cart empty warning'));
        }

        $userId = $this->posHoldOrderService->getCurrentCustomerId();
        $cartLines = $this->posHoldOrderService->getCustomerCartLines(cart: $cart, customerId: $userId);
        $hasDigitalProduct = $this->posHoldOrderService->hasDigitalProduct(cartId: $cartId);
        if ($userId == 0 && $hasDigitalProduct) {
            return response()->json(['checkProductTypeForWalkingCustomer' => true, 'message' => translate('To order a digital product, kindly fill up the customer form')]);
        }

        $paymentType = (string) $request['type'];
        $cartSummary = $this->getCartSummary(cartId: $cartId);
        $payableAmount = $cartSummary['payableAmount'];
        $totalTaxAmount = $cartSummary['totalTax'];
        $paidAmount = $paymentType == PosCartService::PAYMENT_TYPE_CASH ? (float) ($request['paid_amount'] ?? 0) : $payableAmount;

        $rejection = $this->posHoldOrderService->getOrderPlacementRejection(
            payableAmount: $payableAmount,
            postedAmount: (float) $request['amount'],
            paymentType: $paymentType,
            paidAmount: $paidAmount,
            customerId: $userId,
            isWalletEnabled: $this->isWalletEnabled(),
        );
        if ($rejection !== null) {
            return $this->orderPlacementFailure(message: $this->getOrderPlacementRejectionMessage(rejection: $rejection));
        }

        $isWalletPayment = $paymentType == PosCartService::PAYMENT_TYPE_WALLET;
        $orderLines = $this->getOrderLines(cartId: $cartId, cart: $cart, cartLines: $cartLines, userId: $userId);
        $this->posHoldOrderService->detachCart(cartId: $cartId);

        $stockShortage = null;
        $walletRejection = null;
        try {
            $orderId = DB::transaction(function () use ($cart, $orderLines, $payableAmount, $totalTaxAmount, $paidAmount, $paymentType, $isWalletPayment, $userId, &$stockShortage, &$walletRejection): int {
                if ($isWalletPayment && $this->isWalletBalanceShort(customer: $this->customerService->findForWalletUpdate(id: $userId), amount: $payableAmount)) {
                    $walletRejection = PosCartService::PLACEMENT_REJECTED_WALLET_BALANCE_SHORT;
                    throw new RuntimeException($walletRejection);
                }

                $orderId = $this->orderService->generatePosOrderId();

                $this->orderService->createPosOrder(
                    orderId: $orderId,
                    cart: $cart,
                    amount: $payableAmount,
                    totalTaxAmount: $totalTaxAmount,
                    paidAmount: $paidAmount,
                    paymentType: $paymentType,
                    addedBy: 'admin',
                    userId: $userId,
                    sellerId: auth('admin')->id(),
                );

                foreach ($orderLines as $orderLine) {
                    if (! $this->retailProductService->deductPosStockIfAvailable(product: $orderLine['product'], variant: $orderLine['item']['variant'], quantity: $orderLine['item']['quantity'])) {
                        $stockShortage = $orderLine;
                        throw new InsufficientStockException;
                    }
                    $this->createOrderLine(orderId: $orderId, orderLine: $orderLine);
                }

                if ($isWalletPayment && $this->createWalletTransaction(user_id: $userId, amount: $payableAmount, transaction_type: 'order_place', reference: 'order_place_in_pos') === false) {
                    $walletRejection = PosCartService::PLACEMENT_REJECTED_WALLET_DEBIT_FAILED;
                    throw new RuntimeException($walletRejection);
                }

                return $orderId;
            }, attempts: 2);
        } catch (InsufficientStockException) {
            $this->posHoldOrderService->restoreCart(cartId: $cartId, cart: $cart);

            return $this->orderPlacementFailure(message: translate('Only :count left of :product', [
                'count' => $this->retailProductService->getPosAvailableStock(productId: (int) $stockShortage['product']['id'], variant: $stockShortage['item']['variant']),
                'product' => $stockShortage['product']['name'],
            ]));
        } catch (Throwable $exception) {
            $this->posHoldOrderService->restoreCart(cartId: $cartId, cart: $cart);

            if ($walletRejection !== null) {
                return $this->orderPlacementFailure(message: $this->getOrderPlacementRejectionMessage(rejection: $walletRejection));
            }

            throw $exception;
        }

        if ($hasDigitalProduct) {
            $this->sendDigitalProductDownloadMail(orderId: $orderId);
        }

        $this->posHoldOrderService->rememberLastOrder(orderId: $orderId);
        $this->posHoldOrderService->openNewWalkInCart();
        ToastMagic::success(translate('Order placed successfully'));

        return response()->json();
    }

    private function orderPlacementFailure(string $message): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => $message]);
    }

    private function getOrderPlacementRejectionMessage(string $rejection): string
    {
        return match ($rejection) {
            PosCartService::PLACEMENT_REJECTED_CART_CHANGED => translate('The cart changed. Review the total and place the order again.'),
            PosCartService::PLACEMENT_REJECTED_ZERO_AMOUNT => translate('Order amount cannot be zero. Please enter a valid amount.'),
            PosCartService::PLACEMENT_REJECTED_WALLET_WITHOUT_CUSTOMER => translate('Select a customer to pay by wallet'),
            PosCartService::PLACEMENT_REJECTED_PAID_AMOUNT_SHORT => translate('Paid amount is less than total amount'),
            PosCartService::PLACEMENT_REJECTED_WALLET_DISABLED => translate('Wallet payment is turned off'),
            PosCartService::PLACEMENT_REJECTED_WALLET_BALANCE_SHORT => translate('Insufficient wallet balance'),
            PosCartService::PLACEMENT_REJECTED_WALLET_DEBIT_FAILED => translate('The wallet payment could not be completed. Please try again.'),
        };
    }

    private function isWalletEnabled(): bool
    {
        return (int) $this->businessSettingService->getValueByType(type: BusinessSettingService::WALLET_STATUS, default: 0) === 1;
    }

    private function isWalletBalanceShort(?Model $customer, float $amount): bool
    {
        return $customer === null || $customer['wallet_balance'] < currencyConverter(amount: $amount);
    }

    private function getOrderLines(string $cartId, mixed $cart, Collection $cartLines, int $userId): array
    {
        $products = $this->retailProductService->getList(
            dataLimit: 'all',
            relations: ['retailDetail', 'clearanceSale' => fn ($query) => $query->active()],
            filters: ['id' => $cartLines->pluck('id')->toArray()],
        );
        $totalDiscountedPrice = $this->posHoldOrderService->getCartDiscountedTotal(cart: $cart, products: $products, customerId: $userId);

        $orderLines = [];
        foreach ($cartLines as $item) {
            $product = $products->firstWhere('id', $item['id']);
            if (! $product) {
                continue;
            }

            $orderLines[] = [
                'item' => $item,
                'product' => $product,
                'price' => $this->prepareDigitalProductForOrder(product: $product, item: $item),
                'appliedTaxAmount' => $this->posHoldOrderService->calculateLineSubtotal(
                    product: $product,
                    cartItem: $item,
                    totalDiscountedPrice: $totalDiscountedPrice,
                    cartId: $cartId,
                )['appliedTaxAmount'],
            ];
        }

        return $orderLines;
    }

    private function prepareDigitalProductForOrder(object $product, array $item): float
    {
        $price = $item['price'];

        $digitalProductVariation = $this->digitalProductVariationService->findBy(params: ['product_id' => $item['id'], 'variant_key' => $item['variant']], relations: ['storage']);
        if ($product['product_type'] == PosCartService::PRODUCT_TYPE_DIGITAL && $digitalProductVariation) {
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

        return (float) $price;
    }

    private function createOrderLine(int $orderId, array $orderLine): void
    {
        $item = $orderLine['item'];
        $product = $orderLine['product'];
        $appliedTaxAmount = $orderLine['appliedTaxAmount'];

        $this->orderDetailService->create(data: $this->orderDetailsService->getPOSOrderDetailsData(
            orderId: $orderId, item: $item,
            product: $product, price: $orderLine['price'], tax: $appliedTaxAmount
        ));

        $taxConfig = self::getTaxSystemType();
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
                    'tax_amount' => ($appliedTaxAmount > 0 && $appliedTaxRate > 0) ? ($appliedTaxAmount * $taxItem['tax_rate']) / $appliedTaxRate : 0,
                    'before_tax_amount' => $finalAmount,
                    'after_tax_amount' => $finalAmount + $appliedTaxAmount,
                    'quantity' => $item['quantity'],
                    'seller_id' => $product['seller_id'],
                    'seller_type' => $product['seller_is'],
                ]
            );
        }
    }

    private function sendDigitalProductDownloadMail(int $orderId): void
    {
        $order = $this->orderService->findBy(params: ['id' => $orderId], relations: ['details.productAllStatus']);
        $data = [
            'userName' => $order?->customer?->f_name ?? '',
            'userType' => 'customer',
            'templateName' => 'digital-product-download',
            'order' => $order,
            'subject' => translate('Download Digital Product'),
            'title' => translate('Congratulations').'!',
            'emailId' => $order->customer['email'],
        ];
        event(new DigitalProductDownloadEvent(email: $order->customer['email'], data: $data));
    }

    public function cancelOrder(CancelRequest $request): JsonResponse
    {
        $this->posHoldOrderService->cancelHeldCart(cartId: $request['cart_id']);
        $totalHoldOrders = $this->posHoldOrderService->getTotalHoldOrders();
        $cartItems = $this->getHoldOrderList();

        return response()->json([
            'message' => translate('Held order cancelled'),
            'status' => 'success',
            'totalHoldOrders' => $totalHoldOrders,
            'view' => view('admin-views.pos.partials._view-hold-orders', compact('totalHoldOrders', 'cartItems'))->render(),
        ]);
    }

    public function getAllHoldOrdersView(Request $request): JsonResponse
    {
        $totalHoldOrders = $this->posHoldOrderService->getTotalHoldOrders();
        $cartItems = $this->getHoldOrderList();
        if (! empty($request['customer'])) {
            $searchValue = strtolower($request['customer']);
            $cartItems = collect($cartItems)->filter(fn ($item) => str_contains(strtolower($item['customerName']), $searchValue))->all();
        }

        return response()->json([
            'flag' => 'inactive',
            'totalHoldOrders' => $totalHoldOrders,
            'view' => view('admin-views.pos.partials._view-hold-orders', compact('totalHoldOrders', 'cartItems'))->render(),
        ]);
    }
}
