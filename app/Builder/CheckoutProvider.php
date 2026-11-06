<?php

namespace App\Builder;

use App\Enums\BusinessModule;
use App\Http\Controllers\Customer\PaymentController;
use App\Models\Cart;
use App\Models\CartShipping;
use App\Models\OfflinePaymentMethod;
use App\Models\Order;
use App\Models\ShippingAddress;
use App\Models\ShippingType;
use App\Models\Shop;
use App\Models\User;
use App\Services\New\BusinessSettingService;
use App\Services\New\ShopService;
use App\Utils\CartManager;
use App\Utils\Helpers;
use App\Utils\OrderManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Builder\app\Contracts\CheckoutProvider as CheckoutProviderContract;
use Modules\Builder\app\Contracts\CouponProvider;
use Modules\Builder\app\Contracts\PaymentMethodProvider;
use Modules\Builder\app\Services\StorefrontContext;
use Modules\Builder\app\ValueObjects\Storefront\CheckoutQuoteDTO;
use Modules\Builder\app\ValueObjects\Storefront\CheckoutSnapshotDTO;
use Modules\Builder\app\ValueObjects\StorefrontScope;

class CheckoutProvider implements CheckoutProviderContract
{
    private const TIP_PRESETS = [];

    public function __construct(
        private StorefrontContext $context,
        private PaymentMethodProvider $paymentMethods,
        private CouponProvider $coupons,
        private BusinessSettingService $businessSettingService,
        private ShopService $shopService,
    ) {}

    public function snapshot(?StorefrontScope $scope, ?int $customerId): CheckoutSnapshotDTO
    {
        $shop = $scope?->subTenantId ? Shop::find($scope->subTenantId) : null;

        $walletEnabled = (bool) config('builder.wallet_features_enabled', false) && (int) getWebConfig(name: 'wallet_status') === 1;
        $walletBalance = $customerId ? $this->money((float) (User::where('id', $customerId)->value('wallet_balance') ?? 0)) : 0.0;

        $hasPhysical = $this->cartHasPhysical($this->scopeCartToVendor($scope));

        $freeDeliveryOver = $this->freeDeliveryThreshold($shop);

        return CheckoutSnapshotDTO::fromArray([
            'store' => [
                'id' => (int) ($shop->id ?? 0),
                'name' => $shop->name ?? getWebConfig(name: 'company_name'),
                'open' => $this->storeOpen($shop),
                'minOrder' => $this->money((float) (getWebConfig(name: 'minimum_order_amount') ?? 0)),
                'freeDeliveryOver' => $freeDeliveryOver === null ? null : $this->money($freeDeliveryOver),
            ],
            'deliveryTypes' => [
                'delivery' => true,
                'pickup' => false,
                'schedule' => false,
                'scheduleSlotDuration' => 0,
            ],
            'paymentMethods' => [
                'cod' => ['enabled' => $hasPhysical && (int) getWebConfig(name: 'cash_on_delivery') === 1, 'allowChangeAmount' => true],
                'offline' => ['enabled' => $hasPhysical && (int) (getWebConfig(name: 'offline_payment')['status'] ?? 0) === 1, 'methods' => $this->offlineMethods()],
                'gateways' => $this->gatewayMethods(),
                'partial' => ['enabled' => false, 'method' => null],
                'wallet' => ['enabled' => $walletEnabled, 'balance' => $walletBalance],
            ],
            'features' => [
                'tipsEnabled' => false,
                'additionalCharge' => ['enabled' => false, 'name' => '', 'amount' => 0.0],
                'extraPackaging' => ['enabled' => false, 'fee' => 0.0],
                'taxIncluded' => (int) getWebConfig(name: 'tax_included') === 1,
            ],
            'tipPresets' => self::TIP_PRESETS,
            'mostTipped' => null,
            'scheduleSlots' => [],
        ]);
    }

