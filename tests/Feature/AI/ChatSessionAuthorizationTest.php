<?php

namespace Tests\Feature\AI;

use App\Enums\BusinessModule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\AI\app\Models\AiChatSession;
use Modules\AI\app\Services\ShoppingAssistant\AgentLoopService;
use Modules\AI\app\Services\ShoppingAssistant\AiChatSessionService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChatSessionAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private const GUEST_ID = 'guest-isolation';

    private const SURFACE_PREFIXES = [
        'web' => 'ai/assistant',
        'v6' => 'api/v6/ai/assistant',
        'v1' => 'api/v1/ai/assistant',
    ];

    private AiChatSessionService $sessionService;

    private array $languageFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->sessionService = app(AiChatSessionService::class);

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

    public static function surfaces(): array
    {
        return [
            'web' => ['web'],
            'v6' => ['v6'],
            'v1' => ['v1'],
        ];
    }

    public function test_find_denies_when_no_identity_is_present(): void
    {
        $session = $this->seedSession(guestId: 'guest-a');

        $this->assertNull($this->findOwned(sessionId: $session->id, customerId: null, guestId: null));
    }

    public function test_find_denies_cross_owner_access(): void
    {
        $session = $this->seedSession(customerId: 1);

        $this->assertNull($this->findOwned(sessionId: $session->id, customerId: 2, guestId: null));
        $this->assertNull($this->findOwned(sessionId: $session->id, customerId: null, guestId: 'someone-else'));
    }

    public function test_find_allows_matching_owner(): void
    {
        $customerSession = $this->seedSession(customerId: 7);
        $guestSession = $this->seedSession(guestId: 'guest-x');

        $this->assertNotNull($this->findOwned(sessionId: $customerSession->id, customerId: 7, guestId: null));
        $this->assertNotNull($this->findOwned(sessionId: $guestSession->id, customerId: null, guestId: 'guest-x'));
    }

    public function test_find_denies_a_session_of_another_business_module(): void
    {
        $session = $this->seedSession(customerId: 7, businessModule: BusinessModule::WHOLESALE);

        $this->assertNull($this->findOwned(sessionId: $session->id, customerId: 7, guestId: null));
    }

    public function test_list_is_empty_without_identity(): void
    {
        $this->seedSession(guestId: 'guest-a', withMessage: true);
        $this->seedSession(customerId: 1, withMessage: true);

        $this->assertCount(0, $this->listOwned(customerId: null, guestId: null));
    }

    public function test_list_scopes_to_owner(): void
    {
        $this->seedSession(customerId: 5, withMessage: true);
        $this->seedSession(customerId: 6, withMessage: true);

        $list = $this->listOwned(customerId: 5, guestId: null);

        $this->assertCount(1, $list);
        $this->assertSame(5, (int) $list->first()->customer_id);
    }

    public function test_list_skips_sessions_without_messages_and_of_another_business_module(): void
    {
        $this->seedSession(customerId: 5);
        $this->seedSession(customerId: 5, businessModule: BusinessModule::WHOLESALE, withMessage: true);
        $retail = $this->seedSession(customerId: 5, withMessage: true);

        $list = $this->listOwned(customerId: 5, guestId: null);

        $this->assertSame([$retail->id], $list->pluck('id')->all());
    }

    #[DataProvider('surfaces')]
    public function test_a_session_started_on_a_retail_endpoint_is_retail(string $surface): void
    {
        $response = $this->postJson(self::SURFACE_PREFIXES[$surface].'/sessions', ['guest_id' => self::GUEST_ID])->assertCreated();

        $body = $response->json();
        $sessionId = $body['session']['id'] ?? $body['content']['session']['id'];

        $this->assertSame(BusinessModule::RETAIL, AiChatSession::query()->findOrFail($sessionId)->business_module);
    }

    #[DataProvider('surfaces')]
    public function test_a_wholesale_session_is_invisible_to_retail_endpoints(string $surface): void
    {
        $this->mock(AgentLoopService::class)->shouldNotReceive('run');
        $session = $this->seedSession(guestId: self::GUEST_ID, businessModule: BusinessModule::WHOLESALE, withMessage: true);
        $prefix = self::SURFACE_PREFIXES[$surface];
        $query = '?guest_id='.self::GUEST_ID;

        $this->getJson($prefix.'/sessions/'.$session->id.$query)->assertNotFound();
        $this->postJson($prefix.'/sessions/'.$session->id.'/message', ['guest_id' => self::GUEST_ID, 'message' => 'show me phones'])->assertNotFound();
        $this->deleteJson($prefix.'/sessions/'.$session->id.$query)->assertNotFound();

        $listed = $this->getJson($prefix.'/sessions'.$query)->assertOk()->json();

        $this->assertSame([], $listed['sessions'] ?? $listed['content']['sessions']);
        $this->assertNotNull(AiChatSession::query()->find($session->id));
    }

    #[DataProvider('surfaces')]
    public function test_a_retail_session_cannot_be_deleted_by_another_guest(string $surface): void
    {
        $session = $this->seedSession(guestId: self::GUEST_ID, withMessage: true);

        $this->deleteJson(self::SURFACE_PREFIXES[$surface].'/sessions/'.$session->id.'?guest_id=someone-else')->assertNotFound();

        $this->assertNotNull(AiChatSession::query()->find($session->id));
    }

    private function seedSession(
        ?int $customerId = null,
        ?string $guestId = null,
        string $businessModule = BusinessModule::RETAIL,
        bool $withMessage = false,
    ): AiChatSession {
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

    private function findOwned(int $sessionId, ?int $customerId, ?string $guestId): ?AiChatSession
    {
        return $this->sessionService->findBy(params: [
            'id' => $sessionId,
            AiChatSessionService::FILTER_OWNER => [
                AiChatSessionService::OWNER_CUSTOMER_ID => $customerId,
                AiChatSessionService::OWNER_GUEST_ID => $guestId,
            ],
            AiChatSessionService::FILTER_BUSINESS_MODULE => BusinessModule::RETAIL,
        ]);
    }

    private function listOwned(?int $customerId, ?string $guestId): Collection
    {
        return $this->sessionService->getList(dataLimit: 'all', withCount: ['messages'], filters: [
            AiChatSessionService::FILTER_OWNER => [
                AiChatSessionService::OWNER_CUSTOMER_ID => $customerId,
                AiChatSessionService::OWNER_GUEST_ID => $guestId,
            ],
            AiChatSessionService::FILTER_BUSINESS_MODULE => BusinessModule::RETAIL,
            AiChatSessionService::FILTER_HAS_MESSAGES => true,
        ]);
    }
}
