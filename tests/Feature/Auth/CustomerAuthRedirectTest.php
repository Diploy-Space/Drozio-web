<?php

namespace Tests\Feature\Auth;

use App\Enums\SessionKey;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class CustomerAuthRedirectTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const DEFAULT_THEME = 'default';

    private const ASTER_THEME = 'theme_aster';

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

    public function test_a_guest_bounced_off_a_customer_page_returns_to_it_after_login(): void
    {
        config(['app.theme' => self::DEFAULT_THEME]);

        $this->get(route('wishlists.index'))->assertRedirect(route('customer.auth.login'));

        $this->assertSame(route('wishlists.index'), session(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL));
    }

    public function test_the_bounce_keeps_the_stored_url_untouched_on_a_theme_without_a_login_page(): void
    {
        config(['app.theme' => self::ASTER_THEME]);

        $browsedUrl = route('products');

        $this->withSession([SessionKey::CUSTOMER_LOGIN_REDIRECT_URL => $browsedUrl])
            ->get(route('wishlists.index'))
            ->assertRedirect(route('customer.auth.login'));

        $this->assertSame($browsedUrl, session(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL));
    }

    public function test_a_customer_only_page_never_becomes_the_return_url(): void
    {
        $customer = $this->customer();
        $browsedUrl = route('products');

        $this->actingAs($customer, 'customer')
            ->withSession([SessionKey::CUSTOMER_LOGIN_REDIRECT_URL => $browsedUrl])
            ->get(route('user-profile'));

        $this->assertSame($browsedUrl, session(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL));
        $this->assertNotContains(route('user-profile'), session(SessionKey::RECENT_USER_ROUTES_HISTORY, []));
    }

    public function test_a_storefront_page_becomes_the_return_url(): void
    {
        $this->get(route('products'));

        $this->assertSame(route('products'), session(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL));
        $this->assertContains(route('products'), session(SessionKey::RECENT_USER_ROUTES_HISTORY, []));
    }

    public function test_an_already_captured_return_url_survives_the_login_page(): void
    {
        config(['app.theme' => self::DEFAULT_THEME]);

        $intendedUrl = route('wishlists.index');

        $this->withSession([
            SessionKey::CUSTOMER_LOGIN_REDIRECT_URL => $intendedUrl,
            SessionKey::RECENT_USER_ROUTES_HISTORY => [route('products')],
        ])->get(route('customer.auth.login'));

        $this->assertSame($intendedUrl, session(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL));
    }

    public function test_logout_returns_to_the_home_of_the_surface_on_a_theme_without_a_login_page(): void
    {
        config(['app.theme' => self::ASTER_THEME]);

        $customer = $this->customer();

        $surfaces = [
            route('products') => route('home'),
        ];

        if (Route::has('wholesale.products.index')) {
            $surfaces[route('wholesale.products.details', ['slug' => 'any-product'])] = route('wholesale.products.index');
        }

        if (Route::has('auction.index')) {
            $surfaces[route('auction.product-details', ['slug' => 'any-product'])] = route('auction.index');
        }

        foreach ($surfaces as $browsedUrl => $expectedUrl) {
            $this->flushSession();

            $this->actingAs($customer, 'customer')
                ->withSession([SessionKey::CUSTOMER_LOGIN_REDIRECT_URL => $browsedUrl])
                ->from($browsedUrl)
                ->get(route('customer.auth.logout'))
                ->assertRedirect($expectedUrl);
        }
    }

    public function test_logout_falls_back_to_the_retail_home_without_any_browsing_evidence(): void
    {
        config(['app.theme' => self::ASTER_THEME]);

        $this->flushSession();

        $this->actingAs($this->customer(), 'customer')
            ->get(route('customer.auth.logout'))
            ->assertRedirect(route('home'));
    }

    public function test_logout_keeps_returning_to_the_last_url_on_the_theme_with_a_login_page(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        config(['app.theme' => self::DEFAULT_THEME]);

        $browsedUrl = route('wholesale.products.details', ['slug' => 'any-product']);

        $this->actingAs($this->customer(), 'customer')
            ->withSession([SessionKey::CUSTOMER_LOGIN_REDIRECT_URL => $browsedUrl])
            ->from($browsedUrl)
            ->get(route('customer.auth.logout'))
            ->assertRedirect($browsedUrl);
    }

    private function customer(): User
    {
        return $this->requireFixture(User::query()->where('id', '>', 0)->orderBy('id')->first(), 'customer');
    }
}
