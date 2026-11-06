<?php

namespace Tests\Feature\Product;

use App\Enums\BusinessModule;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\AI\app\PromptTemplates\Wholesale\PricingSetupTemplate;
use Modules\AI\app\Response\WholesaleAIResponse;
use Modules\TaxModule\app\Models\SystemTaxSetup;
use Modules\TaxModule\app\Models\Tax;
use Modules\TaxModule\app\Models\Taxable;
use Modules\TaxModule\app\Services\New\SystemTaxSetupService;
use Modules\TaxModule\app\Services\SystemTaxSetupService as TaxSetupCache;
use Modules\TaxModule\app\Traits\VatTaxManagement;
use Modules\Wholesale\app\Http\Controllers\Admin\Products\ProductController;
use Modules\Wholesale\app\Http\Requests\Shared\Product\AddRequest;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Utils\ProductWizardFormState;
use ReflectionMethod;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductTaxTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;
    use VatTaxManagement;

    private const SETUP_ENDPOINT = 'api/v6/seller/vat-tax/setup';

    private const WHOLESALE_SETUP_ENDPOINT = 'api/v6/wholesale/seller/vat-tax/setup';

    private const LIST_ENDPOINT = 'api/v6/seller/vat-tax/list';

    private const WHOLESALE_LIST_ENDPOINT = 'api/v6/wholesale/seller/vat-tax/list';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_wholesale_screens_read_the_wholesale_setup_not_the_retail_one(): void
    {
        $this->setTaxType(BusinessModule::RETAIL, 'order_wise');
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');

        $wholesale = ProductController::getTaxSystemType(businessModule: BusinessModule::WHOLESALE);
        $retail = ProductController::getTaxSystemType(businessModule: BusinessModule::RETAIL);

        $this->assertTrue($wholesale['productWiseTax']);
        $this->assertFalse($retail['productWiseTax']);
        $this->assertNotSame($retail['SystemTaxVat']->id, $wholesale['SystemTaxVat']->id);
    }

    public function test_a_wholesale_product_stores_its_rates_against_the_wholesale_setup(): void
    {
        $setup = $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');
        $product = $this->wholesaleProduct();
        $tax = $this->activeTax();

        $this->getAddTaxData(
            taxableType: Product::class,
            taxableId: $product->id,
            taxIds: [$tax->id],
            businessModule: BusinessModule::WHOLESALE,
        );

        $stored = Taxable::query()
            ->where('taxable_type', Product::class)
            ->where('taxable_id', $product->id)
            ->firstOrFail();

        $this->assertSame($setup->id, (int) $stored->system_tax_setup_id);
        $this->assertNotSame($this->setupOf(BusinessModule::RETAIL)->id, (int) $stored->system_tax_setup_id);
    }

    public function test_updating_a_wholesale_product_replaces_its_rates_against_the_wholesale_setup(): void
    {
        $setup = $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');
        $product = $this->wholesaleProduct();
        $rates = $this->activeTaxes(2);

        $this->getAddTaxData(
            taxableType: Product::class,
            taxableId: $product->id,
            taxIds: [$rates[0]->id],
            businessModule: BusinessModule::WHOLESALE,
        );

        $this->getUpdateTaxData(
            taxableType: Product::class,
            taxableId: $product->id,
            taxIds: [$rates[1]->id],
            oldTaxIds: [$rates[0]->id],
            businessModule: BusinessModule::WHOLESALE,
        );

        $stored = Taxable::query()
            ->where('taxable_type', Product::class)
            ->where('taxable_id', $product->id)
            ->get();

        $this->assertSame([$rates[1]->id], $stored->pluck('tax_id')->map(intval(...))->all());
        $this->assertSame([$setup->id], $stored->pluck('system_tax_setup_id')->map(intval(...))->unique()->values()->all());
    }

    public function test_the_seller_api_reports_the_retail_setup_even_when_another_module_is_asked_for(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');
        $this->setTaxType(BusinessModule::RETAIL, 'order_wise');

        $response = $this->getJson(
            self::SETUP_ENDPOINT.'?business_module='.BusinessModule::WHOLESALE,
            $this->sellerHeaders($this->seller()),
        );

        $response->assertOk();
        $this->assertFalse($response->json('content.is_product_wise'));
        $this->assertNull($response->json('content.taxes'));
        $this->assertNull($response->json('content.params'));
    }

    public function test_the_seller_list_api_returns_only_retail_rates(): void
    {
        $response = $this->getJson(
            self::LIST_ENDPOINT.'?limit=all&business_module='.BusinessModule::WHOLESALE,
            $this->sellerHeaders($this->seller()),
        );

        $response->assertOk();
        $this->assertSame(
            $this->activeRateIdsOf(BusinessModule::RETAIL),
            collect($response->json('content.data'))->pluck('id')->sort()->values()->all(),
        );
    }

    public function test_the_wholesale_seller_list_api_returns_only_wholesale_rates(): void
    {
        $response = $this->getJson(
            self::WHOLESALE_LIST_ENDPOINT.'?limit=all',
            $this->sellerHeaders($this->seller()),
        );

        $response->assertOk();
        $this->assertSame(
            $this->activeRateIdsOf(BusinessModule::WHOLESALE),
            collect($response->json('content.data'))->pluck('id')->sort()->values()->all(),
        );
    }

    public function test_the_seller_api_refuses_an_unauthenticated_caller(): void
    {
        $this->getJson(self::SETUP_ENDPOINT)->assertStatus(401);
    }

    public function test_the_wholesale_seller_api_returns_the_wholesale_setup_without_a_business_module(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');
        $this->setTaxType(BusinessModule::RETAIL, 'order_wise');

        $response = $this->getJson(
            self::WHOLESALE_SETUP_ENDPOINT,
            $this->sellerHeaders($this->seller()),
        );

        $response->assertOk();
        $this->assertTrue($response->json('content.is_product_wise'));
        $this->assertFalse($response->json('content.is_category_wise'));
        $this->assertFalse($response->json('content.is_included'));
        $this->assertNull($response->json('content.taxes'));
    }

    public function test_the_wholesale_seller_api_never_reports_the_retail_setup(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'order_wise');
        $this->setTaxType(BusinessModule::RETAIL, 'product_wise');

        $response = $this->getJson(
            self::WHOLESALE_SETUP_ENDPOINT,
            $this->sellerHeaders($this->seller()),
        );

        $response->assertOk();
        $this->assertFalse($response->json('content.is_product_wise'));
    }

    public function test_the_wholesale_seller_api_drops_product_wise_tax_while_tax_is_included(): void
    {
        $setup = $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');
        $setup->update(['is_included' => 1]);
        TaxSetupCache::clearTaxSystemTypeCache();

        $response = $this->getJson(
            self::WHOLESALE_SETUP_ENDPOINT,
            $this->sellerHeaders($this->seller()),
        );

        $response->assertOk();
        $this->assertTrue($response->json('content.is_included'));
        $this->assertFalse($response->json('content.is_product_wise'));
    }

    public function test_the_wholesale_seller_api_refuses_an_unauthenticated_caller(): void
    {
        $this->getJson(self::WHOLESALE_SETUP_ENDPOINT)->assertStatus(401);
    }

    public function test_the_tax_field_is_required_while_product_wise_tax_is_on(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');

        $this->actingAs($this->seller(), 'seller')
            ->post('vendor/wholesale/products/add', $this->productPayload())
            ->assertSessionHasErrors('tax_ids');
    }

    public function test_the_tax_field_is_refused_while_product_wise_tax_is_off(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'order_wise');
        $seller = $this->seller();

        $this->actingAs($seller, 'seller')
            ->post('vendor/wholesale/products/add', $this->productPayload())
            ->assertSessionDoesntHaveErrors('tax_ids');

        $this->actingAs($seller, 'seller')
            ->post('vendor/wholesale/products/add', [
                ...$this->productPayload(),
                'tax_ids' => [$this->activeTax()->id],
            ])
            ->assertSessionHasErrors('tax_ids');
    }

    public function test_a_rate_outside_the_vat_and_tax_list_is_refused(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');

        $this->actingAs($this->seller(), 'seller')
            ->post('vendor/wholesale/products/add', [
                ...$this->productPayload(),
                'tax_ids' => [(int) Tax::query()->max('id') + 1000],
            ])
            ->assertSessionHasErrors('tax_ids.0');
    }

    public function test_the_product_form_no_longer_asks_for_a_tax_calculation_model(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');

        $this->actingAs($this->seller(), 'seller')
            ->post('vendor/wholesale/products/add', $this->productPayload())
            ->assertSessionDoesntHaveErrors('tax_model');
    }

    public function test_the_tax_calculation_model_follows_the_vat_and_tax_setup(): void
    {
        $setup = $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');

        $this->assertSame(
            ProductWholesaleService::TAX_MODEL_EXCLUDE,
            $this->postedTaxModel(['tax_model' => ProductWholesaleService::TAX_MODEL_INCLUDE]),
        );

        $setup->update(['is_included' => 1]);
        TaxSetupCache::clearTaxSystemTypeCache();

        $this->assertSame(
            ProductWholesaleService::TAX_MODEL_INCLUDE,
            $this->postedTaxModel(['tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE]),
        );
    }

    public function test_the_ai_pricing_prompt_offers_only_the_wholesale_rates(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');

        $prompt = (new PricingSetupTemplate)->build(context: 'Cotton tee', description: 'Bulk cotton t-shirts');

        $this->assertStringContainsString('tax_names', $prompt);

        foreach ($this->activeTaxes(2) as $rate) {
            $this->assertStringContainsString(strtolower($rate->name), $prompt);
        }
    }

    public function test_the_ai_pricing_prompt_drops_tax_while_product_wise_tax_is_off(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'order_wise');

        $this->assertStringNotContainsString(
            'tax_names',
            (new PricingSetupTemplate)->build(context: 'Cotton tee', description: 'Bulk cotton t-shirts'),
        );
    }

    public function test_the_ai_pricing_result_maps_rate_names_onto_wholesale_rate_ids(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');
        $rates = $this->activeTaxes(2);

        $suggested = (new WholesaleAIResponse)->pricingSetup(
            $this->aiPricingResult([$rates[0]->name, $rates[1]->name, 'A rate nobody configured']),
        );

        $this->assertSame([$rates[0]->id, $rates[1]->id], $suggested['tax_ids']);
    }

    public function test_the_ai_pricing_result_suggests_no_rate_while_product_wise_tax_is_off(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'order_wise');

        $suggested = (new WholesaleAIResponse)->pricingSetup(
            $this->aiPricingResult([$this->activeTax()->name]),
        );

        $this->assertSame([], $suggested['tax_ids']);
    }

    public function test_a_draft_recorded_before_the_admin_turned_tax_off_does_not_block_the_final_submit(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');
        $seller = $this->seller();
        $rate = $this->activeTax();

        $token = $this->mintedToken($seller);

        $this->recordStockPriceStep($seller, $token, [$rate->id]);

        $this->setTaxType(BusinessModule::WHOLESALE, 'order_wise');

        $this->actingAs($seller, 'seller')
            ->post('vendor/wholesale/products/add', [...$this->productPayload(), 'token' => $token])
            ->assertSessionDoesntHaveErrors('tax_ids');
    }

    public function test_a_stale_form_still_posting_a_rate_after_tax_was_turned_off_is_refused(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');
        $seller = $this->seller();
        $rate = $this->activeTax();
        $token = $this->mintedToken($seller);

        $this->recordStockPriceStep($seller, $token, [$rate->id]);
        $this->setTaxType(BusinessModule::WHOLESALE, 'order_wise');

        $this->actingAs($seller, 'seller')
            ->post('vendor/wholesale/products/add', [
                ...$this->productPayload(),
                'token' => $token,
                'tax_ids' => [$rate->id],
            ])
            ->assertSessionHasErrors('tax_ids');
    }

    public function test_a_draft_recorded_before_the_admin_turned_tax_on_asks_for_a_rate(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'order_wise');
        $seller = $this->seller();
        $token = $this->mintedToken($seller);

        $this->recordStockPriceStep($seller, $token, null);
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');

        $this->actingAs($seller, 'seller')
            ->post('vendor/wholesale/products/add', [...$this->productPayload(), 'token' => $token])
            ->assertSessionHasErrors('tax_ids');
    }

    public function test_the_vendor_is_told_when_a_stale_draft_rate_was_dropped(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'product_wise');
        $seller = $this->seller();
        $token = $this->mintedToken($seller);

        $this->recordStockPriceStep($seller, $token, [$this->activeTax()->id]);
        $this->setTaxType(BusinessModule::WHOLESALE, 'order_wise');

        $request = app()->build(AddRequest::class);
        $request->initialize([], [...$this->productPayload(), 'token' => $token], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $prepare = new ReflectionMethod($request, 'prepareForValidation');
        $prepare->setAccessible(true);
        $prepare->invoke($request);

        $this->assertNull($request->input('tax_ids'));
        $this->assertTrue($request->hasDiscardedTaxRates());
    }

    public function test_no_notice_when_nothing_was_dropped(): void
    {
        $this->setTaxType(BusinessModule::WHOLESALE, 'order_wise');

        $request = app()->build(AddRequest::class);
        $request->initialize([], $this->productPayload(), [], [], [], ['REQUEST_METHOD' => 'POST']);

        $prepare = new ReflectionMethod($request, 'prepareForValidation');
        $prepare->setAccessible(true);
        $prepare->invoke($request);

        $this->assertFalse($request->hasDiscardedTaxRates());
    }

    private function recordStockPriceStep(Seller $seller, string $token, ?array $taxIds): void
    {
        $step = $taxIds === null
            ? $this->stockPricePayload()
            : [...$this->stockPricePayload(), 'tax_ids' => $taxIds];

        $this->actingAs($seller, 'seller')->post('vendor/wholesale/products/step', [
            ...$step,
            'step' => ProductWizardFormState::STEP_STOCK_PRICE,
            'token' => $token,
            'form_state' => http_build_query($step),
        ])->assertOk();
    }

    private function stockPricePayload(): array
    {
        return [
            'minimum_order_qty' => 50,
            'current_stock' => 500,
            'discount' => 0,
            'discount_type' => 'flat',
            'quantity_based_price' => 1,
            'tier_min_qty' => [50],
            'tier_max_qty' => [500],
            'tier_unit_price' => [45],
        ];
    }

    private function mintedToken(Seller $seller): string
    {
        $response = $this->actingAs($seller, 'seller')->get('vendor/wholesale/products/add');

        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        return (string) ($query['token'] ?? '');
    }

    private function aiPricingResult(array $taxNames): string
    {
        return json_encode([
            'price_setup' => 'quantity',
            'minimum_order_qty' => 50,
            'discount' => 0,
            'discount_type' => 'flat',
            'variation_discount' => 5,
            'tax_names' => $taxNames,
            'tiers' => [
                ['min_qty' => 50, 'max_qty' => 500, 'unit_price' => 45],
                ['min_qty' => 501, 'max_qty' => 1000, 'unit_price' => 40],
                ['min_qty' => 1001, 'max_qty' => 1500, 'unit_price' => 35],
            ],
        ]);
    }

    private function postedTaxModel(array $payload): string
    {
        $request = app()->build(AddRequest::class);
        $request->initialize([], $payload, [], [], [], ['REQUEST_METHOD' => 'POST']);

        $prepare = new ReflectionMethod($request, 'prepareForValidation');
        $prepare->setAccessible(true);
        $prepare->invoke($request);

        return (string) $request->input('tax_model');
    }

    private function productPayload(): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Wholesale tax fixture'],
            'description' => ['Wholesale tax fixture'],
            'code' => 'WSTAX'.random_int(10000, 99999),
            'unit' => 'pc',
            'shipping_cost' => 10,
            'minimum_order_qty' => 1,
            'current_stock' => 5,
            'discount' => 0,
            'discount_type' => 'flat',
        ];
    }

    private function setTaxType(string $businessModule, string $taxType): SystemTaxSetup
    {
        $setup = $this->setupOf($businessModule);

        $setup->update(['tax_type' => $taxType, 'is_included' => 0, 'is_active' => 1]);

        TaxSetupCache::clearTaxSystemTypeCache();

        return $setup->refresh();
    }

    private function setupOf(string $businessModule): SystemTaxSetup
    {
        $setup = SystemTaxSetup::query()
            ->where('business_module', $businessModule)
            ->where('tax_payer', SystemTaxSetupService::TAX_PAYER_VENDOR)
            ->where('is_default', 1)
            ->first();

        return $setup ?? SystemTaxSetup::query()->create([
            'tax_type' => 'order_wise',
            'country_code' => null,
            'tax_payer' => SystemTaxSetupService::TAX_PAYER_VENDOR,
            'business_module' => $businessModule,
            'is_default' => true,
            'is_active' => 1,
        ]);
    }

    private function activeTax(): Tax
    {
        return $this->requireFixture(
            Tax::query()->where('is_active', 1)->where('is_default', 1)->first(),
            'active vat and tax rate',
        );
    }

    private function activeRateIdsOf(string $businessModule): array
    {
        return Tax::query()
            ->where('is_active', 1)
            ->where('business_module', $businessModule)
            ->orderBy('id')
            ->pluck('id')
            ->map(intval(...))
            ->all();
    }

    private function activeTaxes(int $count): array
    {
        $rates = Tax::query()->where('is_active', 1)->where('is_default', 1)->limit($count)->get();

        if ($rates->count() < $count) {
            $this->markTestSkipped('Test database has fewer than '.$count.' active vat and tax rates.');
        }

        return $rates->all();
    }

    private function wholesaleProduct(): Product
    {
        return $this->requireFixture(
            Product::query()->whereHas('wholesaleDetail')->first(),
            'wholesale product',
        );
    }

    private function seller(): Seller
    {
        return $this->requireFixture(
            Seller::query()->where('status', 'approved')->whereNotNull('auth_token')->first(),
            'approved seller with an auth token',
        );
    }
}
