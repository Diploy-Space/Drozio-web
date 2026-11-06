<?php

namespace Tests\Feature\Pos;

use App\Enums\SessionKey;
use App\Models\Admin;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class QuickViewStateTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const VARIATION_PRODUCT = 434;

    private const SIMPLE_PRODUCT = 1;

    private const WHITE = '#FFFFFF';

    private const RED = '#FF0000';

    private const NAVY = '#000080';

    private const WALK_IN_CART = 'walk-in-customer-qvstate001';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();

        $variations = collect(json_decode(DB::table('products_retail')->where('product_id', self::VARIATION_PRODUCT)->value('variation'), true))
            ->map(function (array $variation): array {
                $variation['qty'] = match ($variation['type']) {
                    'White-S' => 0,
                    'Red-M' => 99,
                    default => 50,
                };

                return $variation;
            })->all();
        DB::table('products_retail')->where('product_id', self::VARIATION_PRODUCT)->update(['variation' => json_encode($variations)]);
        DB::table('products')->where('id', self::SIMPLE_PRODUCT)->update(['current_stock' => 500]);

        $this->actingAs(Admin::query()->findOrFail(1), 'admin');
        $this->withSession([
            SessionKey::CURRENT_USER => self::WALK_IN_CART,
            SessionKey::CART_NAME => [self::WALK_IN_CART],
        ]);
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_an_empty_cart_opens_the_variation_product_on_its_first_in_stock_combination(): void
    {
        $view = $this->quickView(self::VARIATION_PRODUCT);

        $this->assertSame(self::WHITE, $this->checkedValue($view, 'color'));
        $this->assertSame('M', $this->checkedValue($view, 'choice_1'));
        $this->assertFalse($this->addButton($view)->hasAttribute('disabled'));
        $this->assertSame('Add To Cart', $this->addButtonLabel($view));
        $this->assertSame('50', $this->text($view, '//*[contains(@class,"current-stock-qty")]'));
        $this->assertFalse($this->isHidden($view, 'default-quantity-system'));
        $this->assertTrue($this->isHidden($view, 'in-cart-quantity-system'));
        $this->assertStringNotContainsString('d--none', $view);
    }

    public function test_the_in_cart_variation_is_preselected_with_its_cart_quantity(): void
    {
        $this->addVariation(color: self::RED, size: 'M', quantity: 3)->assertJsonPath('data.quantity', 3);

        $view = $this->quickView(self::VARIATION_PRODUCT);

        $this->assertSame(self::RED, $this->checkedValue($view, 'color'));
        $this->assertSame('M', $this->checkedValue($view, 'choice_1'));
        $this->assertSame('3', $this->inputValue($view, 'quantity_in_cart'));
        $this->assertFalse($this->isHidden($view, 'in-cart-quantity-system'));
        $this->assertTrue($this->isHidden($view, 'default-quantity-system'));
        $this->assertSame('Update Cart', $this->addButtonLabel($view));
        $this->assertFalse($this->addButton($view)->hasAttribute('disabled'));
        $this->assertSame('99', $this->text($view, '//*[contains(@class,"current-stock-qty")]'));
    }

    public function test_the_most_recently_added_variation_is_preselected(): void
    {
        $this->addVariation(color: self::RED, size: 'M', quantity: 3);
        $this->addVariation(color: self::NAVY, size: 'L', quantity: 2);

        $view = $this->quickView(self::VARIATION_PRODUCT);

        $this->assertSame(self::NAVY, $this->checkedValue($view, 'color'));
        $this->assertSame('L', $this->checkedValue($view, 'choice_1'));
        $this->assertSame('2', $this->inputValue($view, 'quantity_in_cart'));
    }

    public function test_a_selection_without_stock_is_not_available(): void
    {
        $emptySelection = $this->variantPrice(color: self::WHITE, size: 'S');
        $emptySelection->assertOk()
            ->assertJsonPath('selectedStock', 0)
            ->assertJsonPath('isSelectionAvailable', false)
            ->assertJsonPath('allVariationsOutOfStock', false);

        $this->variantPrice(color: self::RED, size: 'M')
            ->assertOk()
            ->assertJsonPath('selectedStock', 99)
            ->assertJsonPath('isSelectionAvailable', true);
    }

    public function test_a_combination_without_a_variation_row_is_not_available(): void
    {
        $this->variantPrice(color: self::RED, size: 'XXXL')
            ->assertOk()
            ->assertJsonPath('selectedStock', 0)
            ->assertJsonPath('isSelectionAvailable', false);
    }

    public function test_adding_a_combination_without_a_variation_row_is_rejected(): void
    {
        $response = $this->addVariation(color: self::RED, size: 'XXXL', quantity: 1);

        $response->assertOk()
            ->assertJsonPath('data', 'custom-error')
            ->assertJsonPath('text', 'This option combination is not available');
        $this->assertNotEmpty($response->json('title'));
        $this->assertSame([], array_values(array_filter(session(self::WALK_IN_CART) ?? [], 'is_array')));
    }

    public function test_a_simple_product_in_the_cart_renders_its_cart_quantity(): void
    {
        $this->post(route('admin.retail.pos.add-to-cart'), ['id' => self::SIMPLE_PRODUCT, 'quantity' => 3])->assertOk();

        $view = $this->quickView(self::SIMPLE_PRODUCT);

        $this->assertSame('3', $this->inputValue($view, 'quantity_in_cart'));
        $this->assertFalse($this->isHidden($view, 'in-cart-quantity-system'));
        $this->assertTrue($this->isHidden($view, 'default-quantity-system'));
        $this->assertSame('Update Cart', $this->addButtonLabel($view));
    }

    public function test_a_simple_product_without_stock_renders_a_disabled_button(): void
    {
        DB::table('products')->where('id', self::SIMPLE_PRODUCT)->update(['current_stock' => 0]);

        $view = $this->quickView(self::SIMPLE_PRODUCT);

        $this->assertTrue($this->addButton($view)->hasAttribute('disabled'));
        $this->assertSame('Out of Stock', $this->addButtonLabel($view));
    }

    private function addVariation(string $color, string $size, int $quantity): TestResponse
    {
        return $this->post(route('admin.retail.pos.add-to-cart'), [
            'id' => self::VARIATION_PRODUCT,
            'color' => $color,
            'choice_1' => $size,
            'quantity' => $quantity,
        ]);
    }

    private function variantPrice(string $color, string $size): TestResponse
    {
        return $this->post(route('admin.retail.pos.get-variant-price'), [
            'id' => self::VARIATION_PRODUCT,
            'color' => $color,
            'choice_1' => $size,
            'quantity' => 1,
        ]);
    }

    private function quickView(int $productId): string
    {
        $response = $this->get(route('admin.retail.pos.quick-view', ['product_id' => $productId]));
        $response->assertOk();

        return (string) $response->json('view');
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($document);
    }

    private function checkedValue(string $html, string $name): ?string
    {
        $nodes = $this->xpath($html)->query('//input[@type="radio"][@name="'.$name.'"][@checked]');
        $this->assertSame(1, $nodes->length, 'exactly one '.$name.' radio should be checked');

        return $nodes->item(0)->getAttribute('value');
    }

    private function inputValue(string $html, string $name): string
    {
        return $this->xpath($html)->query('//input[@name="'.$name.'"]')->item(0)->getAttribute('value');
    }

    private function addButton(string $html): DOMElement
    {
        return $this->xpath($html)->query('//button[contains(@class,"quick-view-modal-add-cart-button")]')->item(0);
    }

    private function addButtonLabel(string $html): string
    {
        return $this->text($html, '//*[contains(@class,"quick-view-add-cart-label")]');
    }

    private function text(string $html, string $query): string
    {
        return trim($this->xpath($html)->query($query)->item(0)->textContent);
    }

    private function isHidden(string $html, string $class): bool
    {
        $classes = explode(' ', $this->xpath($html)->query('//*[contains(@class,"'.$class.'")]')->item(0)->getAttribute('class'));

        return in_array('d-none', $classes, true);
    }
}
