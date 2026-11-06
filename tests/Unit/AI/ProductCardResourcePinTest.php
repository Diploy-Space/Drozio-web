<?php

namespace Tests\Unit\AI;

use App\Enums\BusinessModule;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\AI\app\Http\Resources\ShoppingAssistant\ProductCardResource;
use Tests\TestCase;

class ProductCardResourcePinTest extends TestCase
{
    use DatabaseTransactions;

    private const PINNED_CARD = [
        'name' => 'Blue Cotton Shirt',
        'slug' => 'blue-cotton-shirt-fixture',
        'unit_price' => 25,
        'discount' => 5,
        'discount_type' => 'percent',
        'discounted_price' => 23.75,
        'unit_price_formatted' => '$25.00',
        'discounted_price_formatted' => '$23.75',
        'avg_rating' => 0,
        'rating_count' => 0,
        'product_type' => 'physical',
        'colors' => [],
        'color_images' => [],
        'choice_options' => [],
        'variation' => [],
        'digital_variations' => [],
        'digital_product_extensions' => [],
        'current_stock' => 40,
        'minimum_order_qty' => 1,
    ];

    public function test_card_matches_the_legacy_format_product_output(): void
    {
        $user = User::create([
            'name' => 'Pin Seller',
            'email' => 'pin-seller@example.test',
            'phone' => '10000000098',
            'password' => bcrypt('password'),
            'user_type' => 'seller',
        ]);

        $seller = Seller::create([
            'id' => $user->id,
            'email' => 'pin-seller@example.test',
            'f_name' => 'Pin',
            'l_name' => 'Seller',
            'phone' => '10000000098',
            'password' => bcrypt('password'),
            'status' => 'approved',
        ]);

        Shop::create([
            'seller_id' => $seller->id,
            'name' => 'Pin Shop',
            'address' => 'Pin Address',
            'contact' => '10000000098',
            'banner' => 'banner.png',
            'image' => 'shop.png',
        ]);

        $category = Category::create([
            'name' => 'Pin Shirts',
            'slug' => 'pin-shirts-retail',
            'parent_id' => 0,
            'position' => 0,
            'status' => 1,
            'business_module' => BusinessModule::RETAIL,
        ]);

        $brand = Brand::create([
            'name' => 'PinBrand',
            'slug' => 'pin-brand-retail',
            'status' => 1,
            'business_module' => BusinessModule::RETAIL,
        ]);

        $product = Product::create([
            'name' => self::PINNED_CARD['name'],
            'slug' => self::PINNED_CARD['slug'],
            'added_by' => 'seller',
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'category_ids' => json_encode([['id' => (string) $category->id, 'position' => 1]]),
            'brand_id' => $brand->id,
            'unit_price' => 25.0,
            'discount' => 5,
            'discount_type' => 'percent',
            'current_stock' => 40,
            'minimum_order_qty' => 1,
            'status' => 1,
            'request_status' => 1,
            'business_module' => BusinessModule::RETAIL,
            'product_type' => 'physical',
            'colors' => json_encode([]),
            'choice_options' => json_encode([]),
            'variation' => json_encode([]),
        ])->load('rating');

        $card = json_decode(json_encode((new ProductCardResource($product))->resolve()), associative: true);

        $this->assertSame($product->id, $card['id']);
        unset($card['id'], $card['thumbnail_full_url']);

        $this->assertSame(self::PINNED_CARD, $card);
    }
}
