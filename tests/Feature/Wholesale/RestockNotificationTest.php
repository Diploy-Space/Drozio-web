<?php

namespace Tests\Feature\Wholesale;

use App\Enums\BusinessModule;
use App\Events\RestockProductNotificationEvent;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\RestockProduct;
use App\Services\New\ColorService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Wholesale\app\Events\ProductStockChangedEvent;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class RestockNotificationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_stock_changed_event_clears_restock_request_and_notifies_customer(): void
    {
        Event::fake([RestockProductNotificationEvent::class]);

        [$product, , $variant] = $this->createColorPricedProduct();

        $restockRequest = RestockProduct::create(['product_id' => $product->id, 'variant' => $variant]);

        $stockBefore = $this->service()->getStockSnapshot(product: $product);
        $this->assertSame(0, $stockBefore['variants'][$variant] ?? null);

        DB::transaction(function () use ($product, $variant, $stockBefore): void {
            ProductStock::query()
                ->where('product_id', $product->id)
                ->where('variant', $variant)
                ->update(['qty' => 5]);

            event(new ProductStockChangedEvent(product: $product, stockBefore: $stockBefore));
        });

        Event::assertDispatched(RestockProductNotificationEvent::class, 1);
        $this->assertNull(RestockProduct::find($restockRequest->id));
    }

    public function test_stock_changed_event_leaves_unrelated_variant_requests_untouched(): void
    {
        Event::fake([RestockProductNotificationEvent::class]);

        [$product, , $variant] = $this->createColorPricedProduct();

        $otherVariant = $variant.'-untouched';
        $restockRequest = RestockProduct::create(['product_id' => $product->id, 'variant' => $otherVariant]);

        $stockBefore = $this->service()->getStockSnapshot(product: $product);

        DB::transaction(function () use ($product, $variant, $stockBefore): void {
            ProductStock::query()
                ->where('product_id', $product->id)
                ->where('variant', $variant)
                ->update(['qty' => 5]);

            event(new ProductStockChangedEvent(product: $product, stockBefore: $stockBefore));
        });

        Event::assertNotDispatched(RestockProductNotificationEvent::class);
        $this->assertNotNull(RestockProduct::find($restockRequest->id));
    }

    /** @return array{0: Model, 1: string, 2: string} */
    private function createColorPricedProduct(): array
    {
        $colorCode = (string) $this->requireFixture(Color::query()->value('code'), 'colour');
        $colorNames = app(ColorService::class)->getNameMap();

        $product = $this->service()->create(
            addedBy: ProductWholesaleService::ADDED_BY_ADMIN,
            userId: null,
            shopId: 0,
            data: [
                'lang' => ['en'],
                'name' => ['Wholesale restock notification fixture'],
                'description' => ['Wholesale restock notification fixture'],
                'code' => 'WSRSTKN'.random_int(10000, 99999),
                'category_id' => $this->categoryId(),
                'unit' => 'pc',
                'shipping_cost' => 0,
                'discount' => 0,
                'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
                'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
                'minimum_order_qty' => 1,
                'current_stock' => 200,
                'colors_active' => 1,
                'colors' => [$colorCode],
                'quantity_based_price' => 1,
                'tier_min_qty' => [1],
                'tier_max_qty' => [''],
                'tier_unit_price' => [25],
            ],
            colorNames: $colorNames,
        );

        $this->service()->updateStatus($product->id, true);
        Product::query()->whereKey($product->id)->update(['request_status' => ProductWholesaleService::REQUEST_STATUS_APPROVED]);

        $product = $this->reload($product);
        $variant = $this->service()->getVariantKey(product: $product, selection: ['color' => $colorCode], colorNames: $colorNames);

        return [$product, $colorCode, $variant];
    }

    private function service(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function reload(Model $product): Model
    {
        return $this->service()->findUnscoped($product->id, ['wholesaleDetail', 'stocks', 'seller']);
    }

    private function categoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }
}