    public function quote(?StorefrontScope $scope, ?int $customerId, array $state): CheckoutQuoteDTO
    {
        $cartType = $this->scopeCartToVendor($scope);
        $carts = CartManager::getCartListQuery(type: $cartType);

        $itemPrice = 0.0;
        $itemDiscount = 0.0;
        foreach ($carts as $cart) {
            $itemPrice += (float) $cart['price'] * (int) $cart['quantity'];
            $itemDiscount += (float) $cart['discount'] * (int) $cart['quantity'];
        }

        $this->ensureOrderWiseShippingSelected($cartType);
        $deliveryFee = (float) CartManager::get_shipping_cost(type: $cartType);
        $subtotalAfterDiscount = max(0.0, $itemPrice - $itemDiscount);

        [$couponCode, $couponTitle, $couponDiscount, $couponError, $couponFreeDelivery] = $this->resolveCoupon($state, $customerId, $scope, $subtotalAfterDiscount);

        if ($couponFreeDelivery) {
            $deliveryFee = 0.0;
        }

        $freeDelivery = $this->freeDeliveryState($cartType);
        $thresholdFreeDelivery = ! $couponFreeDelivery && $freeDelivery['shippingSaved'] > 0;
        if ($thresholdFreeDelivery) {
            $deliveryFee = max(0.0, $deliveryFee - $freeDelivery['shippingSaved']);
        }

        [$tax, $taxIncluded] = $this->calculateTax($carts, $couponDiscount, $deliveryFee);

        $total = max(0.0, $subtotalAfterDiscount - $couponDiscount + $tax + $deliveryFee);

        $minOrder = $this->minOrderState($cartType);
        $minQty = $this->minQtyState();

        return CheckoutQuoteDTO::fromArray([
            'itemPrice' => $this->money($itemPrice),
            'itemDiscount' => $this->money($itemDiscount),
            'couponCode' => $couponCode,
            'couponTitle' => $couponTitle,
            'couponDiscount' => $this->money($couponDiscount),
            'couponError' => $couponError,
            'tax' => $this->money($tax),
            'taxIncluded' => $taxIncluded,
            'taxEnabled' => $this->taxEnabled(),
            'deliveryFee' => $this->money($deliveryFee),
            'deliveryFeeNote' => null,
            'additionalCharge' => 0.0,
            'dmTip' => 0.0,
            'extraPackaging' => 0.0,
            'distance' => null,
            'total' => $this->money($total),
            'cashback' => null,
            'freeDelivery' => [
                'active' => $deliveryFee <= 0,
                'reason' => $couponFreeDelivery ? 'coupon' : ($thresholdFreeDelivery ? 'threshold' : null),
                'enabled' => $freeDelivery['status'],
                'threshold' => $this->money($freeDelivery['threshold']),
                'amountNeeded' => $this->money($freeDelivery['amountNeeded']),
                'progress' => $freeDelivery['progress'],
                'qualified' => $thresholdFreeDelivery,
            ],
            'minOrder' => [
                'enabled' => $minOrder['status'],
                'met' => $minOrder['met'],
                'threshold' => $this->money($minOrder['threshold']),
                'current' => $this->money($minOrder['current']),
                'amountNeeded' => $this->money($minOrder['amountNeeded']),
                'progress' => $minOrder['progress'],
            ],
            'minQty' => [
                'met' => $minQty['met'],
                'message' => $minQty['message'],
            ],
        ]);
    }

    /** @return array{status:bool,threshold:float,amountNeeded:float,progress:int,shippingSaved:float} */
    private function freeDeliveryState(?string $cartType): array
    {
        $empty = ['status' => false, 'threshold' => 0.0, 'amountNeeded' => 0.0, 'progress' => 0, 'shippingSaved' => 0.0];

        if ((int) (getWebConfig(name: 'free_delivery_status') ?? 0) !== 1) {
            return $empty;
        }

        $groupIds = CartManager::get_cart_group_ids(type: $cartType === 'checked' ? 'checked' : null);
        if ($groupIds === []) {
            return $empty;
        }

        $configured = false;
        $threshold = 0.0;
        $amountNeeded = 0.0;
        $shippingSaved = 0.0;
        $progress = 100;

        foreach ($groupIds as $groupId) {
            $group = OrderManager::getFreeDeliveryOrderAmountArray($groupId);
            if ((int) ($group['status'] ?? 0) !== 1) {
                continue;
            }

            $configured = true;
            $threshold = max($threshold, (float) ($group['amount'] ?? 0));
            $amountNeeded += max(0.0, (float) ($group['amount_need'] ?? 0));
            $shippingSaved += (float) ($group['shipping_cost_saved'] ?? 0);
            $progress = min($progress, (int) ($group['percentage'] ?? 0));
        }

        if (! $configured) {
            return $empty;
        }

        return [
            'status' => true,
            'threshold' => $threshold,
            'amountNeeded' => $amountNeeded,
            'progress' => $progress,
            'shippingSaved' => $shippingSaved,
        ];
    }

