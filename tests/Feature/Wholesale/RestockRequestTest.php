<?php

namespace Tests\Feature\Wholesale;

use App\Enums\BusinessModule;
use App\Events\RequestProductRestockEvent;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\RestockProduct;
use App\Models\User;
use App\Services\New\ColorService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class RestockRequestTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const RESTOCK_REQUESTS_URI = '/api/v6/wholesale/customer/restock-requests';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();

        Event::fake([RequestProductRestockEvent::class]);
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_api_store_saves_server_derived_variant_and_returns_fcm_topic(): void
    {
        $customer = $this->customer();
        [$product, $colorCode, $expectedVariant] = $this->createColorPricedProduct();

        $response = $this->postJson(self::RESTOCK_REQUESTS_URI, ['id' => $product->id, 'variation' => ['color' => $colorCode]], $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('identical_code', 'restock_request_sent_200');

        $this->assertNotEmpty($response->json('content.topic'));

        $restockRequest = RestockProduct::where(['product_id' => $product->id, 'variant' => $expectedVariant])->first();
        $this->assertNotNull($restockRequest);
    }

    public function test_api_store_ignores_a_client_supplied_variation_code(): void
    {
        $customer = $this->customer();
        [$product, $colorCode, $expectedVariant] = $this->createColorPricedProduct();

        $this->postJson(self::RESTOCK_REQUESTS_URI, [
            'id' => $product->id,
            'variation' => ['color' => $colorCode],
            'product_variation_code' => 'Hacked',
        ], $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('identical_code', 'restock_request_sent_200');

        $this->assertNotNull(RestockProduct::where(['product_id' => $product->id, 'variant' => $expectedVariant])->first());
        $this->assertNull(RestockProduct::where(['product_id' => $product->id, 'variant' => 'Hacked'])->first());
    }

    public function test_api_store_second_request_from_same_customer_conflicts(): void
    {
        $customer = $this->customer();
        [$product, $colorCode] = $this->createColorPricedProduct();
        $payload = ['id' => $product->id, 'variation' => ['color' => $colorCode]];

        $this->postJson(self::RESTOCK_REQUESTS_URI, $payload, $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('identical_code', 'restock_request_sent_200');

        $this->postJson(self::RESTOCK_REQUESTS_URI, $payload, $this->actingAsCustomer($customer))
            ->assertStatus(409)
            ->assertJsonPath('identical_code', 'restock_request_already_sent_409');
    }

    public function test_api_store_refuses_a_guest(): void
    {
        [$product, $colorCode] = $this->createColorPricedProduct();

        $response = $this->postJson(self::RESTOCK_REQUESTS_URI, ['id' => $product->id, 'variation' => ['color' => $colorCode], ...$this->guestParams()]);

        $response->assertStatus(403)->assertJsonPath('identical_code', 'restock_request_sign_in_required_403');
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_price_quote_no_longer_reports_restock_state(): void
    {
        [$product, $colorCode] = $this->createColorPricedProduct();

        $this->postJson(route('wholesale.products.price-quote', ['id' => $product->id]), ['variation' => ['color' => $colorCode], 'quantity' => 50])
            ->assertOk()
            ->assertJsonMissingPath('is_restockable')
            ->assertJsonMissingPath('is_restock_requested');
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
                'name' => ['Wholesale restock fixture'],
                'description' => ['Wholesale restock fixture'],
                'code' => 'WSRSTK'.random_int(10000, 99999),
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
        $expectedVariant = $this->service()->getVariantKey(product: $product, selection: ['color' => $colorCode], colorNames: $colorNames);

        return [$product, $colorCode, $expectedVariant];
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

    private function customer(): User
    {
        return $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');
    }
}
