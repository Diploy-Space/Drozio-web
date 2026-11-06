<?php

namespace Tests\Feature\Order;

use App\Enums\BusinessModule;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\Product\ProductStockService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleStockRestoreTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const CURRENT_STOCK = 200;

    private const VARIANT = 'M';

    private const VARIANT_STOCK = 100;

    private const LINE_QUANTITY = 15;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
    }

    public function test_cancel_restores_current_stock_and_variant_qty(): void
    {
        $product = $this->createVariantProduct();

        $this->applyStatusChange(product: $product, variant: self::VARIANT, isStockDecreased: 1, orderStatus: 'canceled');

        $this->assertStock(product: $product, currentStock: self::CURRENT_STOCK + self::LINE_QUANTITY, variantStock: self::VARIANT_STOCK + self::LINE_QUANTITY);
    }

    #[DataProvider('restoringStatuses')]
    public function test_returned_and_failed_restore_like_canceled(string $orderStatus): void
    {
        $product = $this->createVariantProduct();

        $quantityChange = $this->applyStatusChange(product: $product, variant: self::VARIANT, isStockDecreased: 1, orderStatus: $orderStatus);

        $this->assertSame(self::LINE_QUANTITY, $quantityChange);
        $this->assertStock(product: $product, currentStock: self::CURRENT_STOCK + self::LINE_QUANTITY, variantStock: self::VARIANT_STOCK + self::LINE_QUANTITY);
    }

    public static function restoringStatuses(): array
    {
        return self::wholesaleDataSets(static function (): array {
            return array_combine(
                ProductWholesaleService::STOCK_RESTORING_ORDER_STATUSES,
                array_map(static fn (string $status): array => [$status], ProductWholesaleService::STOCK_RESTORING_ORDER_STATUSES),
            );
        });
    }

    public function test_restore_is_skipped_when_stock_was_never_decreased(): void
    {
        $product = $this->createVariantProduct();

        $quantityChange = $this->applyStatusChange(product: $product, variant: self::VARIANT, isStockDecreased: 0, orderStatus: 'canceled');

        $this->assertNull($quantityChange);
        $this->assertStock(product: $product, currentStock: self::CURRENT_STOCK, variantStock: self::VARIANT_STOCK);
    }

    public function test_a_non_restoring_status_leaves_decreased_stock_alone(): void
    {
        $product = $this->createVariantProduct();

        $this->assertNull($this->productService()->getStockChangeForOrderStatus(
            orderDetail: $this->orderDetail(product: $product, variant: self::VARIANT, isStockDecreased: 1),
            orderStatus: 'confirmed',
        ));
    }

    public function test_leaving_a_restoring_status_deducts_again(): void
    {
        $product = $this->createVariantProduct();

        $quantityChange = $this->applyStatusChange(product: $product, variant: self::VARIANT, isStockDecreased: 0, orderStatus: 'pending');

        $this->assertSame(-self::LINE_QUANTITY, $quantityChange);
        $this->assertStock(product: $product, currentStock: self::CURRENT_STOCK - self::LINE_QUANTITY, variantStock: self::VARIANT_STOCK - self::LINE_QUANTITY);
    }

    public function test_deduct_again_fails_when_stock_is_insufficient(): void
    {
        $product = $this->createVariantProduct();
        $oversizedChange = -(self::CURRENT_STOCK + 1);

        $this->assertFalse($this->productService()->adjustCurrentStockForOrderItem(productId: $product->id, quantityChange: $oversizedChange));
        $this->assertFalse($this->productStockService()->adjustForOrderItem(productId: $product->id, variant: self::VARIANT, quantityChange: -(self::VARIANT_STOCK + 1)));

        $this->assertStock(product: $product, currentStock: self::CURRENT_STOCK, variantStock: self::VARIANT_STOCK);
    }

    public function test_variantless_line_touches_only_current_stock(): void
    {
        $product = $this->createVariantProduct();
        $stocksBefore = $this->variantStocks(product: $product);

        $this->applyStatusChange(product: $product, variant: '', isStockDecreased: 1, orderStatus: 'canceled');

        $this->assertSame(self::CURRENT_STOCK + self::LINE_QUANTITY, $this->currentStock(product: $product));
        $this->assertSame($stocksBefore, $this->variantStocks(product: $product));
    }

    public function test_restore_skips_a_deleted_variant_and_still_restores_current_stock(): void
    {
        $product = $this->createVariantProduct();
        ProductStock::query()->where(['product_id' => $product->id, 'variant' => self::VARIANT])->delete();
        $stocksBefore = $this->variantStocks(product: $product);

        $this->applyStatusChange(product: $product, variant: self::VARIANT, isStockDecreased: 1, orderStatus: 'canceled');

        $this->assertSame(self::CURRENT_STOCK + self::LINE_QUANTITY, $this->currentStock(product: $product));
        $this->assertSame($stocksBefore, $this->variantStocks(product: $product));
    }

    public function test_deduct_again_skips_a_deleted_variant_and_still_deducts_current_stock(): void
    {
        $product = $this->createVariantProduct();
        ProductStock::query()->where(['product_id' => $product->id, 'variant' => self::VARIANT])->delete();
        $stocksBefore = $this->variantStocks(product: $product);

        $this->applyStatusChange(product: $product, variant: self::VARIANT, isStockDecreased: 0, orderStatus: 'pending');

        $this->assertSame(self::CURRENT_STOCK - self::LINE_QUANTITY, $this->currentStock(product: $product));
        $this->assertSame($stocksBefore, $this->variantStocks(product: $product));
    }

    public function test_digital_line_is_never_adjusted(): void
    {
        $product = $this->createVariantProduct();
        Product::query()->whereKey($product->id)->update(['product_type' => ProductWholesaleService::PRODUCT_TYPE_DIGITAL]);

        $orderDetail = $this->orderDetail(product: $product, variant: self::VARIANT, isStockDecreased: 1);

        $this->assertNull($this->productService()->getStockChangeForOrderStatus(orderDetail: $orderDetail, orderStatus: 'canceled'));
    }

    public function test_missing_or_retail_product_is_never_adjusted(): void
    {
        $product = $this->createVariantProduct();
        $orderDetail = $this->orderDetail(product: $product, variant: self::VARIANT, isStockDecreased: 1);

        Product::query()->whereKey($product->id)->update(['business_module' => BusinessModule::RETAIL]);
        $this->assertNull($this->productService()->getStockChangeForOrderStatus(orderDetail: $orderDetail, orderStatus: 'canceled'));

        $orderDetail['product_id'] = 0;
        $this->assertNull($this->productService()->getStockChangeForOrderStatus(orderDetail: $orderDetail, orderStatus: 'canceled'));
    }

    public function test_order_detail_model_is_accepted(): void
    {
        $product = $this->createVariantProduct();
        $orderDetail = (object) $this->orderDetail(product: $product, variant: self::VARIANT, isStockDecreased: 1);

        $this->assertSame(self::LINE_QUANTITY, $this->productService()->getStockChangeForOrderStatus(orderDetail: $orderDetail, orderStatus: 'returned'));
    }

    public function test_placement_deduction_is_unchanged(): void
    {
        $product = $this->createVariantProduct();

        $this->assertTrue($this->productService()->deductCurrentStockForOrderItem(productId: $product->id, quantity: self::LINE_QUANTITY));
        $this->assertTrue($this->productStockService()->deductForOrderItem(productId: $product->id, variant: self::VARIANT, quantity: self::LINE_QUANTITY));
        $this->assertStock(product: $product, currentStock: self::CURRENT_STOCK - self::LINE_QUANTITY, variantStock: self::VARIANT_STOCK - self::LINE_QUANTITY);

        $this->assertFalse($this->productService()->deductCurrentStockForOrderItem(productId: $product->id, quantity: self::CURRENT_STOCK));
        $this->assertFalse($this->productStockService()->deductForOrderItem(productId: $product->id, variant: self::VARIANT, quantity: self::VARIANT_STOCK));
        $this->assertTrue($this->productStockService()->deductForOrderItem(productId: $product->id, variant: '', quantity: self::LINE_QUANTITY));
        $this->assertStock(product: $product, currentStock: self::CURRENT_STOCK - self::LINE_QUANTITY, variantStock: self::VARIANT_STOCK - self::LINE_QUANTITY);
    }

    private function applyStatusChange(Model $product, string $variant, int $isStockDecreased, string $orderStatus): ?int
    {
        $quantityChange = $this->productService()->getStockChangeForOrderStatus(
            orderDetail: $this->orderDetail(product: $product, variant: $variant, isStockDecreased: $isStockDecreased),
            orderStatus: $orderStatus,
        );

        if ($quantityChange !== null) {
            $this->assertTrue($this->productService()->adjustCurrentStockForOrderItem(productId: $product->id, quantityChange: $quantityChange));
            $this->assertTrue($this->productStockService()->adjustForOrderItem(productId: $product->id, variant: $variant, quantityChange: $quantityChange));
        }

        return $quantityChange;
    }

    /** @return array<string, mixed> */
    private function orderDetail(Model $product, string $variant, int $isStockDecreased): array
    {
        return [
            'product_id' => $product->id,
            'qty' => self::LINE_QUANTITY,
            'variant' => $variant,
            'is_stock_decreased' => $isStockDecreased,
        ];
    }

    private function assertStock(Model $product, int $currentStock, int $variantStock): void
    {
        $this->assertSame($currentStock, $this->currentStock(product: $product));
        $this->assertSame($variantStock, (int) ProductStock::query()->where(['product_id' => $product->id, 'variant' => self::VARIANT])->value('qty'));
    }

    private function currentStock(Model $product): int
    {
        return (int) Product::query()->whereKey($product->id)->value('current_stock');
    }

    /** @return array<string, int> */
    private function variantStocks(Model $product): array
    {
        return ProductStock::query()->where('product_id', $product->id)->pluck('qty', 'variant')->map(static fn (mixed $qty): int => (int) $qty)->all();
    }

    private function createVariantProduct(): Model
    {
        $attributeId = $this->attributeId();

        $product = $this->productService()->create(
            addedBy: ProductWholesaleService::ADDED_BY_ADMIN,
            userId: null,
            shopId: 0,
            data: [
                'lang' => ['en'],
                'name' => ['Wholesale stock restore fixture'],
                'description' => ['Wholesale stock restore fixture'],
                'code' => 'WSSTK'.random_int(10000, 99999),
                'category_id' => $this->categoryId(),
                'unit' => 'pc',
                'shipping_cost' => 0,
                'discount' => 0,
                'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
                'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
                'minimum_order_qty' => 1,
                'current_stock' => self::CURRENT_STOCK,
                'choice_attributes' => [$attributeId],
                'choice_no' => [$attributeId],
                'choice' => ['Size'],
                'choice_options_'.$attributeId => ['S,M'],
                'qty_S' => self::VARIANT_STOCK,
                'qty_M' => self::VARIANT_STOCK,
                'quantity_based_price' => 1,
                'tier_min_qty' => [1],
                'tier_max_qty' => [''],
                'tier_unit_price' => [50],
            ],
        );

        Product::query()->whereKey($product->id)->update([
            'current_stock' => self::CURRENT_STOCK,
            'product_type' => ProductWholesaleService::PRODUCT_TYPE_PHYSICAL,
        ]);

        $this->assertSame(self::VARIANT_STOCK, (int) ProductStock::query()->where(['product_id' => $product->id, 'variant' => self::VARIANT])->value('qty'));

        return $product;
    }

    private function attributeId(): int
    {
        $ids = app(AttributeService::class)->getIds();

        if ($ids === []) {
            $this->markTestSkipped('Test database has no wholesale attributes.');
        }

        return (int) $ids[0];
    }

    private function categoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }

    private function productService(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function productStockService(): ProductStockService
    {
        return app(ProductStockService::class);
    }
}