    /** @return array{status:bool,threshold:float,current:float,amountNeeded:float,progress:int,met:bool} */
    private function minOrderState(?string $cartType): array
    {
        $empty = ['status' => false, 'threshold' => 0.0, 'current' => 0.0, 'amountNeeded' => 0.0, 'progress' => 100, 'met' => true];

        if ((int) (getWebConfig(name: 'minimum_order_amount_status') ?? 0) !== 1) {
            return $empty;
        }

        $groupIds = CartManager::get_cart_group_ids(type: $cartType === 'checked' ? 'checked' : null);
        if ($groupIds === []) {
            return $empty;
        }

        $inhouseMin = (float) (getWebConfig(name: 'minimum_order_amount') ?? 0);
        $bySeller = (int) (getWebConfig(name: 'minimum_order_amount_by_seller') ?? 0) === 1;

        $configured = false;
        $threshold = 0.0;
        $current = 0.0;
        $amountNeeded = 0.0;
        $progress = 100;

        foreach ($groupIds as $groupId) {
            $firstItem = Cart::with('seller')
                ->businessModule(BusinessModule::RETAIL)
                ->where('cart_group_id', $groupId)
                ->where('is_checked', 1)
                ->first();
            if (! $firstItem) {
                continue;
            }

            $groupMin = $firstItem->seller_is === 'admin'
                ? $inhouseMin
                : ($bySeller ? (float) ($firstItem->seller->minimum_order_amount ?? 0) : 0.0);
            if ($groupMin <= 0) {
                continue;
            }

            $shipping = (float) CartManager::get_shipping_cost(groupId: $groupId, type: 'checked');
            $groupAmount = max(0.0, (float) CartManager::cart_grand_total(cartGroupId: $groupId, type: 'checked') - $shipping);

            $configured = true;
            $threshold = max($threshold, $groupMin);
            $current += $groupAmount;
            $amountNeeded += max(0.0, $groupMin - $groupAmount);
            $progress = min($progress, (int) min(100, floor($groupAmount / $groupMin * 100)));
        }

        if (! $configured) {
            return $empty;
        }

        return [
            'status' => true,
            'threshold' => $threshold,
            'current' => $current,
            'amountNeeded' => $amountNeeded,
            'progress' => $progress,
            'met' => $amountNeeded <= 0,
        ];
    }

    /** @return array{met:bool,message:?string} */
    private function minQtyState(): array
    {
        $result = OrderManager::validateStockAvailability(request());
        $met = (int) ($result['status'] ?? 1) === 1;

        return [
            'met' => $met,
            'message' => $met ? null : ($result['message'] ?? null),
        ];
    }

    private function orderRuleBlocker(int $shopperId, int $isGuestFlag): ?string
    {
        $request = request();
        if ($isGuestFlag === 1) {
            $request->merge(['guest_id' => $shopperId, 'is_guest' => 1]);
        }

        $minimumAmount = OrderManager::verifyCartListMinimumOrderAmount($request);
        if ((int) ($minimumAmount['status'] ?? 1) === 0) {
            $messages = array_filter($minimumAmount['messages'] ?? []);

            return $messages !== []
                ? implode(' ', $messages)
                : (translate('Please complete minimum Order Amount') ?: 'Minimum order amount is not met.');
        }

        $minimumQuantity = OrderManager::validateStockAvailability($request);
        if ((int) ($minimumQuantity['status'] ?? 1) === 0) {
            return $minimumQuantity['message'] ?? (translate('Minimum order quantity') ?: 'Minimum order quantity is not met.');
        }

        return null;
    }

