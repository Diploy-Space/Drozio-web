<?php

namespace Tests\Feature\Api\V6;

use App\Models\Product;
use App\Models\Seller;
use App\Services\New\ShopService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class SellerProductTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_a_seller_creates_a_retail_product_using_only_the_authenticated_identity(): void
    {
        $seller = $this->approvedSeller();

        $payload = $this->payload('APIADD1') + [
            'user_id' => 999999,
            'added_by' => 'admin',
            'shop_id' => 999999,
        ];

        $response = $this->postJson('api/v6/seller/products', $payload, $this->sellerHeaders($seller));

        $response->assertSuccessful();

        $stored = Product::query()->where('code', 'APIADD1')->first();

        $this->assertNotNull($stored, 'the product was not written to the database');
        $this->assertSame('seller', $stored->added_by);
        $this->assertSame($seller->id, (int) $stored->user_id);
        $this->assertSame($this->expectedShopId($seller), (int) $stored->shop_id);
        $this->assertNotSame(999999, (int) $stored->user_id);
        $this->assertNotSame(999999, (int) $stored->shop_id);
    }

    public function test_a_seller_updates_their_own_retail_product(): void
    {
        $seller = $this->approvedSeller();

        $this->postJson('api/v6/seller/products', $this->payload('APIEDIT1'), $this->sellerHeaders($seller))
            ->assertSuccessful();

        $product = Product::query()->where('code', 'APIEDIT1')->firstOrFail();

        $payload = $this->payload('APIEDIT1');
        $payload['name'] = ['Updated api product'];
        $payload['unit_price'] = 480;
        $payload['existing_thumbnail'] = $product->thumbnail;

        $this->putJson('api/v6/seller/products/'.$product->id, $payload, $this->sellerHeaders($seller))
            ->assertSuccessful();

        $product->refresh();

        $this->assertSame(480.0, (float) $product->unit_price);
        $this->assertSame('Updated api product', $product->getRawOriginal('name'));
        $this->assertSame('seller', $product->added_by);
        $this->assertSame($seller->id, (int) $product->user_id);
    }

    public function test_a_seller_cannot_update_a_product_they_do_not_own(): void
    {
        $seller = $this->approvedSeller();

        $foreign = $this->requireFixture(
            Product::query()->where('added_by', 'admin')->first(),
            'an admin owned product',
        );

        $originalName = $foreign->getRawOriginal('name');
        $originalPrice = (float) $foreign->unit_price;

        $payload = $this->payload('SHOULDNOTAPPLY');
        $payload['name'] = ['Hijacked name'];
        $payload['unit_price'] = $originalPrice + 12345;
        unset($payload['category_id']);

        $response = $this->putJson(
            'api/v6/seller/products/'.$foreign->id,
            $payload,
            $this->sellerHeaders($seller),
        );

        $this->assertSame(
            404,
            $response->status(),
            'an invalid payload against a foreign product must 404 before any validation error can leak',
        );

        $foreign->refresh();

        $this->assertSame($originalName, $foreign->getRawOriginal('name'));
        $this->assertSame($originalPrice, (float) $foreign->unit_price);
    }

    public function test_updating_a_non_existent_product_returns_404_not_500(): void
    {
        $seller = $this->approvedSeller();

        $response = $this->putJson(
            'api/v6/seller/products/999999999',
            $this->payload('DOESNOTEXIST'),
            $this->sellerHeaders($seller),
        );

        $response->assertStatus(404);
    }

    public function test_a_seller_cannot_delete_an_image_on_a_product_they_do_not_own(): void
    {
        $seller = $this->approvedSeller();

        $foreign = $this->requireFixture(
            Product::query()->where('added_by', 'admin')->first(),
            'an admin owned product',
        );

        $originalImages = $foreign->getRawOriginal('images');
        $originalColorImage = $foreign->retailDetail?->getRawOriginal('color_image');

        $response = $this->deleteJson(
            'api/v6/seller/products/'.$foreign->id.'/images',
            ['name' => 'whatever.webp'],
            $this->sellerHeaders($seller),
        );

        $response->assertStatus(404);

        $foreign->refresh();

        $this->assertSame($originalImages, $foreign->getRawOriginal('images'));
        $this->assertSame($originalColorImage, $foreign->retailDetail?->getRawOriginal('color_image'));
    }

    public function test_deleting_an_image_on_a_non_existent_product_returns_404_not_500(): void
    {
        $seller = $this->approvedSeller();

        $response = $this->deleteJson(
            'api/v6/seller/products/999999999/images',
            ['name' => 'whatever.webp'],
            $this->sellerHeaders($seller),
        );

        $response->assertStatus(404);
    }

    public function test_deleting_an_image_not_in_the_products_gallery_deletes_nothing(): void
    {
        $seller = $this->approvedSeller();

        $this->postJson(
            'api/v6/seller/products',
            array_merge($this->payload('APIGALLERY1'), ['existing_images' => ['gallery-a.webp', 'gallery-b.webp']]),
            $this->sellerHeaders($seller),
        )->assertSuccessful();

        $product = Product::query()->where('code', 'APIGALLERY1')->firstOrFail();

        $this->assertSame(
            ['gallery-a.webp', 'gallery-b.webp'],
            array_column(json_decode($product->getRawOriginal('images'), true), 'image_name'),
        );

        $originalImages = $product->getRawOriginal('images');
        $originalColorImage = $product->retailDetail?->getRawOriginal('color_image');

        Storage::disk('public')->put('product/thumbnail/other.webp', 'not-this-file');

        $response = $this->deleteJson(
            'api/v6/seller/products/'.$product->id.'/images',
            ['name' => 'thumbnail/other.webp'],
            $this->sellerHeaders($seller),
        );

        $product->refresh();

        $this->assertSame(
            $originalImages,
            $product->getRawOriginal('images'),
            'the escaping name changed the stored gallery',
        );
        $this->assertSame(
            $originalColorImage,
            $product->retailDetail?->getRawOriginal('color_image'),
            'the escaping name changed the stored color images',
        );

        Storage::disk('public')->assertExists('product/thumbnail/other.webp');

        $response->assertStatus(404);
    }

    public function test_deleting_an_image_that_is_actually_in_the_gallery_succeeds(): void
    {
        $seller = $this->approvedSeller();

        $this->postJson(
            'api/v6/seller/products',
            array_merge($this->payload('APIGALLERY2'), ['existing_images' => ['gallery-c.webp', 'gallery-d.webp']]),
            $this->sellerHeaders($seller),
        )->assertSuccessful();

        $product = Product::query()->where('code', 'APIGALLERY2')->firstOrFail();

        $response = $this->deleteJson(
            'api/v6/seller/products/'.$product->id.'/images',
            ['name' => 'gallery-c.webp'],
            $this->sellerHeaders($seller),
        );

        $response->assertSuccessful();

        $product->refresh();

        $this->assertSame(
            ['gallery-d.webp'],
            array_column(json_decode($product->getRawOriginal('images'), true), 'image_name'),
        );
    }

    public function test_a_seller_update_removes_only_the_images_marked_for_removal(): void
    {
        $seller = $this->approvedSeller();

        $this->postJson(
            'api/v6/seller/products',
            array_merge($this->payload('APIREMOVE1'), ['existing_images' => ['gallery-e.webp', 'gallery-f.webp']]),
            $this->sellerHeaders($seller),
        )->assertSuccessful();

        $product = Product::query()->where('code', 'APIREMOVE1')->firstOrFail();

        Storage::disk('public')->put('product/gallery-e.webp', 'gallery-e');
        Storage::disk('public')->put('product/gallery-f.webp', 'gallery-f');
        Storage::disk('public')->put('product/thumbnail/other.webp', 'not-this-file');

        $payload = $this->payload('APIREMOVE1');
        $payload['existing_thumbnail'] = $product->thumbnail;
        $payload['removed_images'] = ['gallery-e.webp', 'thumbnail/other.webp'];

        $this->putJson('api/v6/seller/products/'.$product->id, $payload, $this->sellerHeaders($seller))
            ->assertSuccessful()
            ->assertJsonPath('content.data.image_names.0.image_name', 'gallery-f.webp');

        $product->refresh();

        $this->assertSame(
            ['gallery-f.webp'],
            array_column(json_decode($product->getRawOriginal('images'), true), 'image_name'),
        );

        Storage::disk('public')->assertMissing('product/gallery-e.webp');
        Storage::disk('public')->assertExists('product/gallery-f.webp');
        Storage::disk('public')->assertExists('product/thumbnail/other.webp');
    }

    public function test_a_seller_update_cannot_remove_every_image(): void
    {
        $seller = $this->approvedSeller();

        $this->postJson(
            'api/v6/seller/products',
            array_merge($this->payload('APIREMOVE2'), ['existing_images' => ['gallery-g.webp', 'gallery-h.webp']]),
            $this->sellerHeaders($seller),
        )->assertSuccessful();

        $product = Product::query()->where('code', 'APIREMOVE2')->firstOrFail();
        $originalImages = $product->getRawOriginal('images');

        Storage::disk('public')->put('product/gallery-g.webp', 'gallery-g');

        $payload = $this->payload('APIREMOVE2');
        $payload['existing_thumbnail'] = $product->thumbnail;
        $payload['removed_images'] = ['gallery-g.webp', 'gallery-h.webp'];

        $this->putJson('api/v6/seller/products/'.$product->id, $payload, $this->sellerHeaders($seller))
            ->assertStatus(422);

        $product->refresh();

        $this->assertSame($originalImages, $product->getRawOriginal('images'));
        Storage::disk('public')->assertExists('product/gallery-g.webp');
    }

    public function test_variation_combinations_are_generated_from_the_posted_options(): void
    {
        $seller = $this->approvedSeller();

        $response = $this->postJson('api/v6/seller/products/variations', [
            'lang' => ['en'],
            'name' => ['Combo product'],
            'unit_price' => 100,
            'discount' => 0,
            'discount_type' => 'flat',
            'choice_no' => ['1'],
            'choice_options_1' => ['S,M'],
        ], $this->sellerHeaders($seller));

        $response->assertSuccessful();

        $types = array_column($response->json('content.data') ?? [], 'type');

        $this->assertEqualsCanonicalizing(['S', 'M'], $types);

        foreach ($response->json('content.data') as $combination) {
            $this->assertSame(100.0, (float) $combination['price']);
            $this->assertNotEmpty($combination['sku']);
        }
    }

    public function test_a_seller_creates_a_product_when_the_payload_omits_every_optional_field(): void
    {
        $seller = $this->approvedSeller();

        $payload = [
            'lang' => ['en'],
            'name' => ['Api minimal product'],
            'description' => ['<p>Api minimal description</p>'],
            'code' => 'APIMIN1',
            'category_id' => $this->categoryId(),
            'unit' => 'pc',
            'product_type' => 'physical',
            'unit_price' => 320,
            'discount' => 0,
            'minimum_order_qty' => 1,
            'shipping_cost' => 0,
            'existing_thumbnail' => 'def.png',
            'existing_images' => ['def.png'],
            'tax_ids' => $this->taxIds(),
        ];

        $this->postJson('api/v6/seller/products', $payload, $this->sellerHeaders($seller))->assertSuccessful();

        $stored = Product::query()->where('code', 'APIMIN1')->first();

        $this->assertNotNull($stored, 'the product was not stored from a payload without optional fields');
        $this->assertNull($stored->sub_category_id);
        $this->assertNull($stored->sub_sub_category_id);
        $this->assertNull($stored->brand_id);
        $this->assertSame('flat', $stored->discount_type);
    }

    public function test_the_list_applies_json_encoded_filters_the_same_as_bracketed_ones(): void
    {
        $seller = $this->approvedSeller();
        $brandId = $this->requireFixture(
            DB::table('products')->where('added_by', 'seller')->where('user_id', $seller->id)
                ->where('business_module', 'retail')->whereNotNull('brand_id')->value('brand_id'),
            'a retail product with a brand for the seller',
        );

        $expectedIds = DB::table('products')->where('added_by', 'seller')->where('user_id', $seller->id)
            ->where('business_module', 'retail')->where('brand_id', $brandId)->where('status', 1)
            ->orderByDesc('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();

        foreach ([
            "product_status[]=1&filter_brand_ids[]={$brandId}",
            "product_status=1&filter_brand_ids={$brandId}",
            'product_status='.urlencode('[1]').'&filter_brand_ids='.urlencode("[{$brandId}]"),
        ] as $query) {
            $ids = $this->getJson('api/v6/seller/products?limit=500&'.$query, $this->sellerHeaders($seller))
                ->assertOk()
                ->json('content.data.*.id');

            $this->assertSame($expectedIds, $ids, $query);
        }
    }

    public function test_the_list_falls_back_to_the_default_page_size_for_an_unusable_limit(): void
    {
        $seller = $this->approvedSeller();

        foreach (['abc', '0', '-5', 'all'] as $limit) {
            $this->getJson('api/v6/seller/products?limit='.$limit, $this->sellerHeaders($seller))
                ->assertOk()
                ->assertJsonPath('content.pagination.per_page', (int) DEFAULT_DATA_LIMIT);
        }
    }

    public function test_the_list_rejects_an_unknown_product_type(): void
    {
        $this->getJson('api/v6/seller/products?filter_product_types[]=service', $this->sellerHeaders($this->approvedSeller()))
            ->assertUnprocessable();
    }

    private function approvedSeller(): Seller
    {
        $seller = $this->requireFixture(
            Seller::query()->where('status', 'approved')->first(),
            'an approved seller',
        );

        $seller->forceFill(['auth_token' => Str::random(50)])->save();

        return $seller->fresh();
    }

    private function expectedShopId(Seller $seller): int
    {
        return app(ShopService::class)->findBy(params: ['seller_id' => $seller->id])?->id ?? 0;
    }

    private function payload(string $code): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Api product '.$code],
            'description' => ['<p>Api description '.$code.'</p>'],
            'code' => $code,
            'category_id' => $this->categoryId(),
            'sub_category_id' => null,
            'sub_sub_category_id' => null,
            'brand_id' => null,
            'unit' => 'pc',
            'product_type' => 'physical',
            'digital_product_type' => null,
            'unit_price' => 320,
            'discount' => 0,
            'discount_type' => 'flat',
            'current_stock' => 12,
            'minimum_order_qty' => 1,
            'shipping_cost' => 0,
            'video_url' => null,
            'meta_title' => 'Meta title '.$code,
            'meta_description' => 'Meta description '.$code,
            'existing_thumbnail' => 'def.png',
            'existing_images' => ['def.png'],
            'choice_attributes' => [],
            'tags' => '',
            'tax_ids' => $this->taxIds(),
        ];
    }

    private function taxIds(): array
    {
        return DB::table('taxes')->where('is_active', 1)->where('is_default', 1)->limit(1)->pluck('id')->all();
    }

    private function categoryId(): int
    {
        return (int) DB::table('categories')->where('position', 0)->value('id');
    }
}
