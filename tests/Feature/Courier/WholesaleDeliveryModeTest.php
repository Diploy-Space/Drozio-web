<?php

namespace Tests\Feature\Courier;

use App\Enums\BusinessModule;
use App\Enums\DeliveryPartnerSetting;
use App\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class WholesaleDeliveryModeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-delivery-mode-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        if (! addon_published_status('Courier') || ! getCheckAddonPublishedStatus(moduleName: 'Wholesale')) {
            $this->markTestSkipped('Courier and Wholesale addons must be published.');
        }

        $this->rememberLanguageFiles();
        $this->stubAdminLayout();

        $this->actingAs(Admin::query()->where('admin_role_id', 1)->firstOrFail(), 'admin');
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

    public function test_the_wholesale_third_party_switch_writes_only_the_wholesale_key(): void
    {
        $retailKey = DeliveryPartnerSetting::thirdPartyDeliveryKey(businessModule: BusinessModule::RETAIL);
        $retailBefore = DB::table('business_settings')->where('type', $retailKey)->value('value');

        $this->post(route('admin.settings.business-settings.shipping-method.wholesale.update-third-party-mode'), ['status' => 1])
            ->assertRedirect(route('admin.settings.business-settings.shipping-method.wholesale'));

        $this->assertDatabaseHas('business_settings', [
            'type' => DeliveryPartnerSetting::thirdPartyDeliveryKey(businessModule: BusinessModule::WHOLESALE),
            'value' => 1,
        ]);
        $this->assertSame($retailBefore, DB::table('business_settings')->where('type', $retailKey)->value('value'));
    }

    public function test_an_unchecked_switch_turns_the_wholesale_vendor_setup_off(): void
    {
        $this->post(route('admin.settings.business-settings.shipping-method.wholesale.update-vendor-partner-setup'))
            ->assertRedirect(route('admin.settings.business-settings.shipping-method.wholesale'));

        $this->assertDatabaseHas('business_settings', [
            'type' => DeliveryPartnerSetting::vendorSetupKey(businessModule: BusinessModule::WHOLESALE),
            'value' => 0,
        ]);
    }

    public function test_the_wholesale_page_posts_its_switches_to_wholesale_routes(): void
    {
        $this->get(route('admin.settings.business-settings.shipping-method.wholesale'))
            ->assertOk()
            ->assertSee(route('admin.settings.business-settings.shipping-method.wholesale.update-third-party-mode'), false)
            ->assertDontSee(route('admin.settings.business-settings.shipping-method.update-third-party-mode'), false);
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
