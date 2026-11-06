<?php

namespace Tests\Feature\AI;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Modules\AI\app\Services\ShoppingAssistant\AgentLoopService;
use Modules\AI\app\Services\ShoppingAssistant\AssistantUsageGuardService;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ShoppingAssistantResponseCharacterizationTest extends TestCase
{
    use DatabaseTransactions;

    private const GUEST_ID = 'guest-characterization';

    private const FROZEN_NOW = '2026-09-21 10:00:00';

    private const SURFACE_PREFIXES = [
        'web' => 'ai/assistant',
        'v6' => 'api/v6/ai/assistant',
        'v1' => 'api/v1/ai/assistant',
    ];

    private const AGENT_RESULT = [
        'reply' => 'Here are some phones you may like.',
        'products' => [['id' => 987654, 'name' => 'Sample phone', 'unit_price_formatted' => '$10.00']],
        'tools_used' => ['search_products'],
        'actions' => [],
    ];

    private array $languageFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(self::FROZEN_NOW);
        Cache::flush();
        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();
        Carbon::setTestNow();

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

    #[DataProvider('surfaces')]
    public function test_start_session_response_is_unchanged(string $surface): void
    {
        $this->assertMatchesSnapshot(surface: $surface, scenario: 'start-session', response: $this->startAssistantSession(surface: $surface));
    }

    #[DataProvider('surfaces')]
    public function test_normal_turn_response_is_unchanged(string $surface): void
    {
        $this->stubAgent(result: self::AGENT_RESULT);
        $sessionId = $this->startSessionId($surface);

        $this->assertMatchesSnapshot(surface: $surface, scenario: 'normal-turn', response: $this->sendMessage(surface: $surface, sessionId: $sessionId, message: 'show me phones'));
    }

    #[DataProvider('surfaces')]
    public function test_guard_blocked_turn_response_is_unchanged(string $surface): void
    {
        $this->stubAgent(result: self::AGENT_RESULT);
        $sessionId = $this->startSessionId($surface);

        $this->assertMatchesSnapshot(surface: $surface, scenario: 'guard-blocked-turn', response: $this->sendMessage(surface: $surface, sessionId: $sessionId, message: 'what is the weather today'));
    }

    #[DataProvider('surfaces')]
    public function test_usage_limited_turn_response_is_unchanged(string $surface): void
    {
        $this->stubAgent(result: self::AGENT_RESULT);
        $this->mock(AssistantUsageGuardService::class)
            ->shouldReceive('check')
            ->andReturn(['reply' => translate('You have reached your usage limit for the shopping assistant'), 'intent' => 'blocked']);
        $sessionId = $this->startSessionId($surface);

        $this->assertMatchesSnapshot(surface: $surface, scenario: 'usage-limited-turn', response: $this->sendMessage(surface: $surface, sessionId: $sessionId, message: 'show me phones'));
    }

    #[DataProvider('surfaces')]
    public function test_provider_failure_turn_response_is_unchanged(string $surface): void
    {
        $this->mock(AgentLoopService::class)
            ->shouldReceive('run')
            ->andThrow(new RuntimeException('provider down'));
        $sessionId = $this->startSessionId($surface);

        $this->assertMatchesSnapshot(surface: $surface, scenario: 'provider-failure-turn', response: $this->sendMessage(surface: $surface, sessionId: $sessionId, message: 'show me phones'));
    }

    #[DataProvider('surfaces')]
    public function test_session_list_show_and_delete_responses_are_unchanged(string $surface): void
    {
        $this->stubAgent(result: self::AGENT_RESULT);
        $sessionId = $this->startSessionId($surface);
        $this->sendMessage(surface: $surface, sessionId: $sessionId, message: 'show me phones');

        $prefix = self::SURFACE_PREFIXES[$surface];
        $query = '?guest_id='.self::GUEST_ID;

        $this->assertMatchesSnapshot(surface: $surface, scenario: 'list-sessions', response: $this->getJson($prefix.'/sessions'.$query));
        $this->assertMatchesSnapshot(surface: $surface, scenario: 'show-session', response: $this->getJson($prefix.'/sessions/'.$sessionId.$query));
        $this->assertMatchesSnapshot(surface: $surface, scenario: 'delete-session', response: $this->deleteJson($prefix.'/sessions/'.$sessionId.$query));
        $this->assertMatchesSnapshot(surface: $surface, scenario: 'show-missing-session', response: $this->getJson($prefix.'/sessions/'.$sessionId.$query));
        $this->assertMatchesSnapshot(surface: $surface, scenario: 'message-missing-session', response: $this->sendMessage(surface: $surface, sessionId: $sessionId, message: 'show me phones'));
    }

    public function test_v1_start_session_without_identity_keeps_its_422_body(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer characterization-without-user')
            ->postJson(self::SURFACE_PREFIXES['v1'].'/sessions');

        $this->assertMatchesSnapshot(surface: 'v1', scenario: 'start-session-without-identity', response: $response);
    }

    private function stubAgent(array $result): void
    {
        $this->mock(AgentLoopService::class)
            ->shouldReceive('run')
            ->andReturn($result);
    }

    private function startAssistantSession(string $surface): TestResponse
    {
        return $this->postJson(self::SURFACE_PREFIXES[$surface].'/sessions', ['guest_id' => self::GUEST_ID]);
    }

    private function startSessionId(string $surface): int
    {
        $body = $this->startAssistantSession(surface: $surface)->json();

        return (int) ($body['session']['id'] ?? $body['data']['session']['id'] ?? $body['content']['session']['id']);
    }

    private function sendMessage(string $surface, int $sessionId, string $message): TestResponse
    {
        return $this->postJson(
            self::SURFACE_PREFIXES[$surface].'/sessions/'.$sessionId.'/message',
            ['guest_id' => self::GUEST_ID, 'message' => $message],
        );
    }

    private function assertMatchesSnapshot(string $surface, string $scenario, TestResponse $response): void
    {
        $actual = $response->getStatusCode()."\n".$this->normalizeIdentifiers((string) $response->getContent());
        $path = __DIR__.'/fixtures/responses/'.$surface.'-'.$scenario.'.txt';

        if (getenv('AI_CAPTURE_SNAPSHOTS') === '1') {
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            file_put_contents($path, $actual);
        }

        $this->assertFileExists($path);
        $this->assertSame(file_get_contents($path), $actual, $surface.' '.$scenario.' response changed');
    }

    private function normalizeIdentifiers(string $content): string
    {
        return (string) preg_replace('/"(id|session_id)":\d+/', '"$1":0', $content);
    }

    private function rememberLanguageFiles(): void
    {
        foreach (glob(base_path('resources/lang/*/{new-messages,lang,messages}.php'), GLOB_BRACE) ?: [] as $path) {
            $this->languageFiles[$path] = (string) file_get_contents($path);
        }
    }

    private function restoreLanguageFiles(): void
    {
        foreach ($this->languageFiles as $path => $contents) {
            if (file_get_contents($path) !== $contents) {
                file_put_contents($path, $contents);
            }
        }
    }
}
