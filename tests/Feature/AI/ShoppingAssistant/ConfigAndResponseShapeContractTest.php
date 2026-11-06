<?php

namespace Tests\Feature\AI\ShoppingAssistant;

use App\Models\BusinessSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Modules\AI\app\Services\ShoppingAssistant\AgentLoopService;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionFunction;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class ConfigAndResponseShapeContractTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithWholesaleAddon;

    private const AGENT_RESULT = [
        'reply' => 'Here are some items you may like.',
        'products' => [],
        'tools_used' => [],
        'actions' => [],
    ];

    private array $languageFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

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

    #[RunInSeparateProcess]
    public function test_v6_config_serves_retail_and_wholesale_assistant_flags_from_the_same_shared_toggle(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->setBusinessSetting('ai_shopping_assistant_status', 1);

        $body = $this->getJson('api/v6/config')->assertOk()->json();

        $this->assertTrue((bool) $body['content']['ai']['shopping_assistant']);
        $this->assertTrue((bool) $body['content']['wholesale']['ai']['shopping_assistant']);
    }

    #[RunInSeparateProcess]
    public function test_v6_config_flags_flip_together_the_other_direction(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->setBusinessSetting('ai_shopping_assistant_status', 0);

        $body = $this->getJson('api/v6/config')->assertOk()->json();

        $this->assertFalse((bool) $body['content']['ai']['shopping_assistant']);
        $this->assertFalse((bool) $body['content']['wholesale']['ai']['shopping_assistant']);
    }

    public function test_wholesale_web_and_v6_responses_match_retail_shape_for_a_normal_turn(): void
    {
        $this->mock(AgentLoopService::class)->shouldReceive('run')->andReturn(self::AGENT_RESULT);

        $retailWeb = $this->runWebTurn(prefix: 'ai/assistant', guestId: 'guest-shape-retail-web');
        $wholesaleWeb = $this->runWebTurn(prefix: 'wholesale/ai/assistant', guestId: 'guest-shape-wholesale-web');
        $this->assertSame($this->shape($retailWeb->json()), $this->shape($wholesaleWeb->json()));

        $retailV6 = $this->runV6Turn(prefix: 'api/v6/ai/assistant', guestId: 'guest-shape-retail-v6');
        $wholesaleV6 = $this->runV6Turn(prefix: 'api/v6/wholesale/ai/assistant', guestId: 'guest-shape-wholesale-v6');
        $this->assertSame($this->shape($retailV6->json()), $this->shape($wholesaleV6->json()));
    }

    private function runWebTurn(string $prefix, string $guestId): TestResponse
    {
        $sessionId = $this->postJson($prefix.'/sessions', ['guest_id' => $guestId])->assertCreated()->json('session.id');

        return $this->postJson($prefix.'/sessions/'.$sessionId.'/message', ['guest_id' => $guestId, 'message' => 'show me deals']);
    }

    private function runV6Turn(string $prefix, string $guestId): TestResponse
    {
        $sessionId = $this->postJson($prefix.'/sessions', ['guest_id' => $guestId])->assertCreated()->json('content.session.id');

        return $this->postJson($prefix.'/sessions/'.$sessionId.'/message', ['guest_id' => $guestId, 'message' => 'show me deals']);
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
