<?php

namespace Tests\Feature\Cart;

use App\Enums\BusinessModule;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Modules\Wholesale\app\Enums\CheckoutSessionKey;
use Modules\Wholesale\app\Http\Requests\Customer\Cart\OrderNoteRequest;
use Modules\Wholesale\app\Services\CartService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleCartOrderNoteTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-cart-order-note-views';

    private ?string $stubbedViewPath = null;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        if ($this->stubbedViewPath !== null && is_file($this->stubbedViewPath)) {
            unlink($this->stubbedViewPath);
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_a_guest_saves_the_order_note_into_the_wholesale_session_key(): void
    {
        $this->withSession(['guest_id' => $this->guestId()])
            ->postJson(route('wholesale.cart.order-note'), ['order_note' => '  Deliver to the back gate  '])
            ->assertOk()
            ->assertJsonPath('order_note', 'Deliver to the back gate')
            ->assertSessionHas(CheckoutSessionKey::ORDER_NOTE, 'Deliver to the back gate')
            ->assertSessionMissing('order_note');
    }

    public function test_an_empty_note_clears_the_saved_one(): void
    {
        $this->withSession(['guest_id' => $this->guestId(), CheckoutSessionKey::ORDER_NOTE => 'Old note'])
            ->postJson(route('wholesale.cart.order-note'), ['order_note' => ''])
            ->assertOk()
            ->assertJsonPath('order_note', null)
            ->assertSessionMissing(CheckoutSessionKey::ORDER_NOTE);
    }

    public function test_a_note_longer_than_the_limit_is_refused(): void
    {
        $this->withSession(['guest_id' => $this->guestId(), CheckoutSessionKey::ORDER_NOTE => 'Old note'])
            ->postJson(route('wholesale.cart.order-note'), ['order_note' => str_repeat('a', OrderNoteRequest::MAX_LENGTH + 1)])
            ->assertUnprocessable()
            ->assertSessionHas(CheckoutSessionKey::ORDER_NOTE, 'Old note');
    }

    public function test_the_cart_page_carries_the_saved_note_and_the_tax_model_flag(): void
    {
        $this->stubCartView();
        $isTaxIncluded = (bool) CartService::getTaxSystemType(businessModule: BusinessModule::WHOLESALE)['is_included'];

        $this->withSession(['guest_id' => $this->guestId(), CheckoutSessionKey::ORDER_NOTE => 'Ring the bell'])
            ->get(route('wholesale.shop-cart'))
            ->assertOk()
            ->assertViewHas('orderNote', 'Ring the bell')
            ->assertViewHas('orderNoteMaxLength', OrderNoteRequest::MAX_LENGTH)
            ->assertViewHas('isTaxIncluded', $isTaxIncluded)
            ->assertViewHas('cartUrls', fn (array $cartUrls): bool => $cartUrls['order_note'] === route('wholesale.cart.order-note'));
    }

    private function stubCartView(): void
    {
        $path = self::STUB_VIEW_ROOT.'/themes/'.theme_root_path().'/orders/cart.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, '{{ $orderNote }}');
        $this->stubbedViewPath = $path;

        View::prependNamespace('wholesale', self::STUB_VIEW_ROOT);
    }
}
