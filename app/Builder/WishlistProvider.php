<?php

namespace App\Builder;

use App\Enums\BusinessModule;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Validation\ValidationException;
use Modules\Builder\app\Contracts\WishlistProvider as WishlistProviderContract;
use Modules\Builder\app\Services\StorefrontContext;

class WishlistProvider implements WishlistProviderContract
{
    public function __construct(
        private StorefrontContext $context,
        private ItemProvider $itemProvider,
    ) {}

    public function toggle(int $itemId): array
    {
        $customerId = $this->requireCustomerId();

        $existing = Wishlist::query()
            ->where('customer_id', $customerId)
            ->where('product_id', $itemId)
            ->whereHas('product', fn ($product) => $product->businessModule(BusinessModule::RETAIL))
            ->first();

        if ($existing) {
            $existing->delete();
            $inWishlist = false;
        } else {
            if (! Product::query()->businessModule(BusinessModule::RETAIL)->whereKey($itemId)->exists()) {
                throw ValidationException::withMessages([
                    'item_id' => translate('Product not found'),
                ]);
            }

            Wishlist::create([
                'customer_id' => $customerId,
                'product_id' => $itemId,
            ]);
            $inWishlist = true;
        }

        return [
            'inWishlist' => $inWishlist,
            'count' => $this->countFor($customerId),
        ];
    }

    public function count(): int
    {
        $customerId = $this->context->getUserId();

        return $customerId ? $this->countFor($customerId) : 0;
    }

    public function has(int $itemId): bool
    {
        $customerId = $this->context->getUserId();
        if (! $customerId) {
            return false;
        }

        return Wishlist::query()
            ->where('customer_id', $customerId)
            ->where('product_id', $itemId)
            ->whereHas('product', fn ($product) => $product->businessModule(BusinessModule::RETAIL))
            ->exists();
    }

    private function countFor(int $customerId): int
    {
        return $this->itemProvider->wishlistCount(scope: $this->context->getScope(), customerId: $customerId);
    }

    private function requireCustomerId(): int
    {
        $customerId = $this->context->getUserId();
        if (! $customerId) {
            throw ValidationException::withMessages([
                '_form' => translate('Sign in required') ?: 'Sign in required',
            ]);
        }

        return $customerId;
    }
}
