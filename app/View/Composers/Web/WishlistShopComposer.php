<?php

namespace App\View\Composers\Web;

use App\Services\New\ProductService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;

class WishlistShopComposer
{
    public function compose(View $view): void
    {
        $wishlists = $view->getData()['wishlists'] ?? null;

        $view->with('wishlistShops', $wishlists === null ? [] : $this->getShopsByProductId(wishlists: $wishlists));
    }

    /** @return array<int, array{name: string, slug: ?string, is_verified: bool}> */
    private function getShopsByProductId(iterable $wishlists): array
    {
        $shops = [];

        foreach ($wishlists as $wishlist) {
            $product = $wishlist->productFullInfo;

            if ($product !== null) {
                $shops[$product->id] = $this->getProductShop(product: $product);
            }
        }

        return $shops;
    }

    /** @return array{name: string, slug: ?string, is_verified: bool} */
    private function getProductShop(Model $product): array
    {
        if ($product->added_by === ProductService::ADDED_BY_SELLER) {
            return [
                'name' => (string) ($product->seller?->shop?->name ?? ''),
                'slug' => $product->seller?->shop?->slug,
                'is_verified' => (bool) $product->seller?->hasVerifiedBadge(),
            ];
        }

        return [
            'name' => (string) getInHouseShopConfig(key: 'name'),
            'slug' => getInHouseShopConfig(key: 'slug'),
            'is_verified' => isInHouseShopVerified(),
        ];
    }
}
