<?php

namespace Tests\Unit;

use App\Enums\BusinessModule;
use App\Models\Product;
use App\Services\New\ProductService;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class ProductServiceTaxAttributesTest extends TestCase
{
    public function test_create_defaults_the_tax_columns_when_the_form_omits_them(): void
    {
        $attributes = $this->service()->createAttributes($this->formData());

        $this->assertSame(0, $attributes['tax']);
        $this->assertSame(ProductService::TAX_TYPE_PERCENT, $attributes['tax_type']);
        $this->assertSame(ProductService::TAX_MODEL_EXCLUDE, $attributes['tax_model']);
    }

    public function test_update_keeps_the_stored_tax_columns_when_the_form_omits_them(): void
    {
        $product = new Product(['tax' => '5', 'tax_type' => 'percent', 'tax_model' => 'include']);

        $attributes = $this->service()->updateAttributes($this->formData(), $product);

        $this->assertSame(5.0, $attributes['tax']);
        $this->assertSame('percent', $attributes['tax_type']);
        $this->assertSame('include', $attributes['tax_model']);
    }

    public function test_a_posted_tax_still_wins_over_the_stored_and_default_values(): void
    {
        $data = $this->formData() + ['tax' => 12, 'tax_type' => 'percent', 'tax_model' => 'include'];

        $created = $this->service()->createAttributes($data);
        $updated = $this->service()->updateAttributes($data, new Product(['tax_model' => 'exclude']));

        $this->assertSame(12, $created['tax']);
        $this->assertSame('include', $created['tax_model']);
        $this->assertSame(12, $updated['tax']);
        $this->assertSame('include', $updated['tax_model']);
    }

    public function test_update_falls_back_to_the_defaults_when_the_stored_tax_columns_are_blank(): void
    {
        $product = new Product(['tax' => null, 'tax_type' => '', 'tax_model' => '']);

        $attributes = $this->service()->updateAttributes($this->formData(), $product);

        $this->assertSame(0, $attributes['tax']);
        $this->assertSame(ProductService::TAX_TYPE_PERCENT, $attributes['tax_type']);
        $this->assertSame(ProductService::TAX_MODEL_EXCLUDE, $attributes['tax_model']);
    }

    private function formData(): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Test product'],
            'description' => ['Test description'],
            'code' => 'ABC123',
            'category_id' => 1,
            'sub_category_id' => null,
            'sub_sub_category_id' => null,
            'brand_id' => null,
            'unit' => 'pc',
            'product_type' => 'physical',
            'unit_price' => 100,
            'discount' => 0,
            'discount_type' => 'flat',
            'choice_attributes' => [],
            'current_stock' => 10,
            'minimum_order_qty' => 1,
            'video_url' => null,
            'shipping_cost' => 0,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    private function service(): object
    {
        return new class(new Product) extends ProductService
        {
            protected function businessModule(): string
            {
                return BusinessModule::RETAIL;
            }

            public function createAttributes(array $data): array
            {
                return $this->buildCommonAttributes($data, ['image_names' => []], [], self::ADDED_BY_ADMIN, 1, 1, []);
            }

            public function updateAttributes(array $data, Model $product): array
            {
                return $this->buildCommonUpdateAttributes($data, ['image_names' => []], [], $product, self::ADDED_BY_ADMIN, []);
            }
        };
    }
}