    public function placeOrder(?StorefrontScope $scope, ?int $customerId, array $state): array
    {
        $isGuest = $this->context->isGuest();
        $shopperId = $this->context->getShopperId();
        $isGuestFlag = $this->context->shopperIsGuestFlag();

        if ($isGuest && (int) getWebConfig(name: 'guest_checkout') !== 1) {
            return $this->fail('auth', translate('Sign in required') ?: 'Please sign in to place an order.');
        }
        if (! $shopperId) {
            return $this->fail('auth', translate('Sign in required') ?: 'Please sign in to place an order.');
        }

        if ($isGuestFlag === 1) {
            session(['guest_id' => $shopperId]);
            request()->merge(['is_guest' => 1, 'guest_id' => $shopperId]);
        }

        try {
            $lock = Cache::lock('storefront:place-order:'.$isGuestFlag.'-'.$shopperId, 20);
            $acquired = $lock->get();
        } catch (\Throwable) {
            $lock = null;
            $acquired = true;
        }
        if (! $acquired) {
            return $this->fail('order', translate('Order placement in progress') ?: 'Your order is already being placed — please wait a moment.');
        }

        try {
            return $this->runPlaceOrder($scope, $shopperId, $isGuestFlag, $state);
        } finally {
            $lock?->release();
        }
    }

