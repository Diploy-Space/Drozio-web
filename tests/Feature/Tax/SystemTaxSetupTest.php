<?php

namespace Tests\Feature\Tax;

use App\Enums\BusinessModule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\TaxModule\app\Models\SystemTaxSetup;
use Modules\TaxModule\app\Services\New\SystemTaxSetupService;
use Modules\TaxModule\app\Services\SystemTaxSetupService as TaxSetupCache;
use Modules\TaxModule\app\Traits\VatTaxManagement;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class SystemTaxSetupTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use VatTaxManagement;

    private SystemTaxSetupService $systemTaxSetupService;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->systemTaxSetupService = app(SystemTaxSetupService::class);
    }

    public function test_excluding_vat_from_the_price_stores_the_vat_type_and_rates(): void
    {
        $setup = $this->wholesaleSetup();

        $updated = $this->systemTaxSetupService->update(id: $setup->id, data: [
            'tax_status' => 'exclude',
            'tax_type' => 'product_wise',
            'tax_ids' => null,
        ]);

        $this->assertSame(0, $updated->is_included);
        $this->assertSame('product_wise', $updated->tax_type);
    }

    public function test_including_vat_in_the_price_keeps_the_stored_vat_type(): void
    {
        $setup = $this->wholesaleSetup(['tax_type' => 'product_wise', 'is_included' => 0]);

        $updated = $this->systemTaxSetupService->update(id: $setup->id, data: ['tax_status' => 'include']);

        $this->assertSame(1, $updated->is_included);
        $this->assertSame('product_wise', $updated->tax_type);
    }

    public function test_including_vat_in_the_price_ignores_a_posted_vat_type_and_rate(): void
    {
        $setup = $this->wholesaleSetup(['tax_type' => 'product_wise', 'is_included' => 1]);

        $updated = $this->systemTaxSetupService->update(id: $setup->id, data: [
            'tax_status' => 'include',
            'tax_type' => 'order_wise',
            'tax_ids' => [1],
        ]);

        $this->assertSame('product_wise', $updated->tax_type);
        $this->assertNull($updated->tax_ids);
    }

    public function test_the_product_rate_selector_follows_the_vat_type_only_when_vat_is_excluded(): void
    {
        $setup = $this->wholesaleSetup(['tax_type' => 'product_wise', 'is_included' => 0, 'is_active' => 1]);

        $this->assertTrue($this->productWiseSelectorShown(BusinessModule::WHOLESALE));

        $this->systemTaxSetupService->update(id: $setup->id, data: ['tax_status' => 'include']);
        TaxSetupCache::clearTaxSystemTypeCache();

        $this->assertFalse($this->productWiseSelectorShown(BusinessModule::WHOLESALE));
    }

    public function test_reading_the_setup_of_a_business_module_without_one_creates_no_row(): void
    {
        SystemTaxSetup::query()->where('business_module', BusinessModule::WHOLESALE)->delete();
        TaxSetupCache::clearTaxSystemTypeCache();

        $rowCount = SystemTaxSetup::query()->count();

        $taxData = self::getTaxSystemType(businessModule: BusinessModule::WHOLESALE);

        $this->assertSame($rowCount, SystemTaxSetup::query()->count());
        $this->assertFalse($taxData['SystemTaxVat']->exists);
        $this->assertSame(0, $taxData['SystemTaxVat']['is_active']);
        $this->assertCount(0, $taxData['SystemTaxVat']['additionalData']);
        $this->assertFalse($taxData['productWiseTax']);
    }

    private function productWiseSelectorShown(string $businessModule): bool
    {
        $taxData = self::getTaxSystemType(businessModule: $businessModule);

        return $taxData['productWiseTax'] && ! $taxData['is_included'];
    }

    private function wholesaleSetup(array $attributes = []): SystemTaxSetup
    {
        SystemTaxSetup::query()->where('business_module', BusinessModule::WHOLESALE)->delete();
        TaxSetupCache::clearTaxSystemTypeCache();

        return SystemTaxSetup::query()->create(array_merge([
            'tax_type' => 'order_wise',
            'country_code' => null,
            'tax_payer' => SystemTaxSetupService::TAX_PAYER_VENDOR,
            'business_module' => BusinessModule::WHOLESALE,
            'is_default' => true,
            'is_active' => 1,
            'is_included' => 0,
        ], $attributes));
    }
}
