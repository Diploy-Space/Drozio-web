<?php

namespace Tests\Feature\Migration;

use App\Models\Product;
use App\Models\ProductRetail;
use App\Services\New\ProductRetailService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Covers the product side of the 16.6 split once the retail columns have moved:
 * reads resolve through the relation, writes go through it, and a delete takes the
 * retail row with it — nothing cascades on its own, this schema has no foreign keys.
 */
class ProductRetailRelationTest extends TestCase
{
    private const PRODUCT_ID = 900500;

    protected function setUp(): void
    {
        parent::setUp();

        if (str_ends_with(DB::connection()->getDatabaseName(), '_snapshot_test')) {
            $this->markTestSkipped('Refusing to write fixtures into the API snapshot clone.');
        }

        $this->deleteFixture();

        DB::table('products')->insert([
            'id' => self::PRODUCT_ID,
            'name' => 'Relation fixture',
            'slug' => 'relation-fixture',
            'code' => 'RELFIX1',
            'added_by' => 'admin',
            'user_id' => 1,
            'product_type' => 'physical',
            'unit_price' => 150,
            'current_stock' => 5,
            'status' => 1,
            'request_status' => 1,
            'images' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        $this->deleteFixture();

        parent::tearDown();
    }

    private function deleteFixture(): void
    {
        ProductRetail::where('product_id', self::PRODUCT_ID)->delete();
        DB::table('products')->where('id', self::PRODUCT_ID)->delete();
    }

    private function seedRetail(array $overrides = []): ProductRetail
    {
        return ProductRetail::create($overrides + [
            'product_id' => self::PRODUCT_ID,
            'digital_product_type' => null,
            'variation' => json_encode([['type' => 'S', 'price' => 10, 'sku' => 'S-1', 'qty' => 3]]),
            'color_image' => json_encode([['color' => '#000000', 'image_name' => 'a.png']]),
            'featured' => 1,
            'purchase_price' => 12.5,
            'digital_product_file_types' => ['pdf'],
        ]);
    }

    #[Test]
    public function moved_attributes_read_through_the_relation(): void
    {
        $this->seedRetail();

        $product = Product::find(self::PRODUCT_ID);

        $this->assertSame('[{"type":"S","price":10,"sku":"S-1","qty":3}]', $product->variation);
        $this->assertSame('[{"color":"#000000","image_name":"a.png"}]', $product->color_image);
        $this->assertSame(1, $product->featured);
        $this->assertSame(12.5, $product->purchase_price);
        $this->assertSame(['pdf'], $product->digital_product_file_types);
    }

    #[Test]
    public function a_product_with_no_retail_row_still_reads_without_failing(): void
    {
        $product = Product::find(self::PRODUCT_ID);

        $this->assertSame('', $product->color_image);
        $this->assertSame(0.0, $product->purchase_price);
        $this->assertNull($product->variation);
        $this->assertNull($product->featured);
    }

    #[Test]
    public function serialization_keeps_the_moved_keys_and_hides_the_relation(): void
    {
        $this->seedRetail();

        $serialized = Product::find(self::PRODUCT_ID)->toArray();

        foreach ([
            'digital_product_type', 'digital_file_ready', 'digital_file_ready_storage_type',
            'digital_product_file_types', 'digital_product_extensions', 'preview_file',
            'preview_file_storage_type', 'featured', 'flash_deal', 'color_image',
            'variation', 'purchase_price',
        ] as $attribute) {
            $this->assertArrayHasKey($attribute, $serialized, "{$attribute} vanished from the serialized product.");
        }

        $this->assertArrayNotHasKey('retail_detail', $serialized);
        $this->assertArrayNotHasKey('retailDetail', $serialized);
    }

    #[Test]
    public function an_update_on_a_product_with_no_retail_row_creates_one(): void
    {
        $product = Product::find(self::PRODUCT_ID);

        $this->assertNull($product->retailDetail);

        $product->retailDetail()->updateOrCreate(
            ['product_id' => self::PRODUCT_ID],
            ['variation' => json_encode([]), 'featured' => 0],
        );

        $this->assertSame(json_encode([]), Product::find(self::PRODUCT_ID)->variation);
    }

    #[Test]
    public function an_explicitly_assigned_value_wins_over_the_relation(): void
    {
        $this->seedRetail();

        $product = Product::find(self::PRODUCT_ID);
        $product['variation'] = [['type' => 'S']];

        $this->assertSame([['type' => 'S']], $product->variation);
        $this->assertSame([['type' => 'S']], $product->toArray()['variation']);
    }

    #[Test]
    public function deleting_a_product_removes_its_retail_row(): void
    {
        $this->seedRetail();

        app(ProductRetailService::class)->delete(id: self::PRODUCT_ID);

        $this->assertSame(0, ProductRetail::where('product_id', self::PRODUCT_ID)->count());
        $this->assertNull(Product::find(self::PRODUCT_ID));
    }

    #[Test]
    public function a_product_list_resolves_the_relation_without_an_n_plus_1(): void
    {
        $this->seedRetail();

        DB::enableQueryLog();
        Product::whereIn('id', [self::PRODUCT_ID])->get()->each(fn (Product $product) => $product->variation);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $retailQueries = collect($queries)->filter(
            fn (array $query): bool => str_contains($query['query'], 'products_retail')
        );

        $this->assertCount(1, $retailQueries, 'The retail extension should be fetched once for the whole list.');
    }
}
