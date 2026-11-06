<?php

namespace Tests\Feature\AI;

use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShoppingAssistantRateLimitTest extends TestCase
{
    private const MESSAGES_PER_MINUTE = 20;

    private const TURN_URI = '/rate-limit-probe/assistant-turn';

    private const UNRELATED_URI = '/rate-limit-probe/unrelated';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        Route::post(self::TURN_URI, fn (): string => 'ok')->middleware([
            'throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_REQUEST,
            'throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_MESSAGE,
        ]);
        Route::get(self::UNRELATED_URI, fn (): string => 'ok')->middleware('throttle:3000,1');
    }

    public function test_every_assistant_route_is_throttled_by_named_limiters_only(): void
    {
        $assistantRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => Str::contains($route->uri(), 'ai/assistant'));

        $this->assertNotEmpty($assistantRoutes);

        foreach ($assistantRoutes as $route) {
            $throttles = collect($route->gatherMiddleware())
                ->filter(fn ($middleware): bool => is_string($middleware) && Str::startsWith($middleware, 'throttle:'))
                ->reject(fn (string $middleware): bool => $middleware === 'throttle:3000,1')
                ->values();

            $this->assertNotEmpty($throttles, $route->uri().' has no assistant throttle');

            foreach ($throttles as $throttle) {
                $this->assertContains(
                    Str::after($throttle, 'throttle:'),
                    RouteServiceProvider::ASSISTANT_LIMITERS,
                    $route->uri().' uses the shared numeric throttle '.$throttle,
                );
            }
        }
    }

    public function test_a_shopper_can_send_the_full_message_allowance(): void
    {
        for ($turn = 1; $turn <= self::MESSAGES_PER_MINUTE; $turn++) {
            $this->postJson(self::TURN_URI, ['guest_id' => 'guest-a'])->assertOk();
        }

        $this->postJson(self::TURN_URI, ['guest_id' => 'guest-a'])->assertStatus(429);
    }

    public function test_one_shopper_reaching_the_limit_does_not_block_another_on_the_same_ip(): void
    {
        for ($turn = 1; $turn <= self::MESSAGES_PER_MINUTE; $turn++) {
            $this->postJson(self::TURN_URI, ['guest_id' => 'guest-a']);
        }

        $this->postJson(self::TURN_URI, ['guest_id' => 'guest-a'])->assertStatus(429);
        $this->postJson(self::TURN_URI, ['guest_id' => 'guest-b'])->assertOk();
    }

    public function test_unrelated_traffic_from_the_same_ip_does_not_consume_the_message_allowance(): void
    {
        for ($request = 1; $request <= 50; $request++) {
            $this->getJson(self::UNRELATED_URI)->assertOk();
        }

        $this->postJson(self::TURN_URI, ['guest_id' => 'guest-a'])->assertOk();
    }

    public function test_rotating_the_guest_id_cannot_exceed_the_ip_ceiling(): void
    {
        $statuses = [];

        for ($turn = 1; $turn <= 150; $turn++) {
            $statuses[] = $this->postJson(self::TURN_URI, ['guest_id' => 'guest-'.$turn])->getStatusCode();
        }

        $this->assertContains(429, $statuses);
    }
}
