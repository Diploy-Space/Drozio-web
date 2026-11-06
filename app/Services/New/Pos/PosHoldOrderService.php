<?php

namespace App\Services\New\Pos;

use App\Enums\SessionKey;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PosHoldOrderService extends PosCartService
{
    public const CUSTOMER_CHANGE_WALK_IN_CART_HELD = 'walk_in_cart_held';

    public const CUSTOMER_CHANGE_CUSTOMER_CART_HELD = 'customer_cart_held';

    public function getTotalHoldOrders(): int
    {
        return count($this->getHeldCartIds());
    }

    public function getHeldCartIds(): array
    {
        return array_values(array_filter(
            $this->session->get(SessionKey::CART_NAME) ?? [],
            function (string $cartName): bool {
                $firstLine = $this->cartLines(cart: $this->session->get($cartName))[0] ?? null;

                return $firstLine !== null && ! empty($firstLine['customerOnHold']);
            },
        ));
    }

    public function getCartNames(): array
    {
        $cartNames = [];
        foreach ($this->session->get(SessionKey::CART_NAME) ?? [] as $cartName) {
            if ($this->cartLines(cart: $this->session->get($cartName)) !== []) {
                $cartNames[] = $cartName;
            }
        }

        return $cartNames;
    }

    public function getSummaryData(): array
    {
        return [
            'cartName' => $this->session->get(SessionKey::CART_NAME),
            'currentUser' => $this->session->get(SessionKey::CURRENT_USER),
            'totalHoldOrders' => $this->getTotalHoldOrders(),
            'cartNames' => $this->getCartNames(),
        ];
    }

    public function getHoldOrderList(array $cartIds, Collection $products, Collection $customers): array
    {
        $holdOrders = [];
        foreach ($cartIds as $cartId) {
            $customerId = $this->getCartCustomerId(cartId: $cartId);
            $customer = $customerId === null ? null : $customers->firstWhere('id', $customerId);
            $cartSummary = $this->getCartSummary(cartId: $cartId, products: $products, customer: $customer);
            if (! $cartSummary['customerOnHold']) {
                continue;
            }
            $holdOrders[$cartId] = array_merge($cartSummary, $this->getHoldOrderDisplayData(cartId: $cartId, cartSummary: $cartSummary));
        }

        return $holdOrders;
    }

    public function holdCurrentCart(): bool
    {
        if ($this->cartLines(cart: $this->getCurrentCartItems()) === []) {
            return false;
        }

        $this->setCurrentCartHoldStatus(onHold: true);
        $this->openNewWalkInCart();

        return true;
    }

    public function resumeCart(string $cartId): void
    {
        $this->setCurrentCartHoldStatus(onHold: true);
        $this->session->put(SessionKey::CURRENT_USER, $cartId);
        $this->setCurrentCartHoldStatus(onHold: false);
    }

    public function cancelHeldCart(string $cartId): void
    {
        $this->session->remove($cartId);
    }

    public function switchCustomerCart(string $cartId): array
    {
        $currentCartId = (string) $this->session->get(SessionKey::CURRENT_USER);
        if ($cartId === $currentCartId) {
            return [];
        }

        $currentCart = $this->cartToArray(cart: $this->session->get($currentCartId));
        $isWalkInCart = Str::startsWith($currentCartId, self::WALK_IN_CART_PREFIX);

        if ($this->cartLines(cart: $currentCart) === []) {
            $this->discardCart(cartId: $currentCartId);
            $this->openCart(cartId: $cartId);

            return [];
        }

        if ($isWalkInCart && $this->cartLines(cart: $this->session->get($cartId)) === []) {
            return $this->carryCartToCustomer(fromCartId: $currentCartId, fromCart: $currentCart, toCartId: $cartId);
        }

        $this->session->put($currentCartId, $this->withHoldStatus(cart: $currentCart, onHold: true));
        $this->openCart(cartId: $cartId);

        return [$isWalkInCart ? self::CUSTOMER_CHANGE_WALK_IN_CART_HELD : self::CUSTOMER_CHANGE_CUSTOMER_CART_HELD];
    }

    public function carryCurrentCartToWalkIn(): array
    {
        $currentCartId = $this->getCurrentCartId();

        return $this->carryCartToCustomer(
            fromCartId: $currentCartId,
            fromCart: $this->cartToArray(cart: $this->session->get($currentCartId)),
            toCartId: $this->generateWalkInCartId(),
        );
    }

    private function getHoldOrderDisplayData(string $cartId, array $cartSummary): array
    {
        $addedAt = $this->cartToArray(cart: $this->session->get($cartId))['add_to_cart_time'] ?? null;

        return [
            'payableTotal' => round($cartSummary['total'] + $cartSummary['totalTax'] - $cartSummary['couponDiscount'], 2),
            'originalTotal' => round($cartSummary['subtotal'] + $cartSummary['discountOnProduct'] + $cartSummary['totalTax'], 2),
            'addedAt' => $addedAt instanceof CarbonInterface ? $addedAt : null,
        ];
    }

    private function carryCartToCustomer(string $fromCartId, array $fromCart, string $toCartId): array
    {
        $customerId = $this->isWalkInCart(cartId: $toCartId) ? self::WALK_IN_CUSTOMER_ID : explode('-', $toCartId)[2];
        $toCart = [];
        foreach ($this->withHoldStatus(cart: $fromCart, onHold: false) as $key => $cartItem) {
            if (is_array($cartItem)) {
                $cartItem['customerId'] = $customerId;
            }
            $toCart[$key] = $cartItem;
        }

        $adjustments = isset($toCart['coupon_code']) ? [self::ADJUSTMENT_COUPON_REMOVED_CUSTOMER_CHANGED] : [];

        $this->discardCart(cartId: $fromCartId);
        $this->session->put($toCartId, array_diff_key($toCart, array_flip(self::COUPON_KEYS)));
        $this->makeCurrentCart(cartId: $toCartId);

        return $adjustments;
    }

    private function openCart(string $cartId): void
    {
        $cart = $this->cartToArray(cart: $this->session->get($cartId));
        $this->session->put($cartId, $this->cartLines(cart: $cart) === [] ? [] : $this->withHoldStatus(cart: $cart, onHold: false));
        $this->makeCurrentCart(cartId: $cartId);
    }

    private function makeCurrentCart(string $cartId): void
    {
        if (! in_array($cartId, $this->session->get(SessionKey::CART_NAME) ?? [], true)) {
            $this->addCartName(cartId: $cartId);
        }
        $this->session->put(SessionKey::CURRENT_USER, $cartId);
    }

    private function discardCart(string $cartId): void
    {
        $this->session->forget($cartId);
        $this->session->put(SessionKey::CART_NAME, array_values(array_filter(
            $this->session->get(SessionKey::CART_NAME) ?? [],
            fn ($cartName) => $cartName !== $cartId,
        )));
    }

    private function setCurrentCartHoldStatus(bool $onHold): void
    {
        $cart = $this->getCurrentCartItems();
        $lines = $this->withHoldStatus(cart: $this->cartLines(cart: $cart), onHold: $onHold);
        $this->session->put($this->getCurrentCartId(), $this->buildCartFromLines(lines: $lines, cart: $cart));
    }

    private function withHoldStatus(array $cart, bool $onHold): array
    {
        foreach ($cart as $key => $cartItem) {
            if (is_array($cartItem)) {
                $cart[$key]['customerOnHold'] = $onHold;
            }
        }

        return $cart;
    }

    private function cartLines(mixed $cart): array
    {
        return array_values(array_filter($this->cartToArray(cart: $cart), 'is_array'));
    }
}
