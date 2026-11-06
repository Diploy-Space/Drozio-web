<?php

namespace Tests\Feature\Product;

use App\Enums\BusinessModule;
use App\Models\Seller;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\TaxModule\app\Models\SystemTaxSetup;
use Modules\TaxModule\app\Services\New\SystemTaxSetupService;
use Modules\TaxModule\app\Services\SystemTaxSetupService as TaxSetupCache;
use Modules\Wholesale\app\Http\Requests\Shared\Product\AddRequest;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Utils\ProductWizardFormState;
use ReflectionMethod;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductPricingTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
        $this->setTaxType(BusinessModule::WHOLESALE, 'order_wise');
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_the_product_discount_is_dropped_when_pricing_is_variation_based(): void
    {
        $request = $this->preparedRequest([
            ...$this->productPayload(),
            'variation_based_price' => 1,
            'discount' => 25,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
        ]);

        $this->assertSame(0, $request->input('discount'));
        $this->assertSame(ProductWholesaleService::DISCOUNT_TYPE_FLAT, $request->input('discount_type'));
    }

    public function test_the_product_discount_is_dropped_when_both_price_setups_are_chosen(): void
    {
        $request = $this->preparedRequest([
            ...$this->productPayload(),
            'variation_based_price' => 1,
            'quantity_based_price' => 1,
            'discount' => 25,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
        ]);

        $this->assertSame(0, $request->input('discount'));
    }

    public function test_the_product_discount_is_kept_when_pricing_is_quantity_based(): void
    {
        $request = $this->preparedRequest([
            ...$this->productPayload(),
            'quantity_based_price' => 1,
            'discount' => 25,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
        ]);

        $this->assertSame(25, $request->input('discount'));
        $this->assertSame(ProductWholesaleService::DISCOUNT_TYPE_PERCENT, $request->input('discount_type'));
    }

    public function test_a_disabled_discount_type_is_supplied_when_pricing_is_variation_based(): void
    {
        $payload = $this->productPayload();
        unset($payload['discount'], $payload['discount_type']);

        $request = $this->preparedRequest([...$payload, 'variation_based_price' => 1]);

        $this->assertSame(ProductWholesaleService::DISCOUNT_TYPE_FLAT, $request->input('discount_type'));
        $this->assertSame(0, $request->input('discount'));
    }

    public function test_a_draft_discount_cannot_survive_a_switch_to_variation_pricing(): void
    {
        $seller = $this->seller();
        $token = $this->mintedToken($seller);

        $this->recordStockPriceStep($seller, $token, [
            'discount' => 15,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
        ]);

        $request = $this->preparedRequest([
            ...$this->productPayload(),
            'token' => $token,
            'variation_based_price' => 1,
        ]);

        $this->assertSame(0, $request->input('discount'));
    }

    public function test_variation_pricing_is_rejected_without_a_product_variation(): void
    {
        $seller = $this->seller();
        $token = $this->mintedToken($seller);

        $step = [...$this->stockPricePayload(), 'variation_based_price' => 1];

        $this->actingAs($seller, 'seller')->post('vendor/wholesale/products/step', [
            ...$step,
            'step' => ProductWizardFormState::STEP_STOCK_PRICE,
            'token' => $token,
            'form_state' => http_build_query($step),
        ])->assertSessionHasErrors('price_setup');
    }

    public function test_a_first_tier_starting_above_the_minimum_order_quantity_is_rejected(): void
    {
        $seller = $this->seller();
        $token = $this->mintedToken($seller);

        $step = [...$this->stockPricePayload(), 'minimum_order_qty' => 40];

        $this->actingAs($seller, 'seller')->post('vendor/wholesale/products/step', [
            ...$step,
            'step' => ProductWizardFormState::STEP_STOCK_PRICE,
            'token' => $token,
            'form_state' => http_build_query($step),
        ])->assertSessionHasErrors('tier_min_qty.0');
    }

    public function test_the_first_tier_row_follows_the_minimum_order_quantity(): void
    {
        $rows = $this->productService()->getQuantityTierRows([
            ...$this->stockPricePayload(),
            'minimum_order_qty' => 12,
            'tier_min_qty' => [15, 21],
            'tier_max_qty' => [20, 25],
            'tier_unit_price' => [10, 9],
        ])['rows'];

        $this->assertSame('12', $rows[0]['min_qty']);
        $this->assertTrue($rows[0]['min_readonly']);
        $this->assertSame('21', $rows[1]['min_qty']);
    }

    public function test_quantity_pricing_alone_passes_the_stock_and_price_step(): void
    {
        $seller = $this->seller();
        $token = $this->mintedToken($seller);

        $this->recordStockPriceStep($seller, $token, []);
    }

    public function test_colors_alone_are_offered_as_the_variation_type(): void
    {
        $options = $this->productService()->getVariationTypeOptions(
            $this->colorOnlyVariationData(),
            [ProductWholesaleService::VARIATION_TYPE_COLOR => 'Color'],
        );

        $this->assertSame([[
            'id' => ProductWholesaleService::VARIATION_TYPE_COLOR,
            'name' => 'Color',
            'selected' => true,
        ]], $options);
    }

    public function test_colors_are_offered_ahead_of_the_selected_attributes(): void
    {
        $attributeId = $this->attributeId();

        $typeIds = $this->productService()->getVariationTypeIds([
            ...$this->colorOnlyVariationData(),
            'choice_attributes' => [$attributeId],
        ]);

        $this->assertSame([ProductWholesaleService::VARIATION_TYPE_COLOR, (string) $attributeId], $typeIds);
    }

    public function test_no_variation_type_is_offered_while_the_colors_toggle_is_off(): void
    {
        $data = $this->colorOnlyVariationData();
        unset($data['colors_active']);

        $this->assertSame([], $this->productService()->getVariationTypeIds($data));
    }

    public function test_the_variation_price_rows_are_named_after_the_selected_colors(): void
    {
        $rows = $this->productService()->getVariationPricingRows(
            [...$this->colorOnlyVariationData(), 'variation_based_price' => 1],
            [],
            [ProductWholesaleService::VARIATION_TYPE_COLOR => 'Color'],
            $this->colorNames(),
        );

        $this->assertSame(['Color IndianRed', 'Color Salmon'], array_column($rows, 'label'));
        $this->assertSame(
            ['variation_price[IndianRed]', 'variation_price[Salmon]'],
            array_column($rows, 'price_name'),
        );
    }

    public function test_the_stock_and_price_step_accepts_colors_as_the_variation_type(): void
    {
        $seller = $this->seller();
        $token = $this->mintedToken($seller);

        $step = [
            ...$this->variationOnlyStockPricePayload(),
            ...$this->colorOnlyVariationData(),
            'variation_based_price' => 1,
            'variation_type' => ProductWholesaleService::VARIATION_TYPE_COLOR,
            'variation_price' => ['IndianRed' => 120, 'Salmon' => 140],
            'sku_IndianRed' => 'FIXTURE-RED',
            'qty_IndianRed' => 10,
            'sku_Salmon' => 'FIXTURE-SALMON',
            'qty_Salmon' => 12,
        ];

        $this->actingAs($seller, 'seller')->post('vendor/wholesale/products/step', [
            ...$step,
            'step' => ProductWizardFormState::STEP_STOCK_PRICE,
            'token' => $token,
            'form_state' => http_build_query($step),
        ])->assertOk();
    }

    public function test_the_stock_and_price_step_rejects_a_variation_type_the_product_does_not_use(): void
    {
        $seller = $this->seller();
        $token = $this->mintedToken($seller);
        $step = [
            ...$this->stockPricePayload(),
            ...$this->colorOnlyVariationData(),
            'variation_based_price' => 1,
            'variation_type' => '999999',
        ];

        $this->actingAs($seller, 'seller')->post('vendor/wholesale/products/step', [
            ...$step,
            'step' => ProductWizardFormState::STEP_STOCK_PRICE,
            'token' => $token,
            'form_state' => http_build_query($step),
        ])->assertSessionHasErrors('variation_type');
    }

    private function variationOnlyStockPricePayload(): array
    {
        $payload = $this->stockPricePayload();

        unset($payload['quantity_based_price'], $payload['tier_min_qty'], $payload['tier_max_qty'], $payload['tier_unit_price']);

        return $payload;
    }

    private function colorOnlyVariationData(): array
    {
        return ['colors_active' => 'on', 'colors' => ['#CD5C5C', '#FA8072']];
    }

    private function colorNames(): array
    {
        return ['#CD5C5C' => 'IndianRed', '#FA8072' => 'Salmon'];
    }

    private function attributeId(): int
    {
        return (int) app(AttributeService::class)->getIds()[0];
    }

    private function productService(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function preparedRequest(array $payload): AddRequest
    {
        $request = app()->build(AddRequest::class);
        $request->initialize([], $payload, [], [], [], ['REQUEST_METHOD' => 'POST']);

        $prepare = new ReflectionMethod($request, 'prepareForValidation');
        $prepare->setAccessible(true);
        $prepare->invoke($request);

        return $request;
    }

    private function recordStockPriceStep(Seller $seller, string $token, array $extra): void
    {
        $step = [...$this->stockPricePayload(), ...$extra];

        $this->actingAs($seller, 'seller')->post('vendor/wholesale/products/step', [
            ...$step,
            'step' => ProductWizardFormState::STEP_STOCK_PRICE,
            'token' => $token,
            'form_state' => http_build_query($step),
        ])->assertOk();
    }

    private function mintedToken(Seller $seller): string
    {
        $response = $this->actingAs($seller, 'seller')->get('vendor/wholesale/products/add');

        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        return (string) ($query['token'] ?? '');
    }

    private function seller(): Seller
    {
        return Seller::query()->where('status', 'approved')->firstOrFail();
    }

    private function stockPricePayload(): array
    {
        return [
            'minimum_order_qty' => 50,
            'current_stock' => 500,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'quantity_based_price' => 1,
            'tier_min_qty' => [50],
            'tier_max_qty' => [500],
            'tier_unit_price' => [45],
        ];
    }

    private function productPayload(): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Wholesale pricing fixture'],
            'description' => ['Wholesale pricing fixture'],
            'code' => 'WSPRC'.random_int(10000, 99999),
            'unit' => 'pc',
            'shipping_cost' => 10,
            'minimum_order_qty' => 1,
            'current_stock' => 5,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
        ];
    }

    private function setTaxType(string $businessModule, string $taxType): void
    {
        SystemTaxSetup::query()
            ->where('business_module', $businessModule)
            ->where('tax_payer', SystemTaxSetupService::TAX_PAYER_VENDOR)
            ->firstOrFail()
            ->update(['tax_type' => $taxType, 'is_included' => 0, 'is_active' => 1]);

        TaxSetupCache::clearTaxSystemTypeCache();
    }
}
