<?php

namespace Tests\Feature\Product;

use App\Models\Seller;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Utils\ProductWizardFormState;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductStockValidationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const OVERSIZED_QUANTITY = '100123456789053';

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

    public function test_an_oversized_variation_quantity_reports_only_the_variation_error(): void
    {
        $response = $this->postStockPriceStep([
            'qty_IndianRed' => self::OVERSIZED_QUANTITY,
            'qty_LightCoral' => '90',
            'current_stock' => (string) ((int) self::OVERSIZED_QUANTITY + 90),
        ]);

        $response->assertSessionHasErrors('qty_IndianRed');
        $response->assertSessionDoesntHaveErrors('current_stock');
    }

    public function test_variation_quantities_whose_total_overflows_the_stock_limit_are_rejected(): void
    {
        $response = $this->postStockPriceStep([
            'qty_IndianRed' => (string) ProductWholesaleService::STOCK_QUANTITY_MAX,
            'qty_LightCoral' => '1',
            'current_stock' => (string) (ProductWholesaleService::STOCK_QUANTITY_MAX + 1),
        ]);

        $response->assertSessionHasErrors('current_stock');
        $response->assertSessionDoesntHaveErrors(['qty_IndianRed', 'qty_LightCoral']);
    }

    public function test_an_oversized_stock_without_variations_is_still_rejected(): void
    {
        $response = $this->postStockPriceStep(
            fields: ['current_stock' => self::OVERSIZED_QUANTITY],
            withVariations: false,
        );

        $response->assertSessionHasErrors('current_stock');
    }

    private function postStockPriceStep(array $fields, bool $withVariations = true)
    {
        $seller = $this->seller();
        $token = $this->mintedToken($seller);

        $variationFields = $withVariations
            ? [
                'colors_active' => 'on',
                'colors' => ['#CD5C5C', '#F08080'],
                'sku_IndianRed' => 'STOCKTEST-IndianRed',
                'sku_LightCoral' => 'STOCKTEST-LightCoral',
            ]
            : [];

        return $this->actingAs($seller, 'seller')->post('vendor/wholesale/products/step', [
            ...$variationFields,
            ...$fields,
            'step' => ProductWizardFormState::STEP_STOCK_PRICE,
            'token' => $token,
            'form_state' => '',
        ]);
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
}
