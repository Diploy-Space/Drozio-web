<?php

namespace Tests\Feature\Wholesale;

use App\Models\Admin;
use App\Models\BusinessSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\View;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class AiShoppingAssistantSettingTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const SHARED_KEY = 'ai_shopping_assistant_status';

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-ai-shopping-assistant-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        config(['app.mode' => 'live']);

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $path = self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php';

        if (is_file($path)) {
            unlink($path);
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_enabling_the_shared_toggle_from_customer_settings_turns_the_wholesale_flag_on_too(): void
    {
        $this->storeSetting(value: 0);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.settings.business-settings.customer-settings'), [
                self::SHARED_KEY => 1,
            ])
            ->assertRedirect();

        $this->assertSame('1', (string) $this->storedSetting());
    }

    public function test_disabling_the_shared_toggle_turns_the_wholesale_flag_off_too(): void
    {
        $this->storeSetting(value: 1);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.settings.business-settings.customer-settings'), [])
            ->assertRedirect();

        $this->assertSame('0', (string) $this->storedSetting());
    }

    public function test_the_wholesale_config_page_no_longer_renders_its_own_toggle(): void
    {
        $this->storeSetting(value: 1);
        $this->stubAdminLayout();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.settings.wholesale.wholesale-config'))
            ->assertOk()
            ->assertDontSee('name="wholesale_ai_shopping_assistant_status"', false);
    }

    private function storeSetting(int $value): void
    {
        BusinessSetting::query()->updateOrInsert(['type' => self::SHARED_KEY], ['value' => $value]);

        clearWebConfigCacheKeys();
    }

    private function storedSetting(): mixed
    {
        return BusinessSetting::query()->where('type', self::SHARED_KEY)->value('value');
    }

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }

    private function stubAdminLayout(): void
    {
        $path = self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, "@yield('content')@stack('script')");

        View::getFinder()->prependLocation(self::STUB_VIEW_ROOT);
        View::getFinder()->flush();
    }
}
