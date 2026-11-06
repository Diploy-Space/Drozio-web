<?php

namespace App\Traits;

use App\Services\New\ProductRetailService;
use App\Services\New\ShopService;
use Illuminate\Database\Eloquent\Model;

trait ShopProductScopeTrait
{
    private const NO_MATCHING_PRODUCT_ID = 0;

    abstract protected function shopService(): ShopService;

    abstract protected function productRetailService(): ProductRetailService;

    /**
     * @return array{found: bool, product_ids: array<int, int>|null}
     */
    protected function resolveShopProductIds(?string $shopSlug): array
    {
        if ($shopSlug === null) {
            return ['found' => true, 'product_ids' => null];
        }

        $shop = $this->shopService()->findBy(params: ['slug' => $shopSlug]);

        if ($shop === null) {
            return ['found' => false, 'product_ids' => null];
        }

        $productIds = $this->productRetailService()->getActiveIds(filters: $this->getShopOwnershipFilters($shop));

        return ['found' => true, 'product_ids' => $productIds === [] ? [self::NO_MATCHING_PRODUCT_ID] : $productIds];
    }

    /**
     * @return array<string, mixed>
     */
    private function getShopOwnershipFilters(Model $shop): array
    {
        if ($shop->author_type === ShopService::AUTHOR_TYPE_ADMIN) {
            return [ProductRetailService::FILTER_ADDED_BY => ProductRetailService::ADDED_BY_IN_HOUSE];
        }

        return [
            ProductRetailService::FILTER_ADDED_BY => ProductRetailService::ADDED_BY_SELLER,
            ProductRetailService::FILTER_SELLER_ID => (int) $shop->seller_id,
        ];
    }
}
