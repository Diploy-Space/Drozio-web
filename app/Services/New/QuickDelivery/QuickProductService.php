<?php

namespace App\Services\New\QuickDelivery;

use App\Enums\BusinessModule;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

/** Reuses existing shops/products/categories; no Quick-specific tables. */
class QuickProductService
{
    /** @param array<int,int> $shopIds @return array<int,int> */
    public function getAvailableProductIds(array $shopIds): array
    {
        if ($shopIds === []) {
            return [];
        }

        // Only IDs verified by the serviceability service are accepted.
        $shopIds = array_values(array_intersect(
            array_map('intval', $shopIds),
            (array) config('quick_delivery.enabled_shop_ids', []),
        ));

        if ($shopIds === [] || ! Schema::hasColumn('products', 'current_stock')) {
            return [];
        }

        $max = (int) config('quick_delivery.max_product_ids', 10000);
        $ids = [];

        $shops = Shop::query()
            ->whereIn('id', $shopIds)
            ->where('author_type', 'vendor')
            ->whereHas('seller', fn ($query) => $query->where('status', 'approved'))
            ->get();

        foreach ($shops as $shop) {
            foreach ($shop->products()
                ->active()
                ->businessModule(BusinessModule::RETAIL)
                ->where('products.current_stock', '>', 0)
                ->limit(max(0, $max - count($ids)))
                ->pluck('products.id') as $id) {
                $ids[(int) $id] = (int) $id;
            }

            if (count($ids) >= $max) {
                break;
            }
        }

        return array_values($ids);
    }

    /** @param array<int,int> $productIds */
    public function getAvailableCategories(array $productIds): Collection
    {
        if ($productIds === []) {
            return new Collection();
        }

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->whereNotNull('category_id')
            ->get();

        $byCategory = $products->groupBy('category_id');
        $categories = Category::query()->whereIn('id', $byCategory->keys()->all())->get();
        foreach ($categories as $category) {
            $category->setRelation('product', new Collection($byCategory->get($category->id, collect())->all()));
        }

        return $categories;
    }
}
