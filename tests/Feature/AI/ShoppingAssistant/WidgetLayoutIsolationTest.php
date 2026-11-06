<?php

namespace Tests\Feature\AI\ShoppingAssistant;

use App\Models\BusinessSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Modules\AI\app\Models\AISetting;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionFunction;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class WidgetLayoutIsolationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithWholesaleAddon;

    public function test_default_theme_layout_renders_the_wholesale_partial_directly(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $contents = file_get_contents(module_path('Wholesale', 'resources/views/themes/default/layouts/app.blade.php'));

        $this->assertStringContainsString("@include('wholesale::partials.shopping-assistant._sidebar')", (string) $contents);
        $this->assertStringNotContainsString("@include('ai::partials.shopping-assistant._sidebar')", (string) $contents);
    }

    public function test_theme_aster_layout_renders_the_wholesale_partial_directly(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $contents = file_get_contents(module_path('Wholesale', 'resources/views/themes/theme_aster/layouts/app.blade.php'));

        $this->assertStringContainsString("@include('wholesale::partials.shopping-assistant._sidebar')", (string) $contents);
        $this->assertStringNotContainsString("@include('ai::partials.shopping-assistant._sidebar')", (string) $contents);
    }

    public function test_core_retail_layouts_resolve_the_widget_through_the_same_shared_helper(): void
    {
        $default = file_get_contents(base_path('resources/themes/default/layouts/front-end/app.blade.php'));
        $aster = file_get_contents(base_path('resources/themes/theme_aster/theme-views/layouts/app.blade.php'));

        $this->assertStringContainsString('@include(storefrontShoppingAssistantView())', (string) $default);
        $this->assertStringContainsString('@include(storefrontShoppingAssistantView())', (string) $aster);
    }

    public function test_helper_resolves_wholesale_partial_on_a_wholesale_named_route_even_under_the_customer_uri(): void
    {
        Route::get('_test/customer/orders/wholesale-probe', fn () => storefrontShoppingAssistantView())
            ->name('wholesale.orders.probe');

        $view = $this->get('_test/customer/orders/wholesale-probe')->getContent();

        $this->assertSame('wholesale::partials.shopping-assistant._sidebar', $view);
    }

    public function test_helper_resolves_retail_partial_on_a_non_wholesale_route(): void
    {
        Route::get('_test/customer/orders/retail-probe', fn () => storefrontShoppingAssistantView())
            ->name('orders.probe');

        $view = $this->get('_test/customer/orders/retail-probe')->getContent();

        $this->assertSame('ai::partials.shopping-assistant._sidebar', $view);
    }

    #[RunInSeparateProcess]
    public function test_each_module_sidebar_partial_renders_its_own_cart_mode_and_routes(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        AISetting::query()->create([
            'ai_name' => 'openai',
            'api_key' => 'sk-isolation-test-key',
            'status' => 1,
        ]);
        $this->setBusinessSetting('ai_shopping_assistant_status', 1);

        $retailHtml = view('ai::partials.shopping-assistant._sidebar')->render();
        $wholesaleHtml = view('wholesale::partials.shopping-assistant._sidebar')->render();

        $this->assertStringContainsString('data-cart-mode="retail"', $retailHtml);
        $this->assertStringNotContainsString('data-cart-mode="wholesale"', $retailHtml);

        $this->assertStringContainsString('data-cart-mode="wholesale"', $wholesaleHtml);
        $this->assertStringNotContainsString('data-cart-mode="retail"', $wholesaleHtml);

        $this->assertStringContainsString(url('ai/assistant/sessions'), $retailHtml);
        $this->assertStringContainsString(url('wholesale/ai/assistant/sessions'), $wholesaleHtml);
        $this->assertStringNotContainsString(url('wholesale/ai/assistant/sessions'), $retailHtml);
    }

    private function setBusinessSetting(string $type, mixed $value): void
    {
        BusinessSetting::query()->updateOrCreate(['type' => $type], ['value' => $value]);

        getWebConfig(name: 'company_name');

        $collection = (new ReflectionFunction('getWebConfig'))->getStaticVariables()['businessSettings'];
        $existing = $collection?->firstWhere('type', $type);
        if ($existing) {
            $existing->value = $value;
        } else {
            $collection?->push(BusinessSetting::query()->where('type', $type)->first());
        }
    }
}