    private function runPlaceOrder(?StorefrontScope $scope, int $shopperId, int $isGuestFlag, array $state): array
    {
        $cartType = $this->scopeCartToVendor($scope);

        $carts = Cart::query()->businessModule(BusinessModule::RETAIL)->where('customer_id', $shopperId)->where('is_guest', $isGuestFlag)
            ->when($cartType === 'checked', fn ($query) => $query->where('is_checked', 1))
            ->get();
        if ($carts->isEmpty()) {
            return $this->fail('cart', translate('Your cart is empty') ?: 'Your cart is empty.');
        }

        $this->ensureOrderWiseShippingSelected($cartType);

        if ($this->unavailableShippingShops($cartType) !== []) {
            return $this->fail('order', translate('No shipping method available for this shop please contact the vendor') ?: 'This shop currently has no shipping method available. Please contact the vendor.');
        }

        if ($blocker = $this->orderRuleBlocker($shopperId, $isGuestFlag)) {
            return $this->fail('order', $blocker);
        }

        $addressId = $this->resolveAddressId($shopperId, $isGuestFlag, $state);
        if (! $addressId) {
            return $this->fail('address', translate('Please update address information') ?: 'Please add a delivery address.');
        }
        session(['address_id' => $addressId, 'billing_address_id' => $addressId]);

        $method = strtolower((string) ($state['paymentMethod'] ?? 'cash_on_delivery'));

        if (! $carts->contains(fn ($cart) => $cart->product_type === 'physical')
            && in_array($method, ['cash_on_delivery', 'cod', 'offline_payment'], true)) {
            return $this->fail('payment', translate('Digital products must be paid online') ?: 'Digital products must be paid with a digital payment method.');
        }

        if ($this->isDigitalGateway($method)) {
            return $this->digitalPaymentRedirect($scope, $shopperId, $isGuestFlag, $state, $method);
        }

        $paymentData = $this->paymentData($method, $state);
        if (isset($paymentData['error'])) {
            return $paymentData['error'];
        }

        try {
            $orderIds = DB::transaction(function () use ($scope, $addressId, $state, $paymentData, $shopperId, $isGuestFlag) {
                $this->scopeCartToVendor($scope);

                return OrderManager::generateOrder(data: array_merge([
                    'coupon_code' => $state['couponCode'] ?? session('coupon_code'),
                    'address_id' => $addressId,
                    'billing_address_id' => $addressId,
                    'transaction_ref' => '',
                    'is_guest' => $isGuestFlag,
                    'guest_id' => $isGuestFlag === 1 ? $shopperId : null,
                ], $paymentData));
            });
        } catch (\Throwable $exception) {
            \Log::warning('Storefront order placement failed', ['customer_id' => $shopperId, 'is_guest' => $isGuestFlag, 'error' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);

            return $this->fail('order', translate('Could not place the order') ?: 'Could not place the order.');
        }

        $orderId = is_array($orderIds) ? (int) ($orderIds[0] ?? 0) : 0;
        if ($orderId <= 0) {
            return $this->fail('order', translate('Could not place the order') ?: 'Could not place the order.');
        }

        return [
            'success' => true,
            'orderId' => $orderId,
            'paymentRedirect' => null,
            'message' => translate('Order placed successfully') ?: 'Order placed successfully.',
            'total' => $this->money(CartManager::cart_grand_total(type: $cartType)),
        ];
    }

    /** @return array{0: float, 1: bool} */
    private function taxEnabled(): bool
    {
        $setup = OrderManager::getTaxSystemType()['SystemTaxVat'] ?? null;

        return getCheckAddonPublishedStatus('TaxModule') && $setup && (int) ($setup->is_active ?? 0) === 1;
    }

    private function calculateTax(iterable $carts, float $couponDiscount, float $deliveryFee): array
    {
        $taxConfig = OrderManager::getTaxSystemType();
        if (! empty($taxConfig['is_included'])) {
            return [0.0, true];
        }

        $totalDiscountedPrice = 0.0;
        $applicableShippingAmount = 0.0;
        foreach ($carts as $cart) {
            if (! $cart->product) {
                continue;
            }
            $line = ((float) $cart['price'] - (float) $cart['discount']) * (int) $cart['quantity'];
            $totalDiscountedPrice += $line;
            if (($cart->product->product_type ?? 'physical') !== 'digital') {
                $applicableShippingAmount += $line;
            }
        }

        $tax = 0.0;
        foreach ($carts as $cart) {
            if (! $cart->product) {
                continue;
            }
            $line = ((float) $cart['price'] - (float) $cart['discount']) * (int) $cart['quantity'];
            $couponAllocation = $totalDiscountedPrice > 0 ? ($couponDiscount * $line) / $totalDiscountedPrice : 0.0;

            $tax += (float) CartManager::getAppliedTaxAmount(
                product: $cart->product,
                taxConfig: $taxConfig,
                totalDiscountedPrice: $line,
                appliedDiscountedAmount: $couponAllocation,
            );

            if (($cart->product->product_type ?? 'physical') !== 'digital') {
                $tax += (float) CartManager::getAppliedShippingTaxAmount(
                    taxConfig: $taxConfig,
                    totalShippingCost: $deliveryFee,
                    totalDiscountedPrice: $line,
                    applicableShippingAmount: $applicableShippingAmount,
                );
            }
        }

        return [max(0.0, $tax), false];
    }

    private function ensureOrderWiseShippingSelected(?string $cartType): void
    {
        if (! config('builder.order_wise_shipping_auto_select', false)) {
            return;
        }

        foreach ($this->orderWiseGroups($cartType) as $group) {
            if (CartShipping::where('cart_group_id', $group['id'])->exists()) {
                continue;
            }

            $method = $group['methods']->sortBy('cost')->first();
            if ($method) {
                $this->persistCartShipping((string) $group['id'], $method);
            }
        }
    }

    public function shippingMethods(?StorefrontScope $scope, ?int $customerId): array
    {
        if (! config('builder.order_wise_shipping_auto_select', false)) {
            return ['enabled' => false, 'groups' => []];
        }

        $cartType = $this->scopeCartToVendor($scope);
        $this->ensureOrderWiseShippingSelected($cartType);

        $groups = [];
        foreach ($this->orderWiseGroups($cartType) as $group) {
            if ($group['methods']->isEmpty()) {
                continue;
            }

            $selectedId = CartShipping::where('cart_group_id', $group['id'])->value('shipping_method_id');

            $groups[] = [
                'id' => (string) $group['id'],
                'shop' => $group['shop'],
                'selectedId' => $selectedId ? (int) $selectedId : null,
                'methods' => $group['methods']->sortBy('cost')->values()->map(fn ($method) => [
                    'id' => (int) $method->id,
                    'title' => (string) $method->title,
                    'cost' => (float) webCurrencyConverterOnlyDigit(amount: (float) $method->cost),
                    'duration' => $method->duration ? (string) $method->duration : null,
                ])->all(),
            ];
        }

        return ['enabled' => $groups !== [], 'groups' => $groups];
    }

    private function unavailableShippingShops(?string $cartType): array
    {
        if (! config('builder.order_wise_shipping_auto_select', false)) {
            return [];
        }

        $shops = [];
        foreach ($this->orderWiseGroups($cartType) as $group) {
            if ($group['methods']->isEmpty()) {
                $shops[] = $group['shop'];
            }
        }

        return $shops;
    }

    public function selectShippingMethod(?StorefrontScope $scope, ?int $customerId, string $cartGroupId, int $shippingMethodId): array
    {
        $cartType = $this->scopeCartToVendor($scope);

        $group = collect($this->orderWiseGroups($cartType))->firstWhere('id', $cartGroupId);
        if (! $group) {
            return ['success' => false, 'error' => translate('Shipping Not Available for this Shop') ?: 'Shipping is not available for this group.'];
        }

        $method = $group['methods']->firstWhere('id', $shippingMethodId);
        if (! $method) {
            return ['success' => false, 'error' => translate('Not found') ?: 'Selected shipping method not found.'];
        }

        $this->persistCartShipping($cartGroupId, $method);

        return ['success' => true];
    }

    /** @return array<int, array{id: mixed, shop: string, methods: Collection}> */
    private function orderWiseGroups(?string $cartType): array
    {
        $shippingMethodConfig = getWebConfig(name: 'shipping_method');
        $adminShipping = ShippingType::where('seller_id', 0)->first();

        $cartItems = Cart::query()->businessModule(BusinessModule::RETAIL)->where('product_type', 'physical')
            ->whereHas('product', fn ($query) => $query->active())
            ->whereIn('cart_group_id', CartManager::get_cart_group_ids(type: $cartType === 'checked' ? 'checked' : null))
            ->when($cartType === 'checked', fn ($query) => $query->where('is_checked', 1))
            ->get();

        $groups = [];
        foreach ($cartItems->groupBy('cart_group_id') as $cartGroupId => $groupItems) {
            $first = $groupItems->first();

            if ($shippingMethodConfig === 'inhouse_shipping' || $first?->seller_is === 'admin') {
                $shippingType = $adminShipping->shipping_type ?? 'order_wise';
                $methods = $shippingType === 'order_wise' ? Helpers::getShippingMethods(0, 'admin') : collect();
            } else {
                $sellerShipping = ShippingType::where('seller_id', $first?->seller_id)->first();
                $shippingType = $sellerShipping->shipping_type ?? 'order_wise';
                $methods = $shippingType === 'order_wise' ? Helpers::getShippingMethods($first?->seller_id, 'seller') : collect();
            }

            if ($shippingType !== 'order_wise') {
                continue;
            }

            $groups[] = [
                'id' => $cartGroupId,
                'shop' => $this->groupShopName($first),
                'methods' => $methods,
            ];
        }

        return $groups;
    }

    private function groupShopName($cartItem): string
    {
        if (($cartItem?->seller_is ?? null) === 'admin') {
            return getWebConfig(name: 'company_name') ?: 'In-House';
        }

        return Shop::where('seller_id', $cartItem?->seller_id)->value('name')
            ?? (translate('Shop') ?: 'Shop');
    }

    private function persistCartShipping(string $cartGroupId, $method): void
    {
        $cartShipping = CartShipping::firstOrNew(['cart_group_id' => $cartGroupId]);
        $cartShipping->shipping_method_id = $method->id;
        $cartShipping->shipping_cost = $method->cost;
        $cartShipping->save();
    }

    private function cartHasPhysical(?string $cartType): bool
    {
        $groupIds = CartManager::get_cart_group_ids(type: $cartType === 'checked' ? 'checked' : null);
        if ($groupIds === []) {
            return false;
        }

        return Cart::query()->businessModule(BusinessModule::RETAIL)->whereIn('cart_group_id', $groupIds)
            ->where('product_type', 'physical')
            ->whereHas('product', fn ($query) => $query->active())
            ->when($cartType === 'checked', fn ($query) => $query->where('is_checked', 1))
            ->exists();
    }

    private function scopeCartToVendor(?StorefrontScope $scope): string
    {
        $vendorId = $scope?->tenantId;
        $shopperId = $this->context->getShopperId();

        if (! $vendorId || $shopperId === null) {
            return 'all';
        }

        $isGuest = $this->context->shopperIsGuestFlag();

        Cart::query()->businessModule(BusinessModule::RETAIL)->where('customer_id', $shopperId)->where('is_guest', $isGuest)
            ->update(['is_checked' => 0]);
        Cart::query()->businessModule(BusinessModule::RETAIL)->where('customer_id', $shopperId)->where('is_guest', $isGuest)
            ->where('seller_id', $vendorId)->where('seller_is', 'seller')
            ->update(['is_checked' => 1]);

        return 'checked';
    }

    private function paymentData(string $method, array $state): array
    {
        if (in_array($method, ['cash_on_delivery', 'cod'], true)) {
            return [
                'order_status' => 'pending',
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'unpaid',
                'bring_change_amount' => $state['bringChange'] ?? 0,
                'bring_change_amount_currency' => session('currency_code'),
            ];
        }

        if ($method === 'offline_payment') {
            $offline = (array) ($state['offlinePayment'] ?? []);
            $info = [];
            if (! empty($offline['methodId'])) {
                $info['method_id'] = $offline['methodId'];
                $info['method_name'] = $offline['methodName'] ?? '';
                foreach ((array) ($offline['fields'] ?? []) as $field => $value) {
                    $info[$field] = $value;
                }
            }

            return [
                'order_status' => 'pending',
                'payment_method' => 'offline_payment',
                'payment_status' => 'unpaid',
                'payment_note' => $offline['customerNote'] ?? null,
                'offline_payment_info' => $info,
            ];
        }

        return ['error' => $this->fail('payment', translate('This payment method is currently unavailable') ?: 'This payment method is currently unavailable.')];
    }

    private function isDigitalGateway(string $method): bool
    {
        if ((int) getWebConfig(name: 'digital_payment') !== 1) {
            return false;
        }

        foreach ($this->paymentMethods->digitalPaymentMethods() as $gateway) {
            if (strtolower((string) $gateway['key']) === $method) {
                return true;
            }
        }

        return false;
    }

    private function digitalPaymentRedirect(?StorefrontScope $scope, int $customerId, int $isGuestFlag, array $state, string $method): array
    {
        $subtotal = 0.0;
        foreach (CartManager::getCartListQuery(type: $this->scopeCartToVendor($scope)) as $cart) {
            $subtotal += ((float) $cart['price'] - (float) $cart['discount']) * (int) $cart['quantity'];
        }

        [$couponCode, , $couponDiscount, $couponError] = $this->resolveCoupon($state, $customerId, $scope, max(0.0, $subtotal));
        if ($couponError) {
            $couponCode = null;
            $couponDiscount = 0.0;
        }

        session([
            'coupon_code' => $couponCode,
            'coupon_discount' => $couponDiscount,
            'order_note' => $state['instructions'] ?? null,
        ]);

        $request = request();
        $request->merge([
            'payment_method' => $method,
            'payment_platform' => 'web',
            'payment_request_from' => 'web',
            'payment_mode' => 'web',
            'is_guest' => $isGuestFlag,
            'guest_id' => $isGuestFlag ? $customerId : 0,
            'current_currency_code' => session('currency_code'),
            'external_redirect_link' => route('storefront.digital_payment_result'),
        ]);

        $redirect = app(PaymentController::class)->getCustomerPaymentRequest($request);

        if (! is_string($redirect) || trim($redirect) === '') {
            return $this->fail('payment', translate('This payment method is currently unavailable') ?: 'This payment method is currently unavailable.');
        }

        return [
            'success' => true,
            'orderId' => 0,
            'paymentRedirect' => $this->paymentUrlOnHostDomain($redirect),
        ];
    }

    private function paymentUrlOnHostDomain(string $url): string
    {
        $hostDomain = config('app.host_domain');
        if (empty($hostDomain)) {
            return $url;
        }

        $parts = parse_url($url) ?: [];
        if (($parts['host'] ?? null) === $hostDomain) {
            return $url;
        }

        $scheme = $parts['scheme'] ?? (request()->isSecure() ? 'https' : 'http');
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $scheme.'://'.$hostDomain.$path.$query;
    }

    private function storeOpen(?Shop $shop): bool
    {
        if (! $shop) {
            return true;
        }

        return ! $this->shopService->isTemporarilyClosed(shop: $shop, businessModule: BusinessModule::RETAIL)
            && ! $this->shopService->isOnVacationNow(shop: $shop, businessModule: BusinessModule::RETAIL);
    }

    private function freeDeliveryThreshold(?Shop $shop): ?float
    {
        $fields = BusinessSettingService::IN_HOUSE_FREE_DELIVERY_SETTINGS[BusinessModule::RETAIL];
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => array_values($fields)])
            ->pluck('value', 'type');

