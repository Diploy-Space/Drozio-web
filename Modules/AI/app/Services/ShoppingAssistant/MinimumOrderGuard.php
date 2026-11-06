<?php

namespace Modules\AI\app\Services\ShoppingAssistant;

use App\Services\New\CartService;

class MinimumOrderGuard
{
    public function __construct(
        private readonly CartSelectionContext $context,
        private readonly CartService $cartService,
    ) {}

    /** @return array<int, array{shop:string,current:string,required:string,shortfall:string}>|null */
    public function blockingShops(): ?array
    {
        [$ownerId, $isGuest] = $this->cartOwner();
        $customerId = $isGuest ? null : $ownerId;
        $guestId = $isGuest ? $ownerId : null;

        $result = $this->cartService->verifyCartListMinimumOrderAmount(
            customerId: $customerId,
            sessionGuestId: $guestId,
            requestGuestId: $guestId,
        );

        if ((int) ($result['status'] ?? CartService::STATUS_SUCCESS) === CartService::STATUS_SUCCESS) {
            return null;
        }

        $shops = $this->shortfallByShop(ownerId: $ownerId, isGuest: $isGuest);

        return $shops ?: null;
    }

    /** @return array<int, array{shop:string,current:string,required:string,shortfall:string}> */
    private function shortfallByShop(int|string|null $ownerId, int $isGuest): array
    {
        if (! getWebConfig(name: 'minimum_order_amount_status')) {
            return [];
        }

        if ($ownerId === null) {
            return [];
        }

        $bySeller = getWebConfig(name: 'minimum_order_amount_by_seller');
        $inhouseMin = getWebConfig(name: 'minimum_order_amount');

        $customerId = $isGuest ? null : $ownerId;
        $guestId = $isGuest ? $ownerId : null;

        $groups = $this->cartService
            ->getCartItemList(customerId: $customerId, guestId: $guestId, type: CartService::TYPE_CHECKED)
            ->groupBy('cart_group_id');

        $shortfalls = [];

        foreach ($groups as $groupKey => $items) {
            $first = $items->first();
            $isAdmin = $first->seller_is === CartService::SELLER_IS_ADMIN;
            $minimum = $isAdmin ? $inhouseMin : ($bySeller ? ($first->seller->minimum_order_amount ?? 0) : 0);

            if (! $minimum) {
                continue;
            }

            $shippingCost = $this->cartService->getShippingCost(customerId: $customerId, guestId: $guestId, cartGroupId: $groupKey, type: CartService::TYPE_CHECKED);
            $subtotal = $this->cartService->getCartGrandTotal(customerId: $customerId, sessionGuestId: $guestId, requestGuestId: $guestId, cartGroupId: $groupKey, type: CartService::TYPE_CHECKED) - $shippingCost;

            if ($minimum > $subtotal) {
                $shortfalls[] = [
                    'shop' => $first->shop_info ?: ($isAdmin ? getInHouseShopConfig(key: 'name') : ($first->seller->shop->name ?? '')),
                    'current' => webCurrencyConverter(amount: $subtotal),
                    'required' => webCurrencyConverter(amount: $minimum),
                    'shortfall' => webCurrencyConverter(amount: $minimum - $subtotal),
                ];
            }
        }

        return $shortfalls;
    }

    private function cartOwner(): array
    {
        return $this->context->cartOwner();
    }
}
