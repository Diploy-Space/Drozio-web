<?php

namespace Tests\Feature\AI;

use Illuminate\Support\Facades\Cache;
use Modules\AI\app\Services\ShoppingAssistant\AssistantUsageGuardService;
use Tests\TestCase;

class AssistantUsageGuardDemoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('APP_MODE=demo');
        $_ENV['APP_MODE'] = $_SERVER['APP_MODE'] = 'demo';
        config(['app.mode' => 'demo']);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        putenv('APP_MODE');
        unset($_ENV['APP_MODE'], $_SERVER['APP_MODE']);
        parent::tearDown();
    }

    public function test_demo_mode_caps_assistant_usage_at_ten_per_ip(): void
    {
        $guard = app(AssistantUsageGuardService::class);

        for ($turn = 1; $turn <= 10; $turn++) {
            $this->assertNull($guard->check(), "Turn {$turn} should be allowed");
        }

        $blocked = $guard->check();
        $this->assertIsArray($blocked);
        $this->assertSame('blocked', $blocked['intent']);
    }
}
