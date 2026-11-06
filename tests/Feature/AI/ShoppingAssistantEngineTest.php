<?php

namespace Tests\Feature\AI;

use App\Enums\BusinessModule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\AI\app\Events\ShoppingAssistantTurnRequestedEvent;
use Modules\AI\app\Models\AiChatSession;
use Modules\AI\app\Services\ShoppingAssistant\AgentLoopService;
use Modules\AI\app\Services\ShoppingAssistant\ShownProductStore;
use Modules\AI\app\Storefronts\RetailShoppingStorefront;
use Tests\TestCase;

class ShoppingAssistantEngineTest extends TestCase
{
    use DatabaseTransactions;

    private const RETAIL_TOOL_NAMES = [
        'search_products',
        'get_similar_products',
        'add_to_cart',
        'update_cart_quantity',
        'go_to_checkout',
        'recall_shown_products',
        'search_offers',
        'list_vendors',
        'list_brands',
    ];

    private array $languageFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        foreach (glob(base_path('resources/lang/*/{new-messages,lang,messages}.php'), GLOB_BRACE) ?: [] as $path) {
            $this->languageFiles[$path] = (string) file_get_contents($path);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->languageFiles as $path => $contents) {
            if (file_get_contents($path) !== $contents) {
                file_put_contents($path, $contents);
            }
        }

        parent::tearDown();
    }

    public function test_retail_system_prompt_is_byte_identical_to_the_captured_copy(): void
    {
        $captured = file_get_contents(__DIR__.'/fixtures/retail-shopping-system-prompt.txt');

        $this->assertSame($captured, app(RetailShoppingStorefront::class)->systemPrompt());
    }

    public function test_retail_storefront_lists_its_nine_tools_in_order(): void
    {
        $storefront = app(RetailShoppingStorefront::class);

        $this->assertSame(BusinessModule::RETAIL, $storefront->businessModule());
        $this->assertSame(self::RETAIL_TOOL_NAMES, array_map(fn ($tool) => $tool->name(), $storefront->tools()));
    }

    public function test_response_cache_is_keyed_by_business_module(): void
    {
        $fingerprint = md5(app()->getLocale().'||show me phones|');
        Cache::put('ai_response_global:'.BusinessModule::RETAIL.':'.$fingerprint, ['reply' => 'retail'], 60);
        Cache::put('ai_response_global:'.BusinessModule::WHOLESALE.':'.$fingerprint, ['reply' => 'wholesale'], 60);

        $agentLoop = app(AgentLoopService::class);

        foreach ([BusinessModule::RETAIL, BusinessModule::WHOLESALE] as $module) {
            $result = $agentLoop->run(history: [], newMessage: 'show me phones', systemPrompt: '', tools: [], businessModule: $module);

            $this->assertSame($module, $result['reply']);
        }
    }

    public function test_recall_keeps_only_active_retail_products(): void
    {
        $retailId = $this->seedActiveProduct(businessModule: BusinessModule::RETAIL);
        $wholesaleId = $this->seedActiveProduct(businessModule: BusinessModule::WHOLESALE);
        $inactiveId = $this->seedActiveProduct(businessModule: BusinessModule::RETAIL, status: 0);

        $activeIds = app(RetailShoppingStorefront::class)->getActiveProductIds(productIds: [$retailId, $wholesaleId, $inactiveId]);

        $this->assertSame([$retailId], $activeIds);
    }

    public function test_turn_hands_the_storefront_tools_module_and_prompt_to_the_agent(): void
    {
        $storefront = app(RetailShoppingStorefront::class);
        $retailId = $this->seedActiveProduct(businessModule: BusinessModule::RETAIL);
        $wholesaleId = $this->seedActiveProduct(businessModule: BusinessModule::WHOLESALE);
        $session = AiChatSession::query()->create(['guest_id' => 'guest-engine', 'locale' => 'en', 'last_activity_at' => now()]);
        $shownProducts = array_map(fn ($id) => ['id' => $id, 'name' => 'Product '.$id], [$retailId, $wholesaleId]);
        $session->messages()->create(['role' => 'assistant', 'content' => 'Here you go', 'meta' => ['products' => $shownProducts]]);

        $captured = [];
        $this->mock(AgentLoopService::class)
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function (array $history, string $newMessage, string $systemPrompt, array $tools, string $businessModule) use (&$captured) {
                $captured = compact('systemPrompt', 'tools', 'businessModule');

                return ['reply' => 'Sure [id: 12]', 'products' => [], 'tools_used' => [], 'actions' => []];
            });

        $turn = new ShoppingAssistantTurnRequestedEvent(sessionId: $session->id, content: 'show me phones', imageUrl: null, storefront: $storefront);
        event($turn);

        $this->assertSame(['reply' => 'Sure', 'intent' => 'smalltalk', 'products' => [], 'actions' => []], $turn->result);
        $this->assertSame(BusinessModule::RETAIL, $captured['businessModule']);
        $this->assertSame(self::RETAIL_TOOL_NAMES, array_map(fn ($tool) => $tool->name(), $captured['tools']));
        $this->assertStringStartsWith($storefront->systemPrompt()."\n\nINTERNAL CONTEXT", $captured['systemPrompt']);
        $this->assertSame([$retailId], array_column(app(ShownProductStore::class)->all(), 'id'));
        $this->assertSame('show me phones', $session->refresh()->title);
    }

    public function test_prune_command_removes_stale_guest_and_empty_sessions_only(): void
    {
        $staleGuest = AiChatSession::query()->create(['guest_id' => 'stale', 'locale' => 'en', 'last_activity_at' => now()->subDays(40)]);
        $staleGuest->messages()->create(['role' => 'user', 'content' => 'hi']);
        $staleCustomer = AiChatSession::query()->create(['customer_id' => 1, 'locale' => 'en', 'last_activity_at' => now()->subDays(40)]);
        $staleCustomer->messages()->create(['role' => 'user', 'content' => 'hi']);
        $emptyOld = AiChatSession::query()->create(['customer_id' => 1, 'locale' => 'en']);
        $emptyOld->forceFill(['created_at' => now()->subDays(2)])->save();
        $activeGuest = AiChatSession::query()->create(['guest_id' => 'active', 'locale' => 'en', 'last_activity_at' => now()]);
        $activeGuest->messages()->create(['role' => 'user', 'content' => 'hi']);

        $this->artisan('ai:prune-chat-sessions')->assertSuccessful();

        $this->assertNull(AiChatSession::query()->find($staleGuest->id));
        $this->assertNull(AiChatSession::query()->find($emptyOld->id));
        $this->assertNotNull(AiChatSession::query()->find($staleCustomer->id));
        $this->assertNotNull(AiChatSession::query()->find($activeGuest->id));
    }

    private function seedActiveProduct(string $businessModule, int $status = 1): int
    {
        return DB::table('products')->insertGetId([
            'business_module' => $businessModule,
            'added_by' => 'admin',
            'user_id' => 1,
            'name' => 'Assistant engine product',
            'slug' => 'assistant-engine-product-'.uniqid(),
            'product_type' => 'physical',
            'status' => $status,
            'request_status' => 1,
            'brand_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
