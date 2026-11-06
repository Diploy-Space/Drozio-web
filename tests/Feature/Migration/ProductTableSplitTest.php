<?php

namespace Tests\Feature\Migration;

use App\Enums\BusinessModule;
use App\Traits\UpdateClass;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Throwable;

class ProductTableSplitTest extends TestCase
{
    public const MIGRATIONS = [
        'database/migrations/2026_08_08_000000_update_products_table.php',
        'database/migrations/2026_08_30_100020_create_products_retail_table.php',
        'Modules/Wholesale/database/migrations/2026_08_30_110000_create_products_wholesale_table.php',
        'Modules/Wholesale/database/migrations/2026_08_30_110010_create_products_wholesale_attribute_images_table.php',
        'Modules/Wholesale/database/migrations/2026_08_30_110020_create_product_faqs_table.php',
    ];

    private const SCOPE_INDEXES = ['products_scope_owner_index', 'products_scope_status_index'];

    public const CUTOVER_MIGRATION = 'database/migrations/2026_08_30_100020_create_products_retail_table.php';

    public const FIXTURE_IDS = [900001, 900002, 900003];

    public const MOVED_COLUMNS = [
        'digital_product_type',
        'digital_file_ready',
        'digital_file_ready_storage_type',
        'digital_product_file_types',
        'digital_product_extensions',
        'preview_file',
        'preview_file_storage_type',
        'featured',
        'flash_deal',
        'color_image',
        'variation',
        'purchase_price',
    ];

    public const LEGACY_COLUMNS = [
        'min_qty',
        'refundable',
        'variant_product',
        'published',
        'free_shipping',
        'attachment',
    ];

    private object $updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipWhenNotOnTheContractDatabase();

        $this->updater = new class
        {
            use UpdateClass;
        };

