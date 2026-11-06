<?php

namespace Tests\Feature\Wholesale;

use App\Enums\BusinessModule;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Modules\AI\app\Models\AiChatSession;
use Modules\AI\app\Services\ShoppingAssistant\AgentLoopService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class AiShoppingAssistantApiTest extends TestCase
{
    use DatabaseTransactions;
    use RequiresWholesaleAddon;

    private const RETAIL_PREFIX = 'api/v6/ai/assistant';

    private const WHOLESALE_PREFIX = 'api/v6/wholesale/ai/assistant';

    private const GUEST_ID = 'guest-wholesale-ai-v6';

    private const AGENT_RESULT = [
        'reply' => 'Here are some wholesale offers you may like.',
        'products' => [['id' => 445566, 'name' => 'Sample wholesale product', 'unit_price_formatted' => '$5.00']],
        'tools_used' => ['search_products'],
        'actions' => [],
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

    public function test_a_guest_can_start_list_show_message_and_delete_a_wholesale_session(): void
    {
        $this->stubAgent();

        $sessionId = $this->startGuestSession(prefix: self::WHOLESALE_PREFIX, guestId: self::GUEST_ID);

        $this->postJson(self::WHOLESALE_PREFIX.'/sessions/'.$sessionId.'/message', [
            'guest_id' => self::GUEST_ID,
            'message' => 'show me wholesale deals',
        ])->assertOk();

        $this->getJson(self::WHOLESALE_PREFIX.'/sessions?guest_id='.self::GUEST_ID)
            ->assertOk()
            ->assertJsonCount(1, 'content.sessions');

        $this->getJson(self::WHOLESALE_PREFIX.'/sessions/'.$sessionId.'?guest_id='.self::GUEST_ID)->assertOk();

        $this->deleteJson(self::WHOLESALE_PREFIX.'/sessions/'.$sessionId.'?guest_id='.self::GUEST_ID)->assertOk();

        $this->assertNull(AiChatSession::query()->find($sessionId));
    }

    public function test_a_bearer_customer_can_start_list_show_message_and_delete_a_wholesale_session(): void
    {
        $this->stubAgent();
        $headers = $this->bearerHeaders($this->customer());

        $sessionId = $this->withHeaders($headers)
            ->postJson(self::WHOLESALE_PREFIX.'/sessions')
            ->assertCreated()
            ->json('content.session.id');

        $this->withHeaders($headers)
            ->postJson(self::WHOLESALE_PREFIX.'/sessions/'.$sessionId.'/message', ['message' => 'show me wholesale deals'])
            ->assertOk();

        $this->withHeaders($headers)
            ->getJson(self::WHOLESALE_PREFIX.'/sessions')
            ->assertOk()
            ->assertJsonCount(1, 'content.sessions');

        $this->withHeaders($headers)->getJson(self::WHOLESALE_PREFIX.'/sessions/'.$sessionId)->assertOk();

        $this->withHeaders($headers)->deleteJson(self::WHOLESALE_PREFIX.'/sessions/'.$sessionId)->assertOk();

        $this->assertNull(AiChatSession::query()->find($sessionId));
    }

    public function test_a_retail_session_id_returns_not_found_on_wholesale_endpoints(): void
    {
        $session = $this->seedSession(guestId: self::GUEST_ID, businessModule: BusinessModule::RETAIL, withMessage: true);
        $query = '?guest_id='.self::GUEST_ID;

        $this->getJson(self::WHOLESALE_PREFIX.'/sessions/'.$session->id.$query)->assertNotFound();
        $this->postJson(self::WHOLESALE_PREFIX.'/sessions/'.$session->id.'/message', ['guest_id' => self::GUEST_ID, 'message' => 'hi'])->assertNotFound();
        $this->deleteJson(self::WHOLESALE_PREFIX.'/sessions/'.$session->id.$query)->assertNotFound();

        $listed = $this->getJson(self::WHOLESALE_PREFIX.'/sessions'.$query)->assertOk()->json('content.sessions');
        $this->assertSame([], $listed);
        $this->assertNotNull(AiChatSession::query()->find($session->id));
    }

    public function test_a_wholesale_session_id_returns_not_found_on_retail_endpoints(): void
    {
        $session = $this->seedSession(guestId: self::GUEST_ID, businessModule: BusinessModule::WHOLESALE, withMessage: true);
        $query = '?guest_id='.self::GUEST_ID;

        $this->getJson(self::RETAIL_PREFIX.'/sessions/'.$session->id.$query)->assertNotFound();
        $this->postJson(self::RETAIL_PREFIX.'/sessions/'.$session->id.'/message', ['guest_id' => self::GUEST_ID, 'message' => 'hi'])->assertNotFound();
        $this->deleteJson(self::RETAIL_PREFIX.'/sessions/'.$session->id.$query)->assertNotFound();

        $listed = $this->getJson(self::RETAIL_PREFIX.'/sessions'.$query)->assertOk()->json('content.sessions');
        $this->assertSame([], $listed);
        $this->assertNotNull(AiChatSession::query()->find($session->id));
    }

    public function test_a_wholesale_session_is_tagged_with_the_wholesale_business_module(): void
    {
        $sessionId = $this->startGuestSession(prefix: self::WHOLESALE_PREFIX, guestId: self::GUEST_ID);

        $this->assertSame(BusinessModule::WHOLESALE, AiChatSession::query()->findOrFail($sessionId)->business_module);
    }

    #[DataProvider('parityScenarios')]
    public function test_response_envelope_shape_matches_retail_v6_for_the_same_scenario(string $scenario): void
    {
        $this->stubAgent();

        $retailResponse = $this->runScenario(scenario: $scenario, prefix: self::RETAIL_PREFIX, guestId: self::GUEST_ID.'-retail-'.$scenario);
        $wholesaleResponse = $this->runScenario(scenario: $scenario, prefix: self::WHOLESALE_PREFIX, guestId: self::GUEST_ID.'-wholesale-'.$scenario);

        $this->assertSame($retailResponse->getStatusCode(), $wholesaleResponse->getStatusCode());
        $this->assertSame($this->shape($retailResponse->json()), $this->shape($wholesaleResponse->json()));
    }

    public static function parityScenarios(): array
    {
        return [
            'start-session' => ['start-session'],
            'list-sessions' => ['list-sessions'],
            'show-session' => ['show-session'],
            'message' => ['message'],
            'delete-session' => ['delete-session'],
        ];
    }

    private function runScenario(string $scenario, string $prefix, string $guestId): TestResponse
    {
        $sessionId = $this->startGuestSession(prefix: $prefix, guestId: $guestId);
        $query = '?guest_id='.$guestId;

        return match ($scenario) {
            'start-session' => $this->postJson($prefix.'/sessions', ['guest_id' => $guestId.'-fresh']),
            'list-sessions' => $this->getJson($prefix.'/sessions'.$query),
            'show-session' => $this->getJson($prefix.'/sessions/'.$sessionId.$query),
            'message' => $this->postJson($prefix.'/sessions/'.$sessionId.'/message', ['guest_id' => $guestId, 'message' => 'show me deals']),
            'delete-session' => $this->deleteJson($prefix.'/sessions/'.$sessionId.$query),
        };
    }

    private function shape(mixed $value): mixed
    {
        if (! is_array($value)) {
            return gettype($value);
        }

        if (array_is_list($value)) {
            return empty($value) ? [] : [$this->shape($value[0])];
        }

        ksort($value);

        return array_map(fn ($item) => $this->shape($item), $value);
    }

    private function startGuestSession(string $prefix, string $guestId): int
    {
        return (int) $this->postJson($prefix.'/sessions', ['guest_id' => $guestId])
            ->assertCreated()
            ->json('content.session.id');
    }

    private function bearerHeaders(User $customer): array
    {
        Passport::actingAs($customer, [], 'api');

        return ['Authorization' => 'Bearer wholesale-ai-test-token'];
    }

    private function customer(): User
    {
        return User::create([
            'name' => 'Wholesale AI Assistant Customer',
            'email' => 'wholesale-ai-assistant-'.uniqid().'@example.test',
            'phone' => '1'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
        ]);
    }

    private function stubAgent(): void
    {
        $this->mock(AgentLoopService::class)
            ->shouldReceive('run')
            ->andReturn(self::AGENT_RESULT);
    }

    private function seedSession(?int $customerId = null, ?string $guestId = null, string $businessModule = BusinessModule::RETAIL, bool $withMessage = false): AiChatSession
    {
        $session = AiChatSession::query()->create([
            'customer_id' => $customerId,
            'guest_id' => $guestId,
            'business_module' => $businessModule,
            'locale' => 'en',
            'last_activity_at' => now(),
        ]);

        if ($withMessage) {
            $session->messages()->create(['role' => 'user', 'content' => 'hi']);
        }

        return $session;
    }
}