        $status = $settings->get($fields['status']);
        $amount = $settings->get($fields['amount']);

        $isActive = $status === null ? (float) $amount > 0 : (bool) $status;

        if (! $isActive) {
            return null;
        }

        return is_numeric($amount) ? (float) $amount : null;
    }

    private function offlineMethods(): array
    {
        if ((int) (getWebConfig(name: 'offline_payment')['status'] ?? 0) !== 1) {
            return [];
        }

        return OfflinePaymentMethod::where('status', 1)
            ->get(['id', 'method_name', 'method_fields', 'method_informations'])
            ->map(fn (OfflinePaymentMethod $method) => [
                'id' => $method->id,
                'name' => $method->method_name,
                'fields' => $method->method_fields ?? [],
                'informations' => $method->method_informations ?? [],
            ])
            ->values()
            ->all();
    }

    private function gatewayMethods(): array
    {
        if ((int) getWebConfig(name: 'digital_payment') !== 1) {
            return [];
        }

        return array_map(fn (array $method) => [
            'key' => $method['key'],
            'title' => $method['label'],
            'imageUrl' => $method['image'],
        ], $this->paymentMethods->digitalPaymentMethods());
    }

    private function resolveCoupon(array $state, ?int $customerId, ?StorefrontScope $scope, float $subtotal): array
    {
        $code = trim((string) ($state['couponCode'] ?? ''));
        if ($code === '') {
            return [null, null, 0.0, null, false];
        }

        $result = $this->coupons->validate($code, $customerId, $scope, $subtotal);
        if (! ($result['ok'] ?? false)) {
            return [$code, null, 0.0, $result['error'] ?? (translate('Invalid coupon') ?: 'Invalid coupon.'), false];
        }

        return [$result['code'], $result['title'] ?? null, (float) ($result['discount'] ?? 0), null, (bool) ($result['freeDelivery'] ?? false)];
    }

    private function resolveAddressId(int $customerId, int $isGuestFlag, array $state): ?int
    {
        $addressId = $state['addressId'] ?? null;
        if ($addressId && ShippingAddress::where('id', $addressId)->where('customer_id', $customerId)->where('is_guest', $isGuestFlag)->exists()) {
            return (int) $addressId;
        }

        if ($isGuestFlag === 1) {
            return $this->createGuestAddress($customerId, $state);
        }

        $latest = ShippingAddress::where('customer_id', $customerId)->where('is_guest', 0)->where('is_saved', true)->latest()->value('id');

        return $latest ? (int) $latest : null;
    }

    private function createGuestAddress(int $guestId, array $state): ?int
    {
        $name = trim((string) ($state['contactName'] ?? ''));
        $phone = trim((string) ($state['contactPhone'] ?? ''));
        $email = trim((string) ($state['contactEmail'] ?? ''));
        $address = trim((string) ($state['address'] ?? ''));

        if ($name === '' || $phone === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $address === '') {
            return null;
        }

        return (int) ShippingAddress::insertGetId([
            'customer_id' => $guestId,
            'is_guest' => 1,
            'contact_person_name' => $name,
            'phone' => $phone,
            'email' => $email,
            'address_type' => $state['addressType'] ?? 'Delivery',
            'address' => $address,
            'latitude' => $state['lat'] ?? null,
            'longitude' => $state['lng'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'errors' => [['code' => $code, 'message' => $message]]];
    }

    private function money(float|int|null $amount): float
    {
        return (float) webCurrencyConverterOnlyDigit(amount: (float) ($amount ?? 0));
    }

    public function resolveDigitalPaymentReturn(array $query): array
    {
        $raw = $query['order_ids'] ?? null;
        if (! $raw) {
            return ['orderId' => null, 'phone' => null];
        }

        $decoded = json_decode(base64_decode(str_replace(' ', '+', (string) $raw)) ?: '[]', true);
        $orderId = is_array($decoded) ? (int) ($decoded[0] ?? 0) : 0;
        if ($orderId <= 0) {
            return ['orderId' => null, 'phone' => null];
        }

        $order = Order::query()->businessModule(BusinessModule::RETAIL)->select(['shipping_address_data'])->find($orderId);
        $address = $order?->shipping_address_data;
        $address = is_string($address) ? json_decode($address, true) : (array) $address;

        return ['orderId' => $orderId, 'phone' => $address['phone'] ?? $address['contact_person_number'] ?? null];
    }
}
