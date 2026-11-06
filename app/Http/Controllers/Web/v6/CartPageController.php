<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Services\New\CartService;
use App\Services\New\ShippingMethodService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartPageController extends Controller
{
    private const ORDER_NOTE_SESSION_KEY = 'order_note';

    private const APP_PAYMENT_MODE = 'app';

    private const LOGIN_REDIRECT_THEME = 'default';

    private const CUSTOMER_LOGIN_PATH = 'customer/auth/login';

    public function __construct(
        private readonly CartService $cartService,
        private readonly ShippingMethodService $shippingMethodService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->canViewCart()) {
            ToastMagic::warning(translate('Please login your account'));

            return theme_root_path() == self::LOGIN_REDIRECT_THEME
                ? redirect(self::CUSTOMER_LOGIN_PATH)
                : redirect('/');
        }

        $this->cartService->refreshPrices(
            customerId: $this->resolveCustomerId(),
            guestId: session('guest_id') ?? $request['guest_id'] ?? 0,
        );

        return view(themeViewName(key: 'cart_list'), [
            'topRatedShops' => [],
            'newSellers' => [],
            'currentDate' => date('Y-m-d H:i:s'),
            'request' => $request,
        ]);
    }

    public function updateOrderNote(Request $request): JsonResponse
    {
        if ($request->has(self::ORDER_NOTE_SESSION_KEY)) {
            session()->put(self::ORDER_NOTE_SESSION_KEY, $request[self::ORDER_NOTE_SESSION_KEY]);
        }

        return response()->json($this->getCheckoutBlockerResponse(request: $request));
    }

    private function canViewCart(): bool
    {
        if (auth('customer')->check() && $this->cartService->count(filters: ['customer_id' => auth('customer')->id()]) > 0) {
            return true;
        }

        return getWebConfig(name: 'guest_checkout') && session()->has('guest_id') && session('guest_id');
    }

    /** @return array{status: int, physical_product_view: bool, errorType?: string, redirect?: string, message: array<int, string>} */
    private function getCheckoutBlockerResponse(Request $request): array
    {
        $response = $this->cartService->getCheckoutBlockers(
            customerId: $this->resolveCustomerId(),
            sessionGuestId: session('guest_id'),
            requestGuestId: $request['guest_id'],
            activeShippingMethodCounts: $this->shippingMethodService->getActiveMethodCountsByCreator(),
        );

        $isAppRequest = $request['payment_request_from'] == self::APP_PAYMENT_MODE;
        $messages = array_map(
            fn (array $blocker): string => $this->checkoutBlockerMessage(blocker: $blocker, isAppRequest: $isAppRequest),
            $response['blockers'],
        );
        unset($response['blockers']);

        if (isset($response['redirect'])) {
            $response['redirect'] = $response['redirect'] === CartService::REDIRECT_HOME ? url('/') : route('shop-cart');
        }

        $response['message'] = $messages;

        return $response;
    }

    private function checkoutBlockerMessage(array $blocker, bool $isAppRequest): string
    {
        return match ($blocker['reason']) {
            CartService::REASON_BELOW_MINIMUM_ORDER_AMOUNT => $isAppRequest
                ? translate('Please complete the minimum order amount for :shop', ['shop' => $blocker['shop_name']])
                : translate('The minimum order amount for :shop is :amount', ['amount' => webCurrencyConverter(amount: $blocker['minimum_order_amount']), 'shop' => $blocker['shop_name']]),
            CartService::REASON_CART_EMPTY => translate('No items in basket'),
            CartService::REASON_NO_CHECKED_ITEMS => translate('Please add or check items before proceeding to checkout'),
            CartService::REASON_SHIPPING_NOT_AVAILABLE_FOR_SHOP => translate('Shipping Not Available for').' '.$blocker['shop_name'],
            CartService::REASON_SHIPPING_METHOD_REQUIRED => translate('Select shipping method'),
            CartService::REASON_SHOP_SHIPPING_METHOD_REQUIRED => translate('Select').' '.$blocker['shop_name'].' '.translate('Shipping Method'),
            CartService::REASON_UNAVAILABLE_VENDORS => translate('Please remove all products from unavailable vendors'),
            CartService::REASON_UNAVAILABLE_PRODUCT => translate('Please remove this unavailable product to continue'),
        };
    }

    private function resolveCustomerId(): ?int
    {
        return auth('customer')->id();
    }
}