        $this->resetMigratedSchema();
        $this->seedFixtureProducts();
        $this->runSplitMigrations();
    }

    protected function tearDown(): void
    {
        
        if (Schema::hasTable('products_retail')) {
            DB::table('products_retail')->whereIn('product_id', self::FIXTURE_IDS)->delete();
        }
        DB::table('products')->whereIn('id', self::FIXTURE_IDS)->delete();
        $this->restoreSplitColumns();

        parent::tearDown();
    }

    protected function skipWhenNotOnTheContractDatabase(): void
    {
        if (str_ends_with(DB::connection()->getDatabaseName(), '_snapshot_test')) {
            $this->markTestSkipped('Refusing to alter the API snapshot clone.');
        }
    }

    protected function resetMigratedSchema(): void
    {
        $this->restoreMovedColumns();

        foreach (['product_faqs', 'products_wholesale_attribute_images', 'products_wholesale', 'products_retail'] as $table) {
            Schema::dropIfExists($table);
        }

        foreach (self::SCOPE_INDEXES as $index) {
            if ($this->hasIndex('products', $index)) {
                Schema::table('products', function ($table) use ($index) {
                    $table->dropIndex($index);
                });
            }
        }

        foreach (['business_module', 'video_file', 'video_file_storage_type'] as $column) {
            if (Schema::hasColumn('products', $column)) {
                Schema::table('products', function ($table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        $this->restoreSplitColumns();

        DB::table('migrations')
            ->whereIn('migration', array_map(
                fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
                self::MIGRATIONS,
            ))
            ->delete();
    }

    protected function restoreMovedColumns(): void
    {
        if (Schema::hasColumn('products', 'variation')) {
            return;
        }

        
        $this->rollbackCutover();
    }

    protected function restoreSplitColumns(): void
    {
        if (Schema::hasColumn('products', 'min_qty')) {
            return;
        }

        Schema::table('products', function ($table) {
            $table->integer('min_qty')->default(1);
            $table->boolean('refundable')->default(1);
            $table->boolean('variant_product')->default(0);
            $table->boolean('published')->default(0);
            $table->boolean('free_shipping')->default(0);
            $table->string('attachment', 191)->nullable();
        });
    }

    protected function seedFixtureProducts(): void
    {
        DB::table('products')->whereIn('id', self::FIXTURE_IDS)->delete();

        DB::table('products')->insert([
            [
                'id' => 900001,
                'name' => 'Split fixture with values',
                'slug' => 'split-fixture-with-values',
                'digital_product_type' => 'ready_product',
                'digital_file_ready' => 'ready.zip',
                'digital_file_ready_storage_type' => 'public',
                'digital_product_file_types' => '["pdf","zip"]',
                'digital_product_extensions' => '["pdf"]',
                'preview_file' => 'preview.pdf',
                'preview_file_storage_type' => 'public',
                'featured' => '1',
                'flash_deal' => '1',
                'color_image' => '[{"color":"#000000","image_name":"a.png"}]',
                'variation' => '[{"type":"S","price":10,"sku":"S-1","qty":5}]',
                'unit_price' => 199.5,
                'purchase_price' => 120.25,
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => '2026-01-02 00:00:00',
            ],
            [
                'id' => 900002,
                'name' => 'Split fixture with nulls',
                'slug' => 'split-fixture-with-nulls',
                'digital_product_type' => null,
                'digital_file_ready' => null,
                'digital_file_ready_storage_type' => null,
                'digital_product_file_types' => null,
                'digital_product_extensions' => null,
                'preview_file' => null,
                'preview_file_storage_type' => null,
                'featured' => null,
                'flash_deal' => null,
                'color_image' => '',
                'variation' => null,
                'unit_price' => 0,
                'purchase_price' => 0,
                'created_at' => '2026-01-03 00:00:00',
                'updated_at' => '2026-01-04 00:00:00',
            ],
            [
                'id' => 900003,
                'name' => "Split fixture with quotes ' and \\ backslash",
                'slug' => 'split-fixture-quotes',
                'digital_product_type' => null,
                'digital_file_ready' => null,
                'digital_file_ready_storage_type' => null,
                'digital_product_file_types' => null,
                'digital_product_extensions' => null,
                'preview_file' => null,
                'preview_file_storage_type' => null,
                'featured' => null,
                'flash_deal' => null,
                'color_image' => '[]',
                'variation' => '[]',
                'unit_price' => '1.00000000000000000001',
                'purchase_price' => '0.50000000000000000000',
                'created_at' => '2026-01-05 00:00:00',
                'updated_at' => '2026-01-06 00:00:00',
            ],
        ]);
    }

    protected function runSplitMigrations(): void
    {
        foreach (self::MIGRATIONS as $path) {
            Artisan::call('migrate', ['--path' => $path, '--force' => true]);
        }
    }

    protected function hasIndex(string $table, string $index): bool
    {
        return DB::selectOne(
            'SELECT COUNT(*) AS total FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index],
        )->total > 0;
    }

    public function test_products_gains_a_business_module_column_defaulted_to_retail(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'business_module'));

        $modules = DB::table('products')->whereIn('id', self::FIXTURE_IDS)->pluck('business_module');

        $this->assertCount(3, $modules);
        foreach ($modules as $module) {
            $this->assertSame(BusinessModule::RETAIL, $module);
        }
    }

    public function test_business_module_scope_index_exists(): void
    {
        $this->assertTrue($this->hasIndex('products', 'products_scope_status_index'));
    }

    public function test_products_gains_the_video_file_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'video_file'));
        $this->assertTrue(Schema::hasColumn('products', 'video_file_storage_type'));

        $storageType = DB::selectOne(
            "SELECT COLUMN_DEFAULT AS value FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = 'products'
               AND column_name = 'video_file_storage_type'"
        );

        $this->assertSame('public', $storageType->value);
    }

    public function test_products_retail_table_has_every_moved_column(): void
    {
        $this->assertTrue(Schema::hasTable('products_retail'));

        foreach (self::MOVED_COLUMNS as $column) {
            $this->assertTrue(
                Schema::hasColumn('products_retail', $column),
                "products_retail is missing the moved column [{$column}]",
            );
        }
    }

    public function test_products_retail_column_types_match_products(): void
    {
        $this->rollbackCutover();

        $types = DB::select(
            "SELECT table_name AS source, column_name AS name, column_type AS type
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name IN ('products', 'products_retail')
               AND column_name IN ('".implode("','", self::MOVED_COLUMNS)."')"
        );

        $byTable = [];
        foreach ($types as $row) {
            $byTable[$row->source][$row->name] = $row->type;
        }

        foreach (self::MOVED_COLUMNS as $column) {
            $this->assertSame(
                $byTable['products'][$column],
                $byTable['products_retail'][$column],
                "products_retail.{$column} type differs from products.{$column}",
            );
        }
    }

    public function test_products_retail_product_id_is_unique(): void
    {
        $unique = DB::selectOne(
            "SELECT COUNT(*) AS total FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = 'products_retail'
               AND column_name = 'product_id' AND non_unique = 0"
        );

        $this->assertSame(1, (int) $unique->total);
    }

    public function test_products_wholesale_table_has_the_pricing_columns(): void
    {
        $this->assertTrue(Schema::hasTable('products_wholesale'));

        foreach ([
            'product_id',
            'reorder_level',
            'variation_based_price',
            'quantity_based_price',
            'variation_type',
            'price_setup',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('products_wholesale', $column),
                "products_wholesale is missing [{$column}]",
            );
        }

        $priceSetup = DB::selectOne(
            "SELECT data_type AS type FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = 'products_wholesale'
               AND column_name = 'price_setup'"
        );

        $this->assertSame('json', $priceSetup->type);
        $this->assertSame(0, DB::table('products_wholesale')->count());
    }

    public function test_wholesale_attribute_images_table_is_scoped_by_product_and_attribute(): void
    {
        $this->assertTrue(Schema::hasTable('products_wholesale_attribute_images'));

        foreach ([
            'product_id',
            'attribute_type',
            'attribute_id',
            'attribute_value',
            'image',
            'storage_type',
            'mark_as_additional_image',
            'position',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('products_wholesale_attribute_images', $column),
                "products_wholesale_attribute_images is missing [{$column}]",
            );
        }

        $this->assertTrue($this->hasIndex('products_wholesale_attribute_images', 'pai_wholesale_scope_index'));
    }

    public function test_product_faqs_table_is_ordered_by_priority_per_product(): void
    {
        $this->assertTrue(Schema::hasTable('product_faqs'));

        foreach (['product_id', 'question', 'answer', 'priority', 'status'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('product_faqs', $column),
                "product_faqs is missing [{$column}]",
            );
        }

        $this->assertTrue($this->hasIndex('product_faqs', 'product_faqs_wholesale_scope_index'));
    }

    public function test_migration_moves_one_retail_row_per_product(): void
    {
        $this->assertSame(
            DB::table('products')->count(),
            DB::table('products_retail')->count(),
        );

        $orphans = DB::table('products_retail as pr')
            ->leftJoin('products as p', 'p.id', '=', 'pr.product_id')
            ->whereNull('p.id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_migration_drops_the_moved_columns_from_products(): void
    {
        foreach (self::MOVED_COLUMNS as $column) {
            $this->assertFalse(
                Schema::hasColumn('products', $column),
                "products.{$column} survived the cutover.",
            );
        }

        foreach (self::MOVED_COLUMNS as $column) {
            $this->assertTrue(
                Schema::hasColumn('products_retail', $column),
                "products_retail.{$column} is missing after the cutover.",
            );
        }
    }

    public function test_migration_keeps_unit_price_and_every_index_on_products(): void
    {
        $this->rollbackCutover();

        $before = $this->indexNames('products');

        $this->runCutover();

        $this->assertTrue(Schema::hasColumn('products', 'unit_price'));
        $this->assertSame($before, $this->indexNames('products'));
    }

    /**
     * @return array<int, string>
     */
    protected function indexNames(string $table): array
    {
        $names = DB::select(
            'SELECT DISTINCT index_name AS name FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? ORDER BY index_name',
            [$table],
        );

        return array_map(fn (object $row): string => $row->name, $names);
    }

    public function test_migration_moves_every_value_including_nulls(): void
    {
        $expected = [
            900001 => [
                'digital_product_type' => 'ready_product',
                'digital_file_ready' => 'ready.zip',
                'digital_product_file_types' => '["pdf","zip"]',
                'preview_file' => 'preview.pdf',
                'featured' => '1',
                'flash_deal' => '1',
                'color_image' => '[{"color":"#000000","image_name":"a.png"}]',
                'variation' => '[{"type":"S","price":10,"sku":"S-1","qty":5}]',
            ],
            900002 => [
                'digital_product_type' => null,
                'digital_file_ready' => null,
                'digital_product_file_types' => null,
                'preview_file' => null,
                'featured' => null,
                'flash_deal' => null,
                'color_image' => '',
                'variation' => null,
            ],
            900003 => [
                'color_image' => '[]',
                'variation' => '[]',
            ],
        ];

        foreach ($expected as $productId => $columns) {
            $row = DB::table('products_retail')->where('product_id', $productId)->first();

            $this->assertNotNull($row, "products_retail row missing for product {$productId}");

            foreach ($columns as $column => $value) {
                $this->assertSame($value, $row->{$column}, "products_retail.{$column} differs for product {$productId}");
            }
        }

        $this->assertSame(120.25, (float) DB::table('products_retail')->where('product_id', 900001)->value('purchase_price'));
        $this->assertSame(0.5, (float) DB::table('products_retail')->where('product_id', 900003)->value('purchase_price'));
    }

    public function test_migration_moves_the_product_timestamps(): void
    {
        $row = DB::table('products_retail')->where('product_id', 900001)->first();

        $this->assertSame('2026-01-01 00:00:00', $row->created_at);
        $this->assertSame('2026-01-02 00:00:00', $row->updated_at);
    }

    public function test_migration_corrects_a_stale_retail_row_before_dropping(): void
    {
        $this->rollbackCutover();

        DB::table('products_retail')->where('product_id', 900001)->update([
            'variation' => '[{"type":"STALE"}]',
            'featured' => '0',
        ]);

        $this->runCutover();

        $row = DB::table('products_retail')->where('product_id', 900001)->first();

        $this->assertSame('[{"type":"S","price":10,"sku":"S-1","qty":5}]', $row->variation);
        $this->assertSame('1', $row->featured);
        $this->assertFalse(Schema::hasColumn('products', 'variation'));
    }

    public function test_migration_creates_a_retail_row_that_went_missing(): void
    {
        $this->rollbackCutover();

        DB::table('products_retail')->where('product_id', 900002)->delete();

        $this->runCutover();

        $this->assertNotNull(DB::table('products_retail')->where('product_id', 900002)->first());
    }

    public function test_migration_refuses_to_drop_over_an_orphaned_retail_row(): void
    {
        $this->rollbackCutover();

        DB::table('products_retail')->insert([
            'product_id' => 999999,
            'color_image' => '[]',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);

        try {
            $this->runCutover();
            $this->fail('The cutover dropped the columns over an orphaned retail row.');
        } catch (Throwable $exception) {
            $this->assertStringContainsString('products_retail', $exception->getMessage());
            $this->assertTrue(
                Schema::hasColumn('products', 'variation'),
                'The columns were dropped even though the copy failed verification.',
            );
        } finally {
            DB::table('products_retail')->where('product_id', 999999)->delete();
        }
    }

    public function test_migration_rollback_restores_the_columns_and_their_values(): void
    {
        $this->rollbackCutover();

        foreach (self::MOVED_COLUMNS as $column) {
            $this->assertTrue(
                Schema::hasColumn('products', $column),
                "products.{$column} was not restored by the rollback.",
            );
        }

        $product = DB::table('products')->where('id', 900001)->first();

        $this->assertSame('[{"type":"S","price":10,"sku":"S-1","qty":5}]', $product->variation);
        $this->assertSame('[{"color":"#000000","image_name":"a.png"}]', $product->color_image);
        $this->assertSame('1', $product->featured);
        $this->assertSame('ready.zip', $product->digital_file_ready);
        $this->assertSame(120.25, (float) $product->purchase_price);
    }

    public function test_migration_is_idempotent(): void
    {
        $before = DB::table('products_retail')->count();

        $this->runCutover();

        $this->assertSame($before, DB::table('products_retail')->count());
        $this->assertFalse(Schema::hasColumn('products', 'variation'));
    }

    protected function cutoverMigration(): object
    {
        return require base_path(self::CUTOVER_MIGRATION);
    }

    protected function runCutover(): void
    {
        $this->cutoverMigration()->up();
    }

    protected function rollbackCutover(): void
    {
        $this->cutoverMigration()->down();
    }

    public function test_legacy_columns_are_dropped(): void
    {
        $this->updater->dropLegacyProductColumns();

        foreach (self::LEGACY_COLUMNS as $column) {
            $this->assertFalse(
                Schema::hasColumn('products', $column),
                "products.{$column} survived the legacy drop.",
            );
        }
    }

    public function test_bulk_import_still_writes_after_the_legacy_drop(): void
    {
        $this->updater->dropLegacyProductColumns();

        $inserted = DB::table('products')->insert([[
            'id' => 900004,
            'name' => 'Bulk import shaped row',
            'slug' => 'bulk-import-shaped-row',
            'code' => 'BULKSPLIT1',
            'unit_price' => 10,
            'created_at' => '2026-01-07 00:00:00',
            'updated_at' => '2026-01-07 00:00:00',
        ]]);

        $this->assertTrue($inserted);

        $retailInserted = DB::table('products_retail')->insert([[
            'product_id' => 900004,
            'color_image' => '[]',
            'variation' => '[]',
            'purchase_price' => 0,
            'created_at' => '2026-01-07 00:00:00',
            'updated_at' => '2026-01-07 00:00:00',
        ]]);

        $this->assertTrue($retailInserted);

        DB::table('products_retail')->where('product_id', 900004)->delete();
        DB::table('products')->where('id', 900004)->delete();
    }

    public function test_products_keeps_the_columns_common_to_both_modules(): void
    {
        $this->updater->dropLegacyProductColumns();

        foreach (['shop_id', 'meta_title', 'meta_description', 'meta_image', 'business_module', 'video_file', 'unit_price'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('products', $column),
                "products.{$column} was dropped — it is common to both modules and stays.",
            );
        }
    }
}
