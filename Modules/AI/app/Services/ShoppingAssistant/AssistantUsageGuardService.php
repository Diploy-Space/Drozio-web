<?php

namespace Modules\AI\app\Services\ShoppingAssistant;

use Illuminate\Support\Facades\Cache;
use Modules\AI\app\Exceptions\UsageLimitException;
use Modules\AI\app\Services\AIUsageManagerService;
use Modules\AI\app\Services\New\AISettingService;
use Modules\AI\app\Utils\CurrentAuthUser;

class AssistantUsageGuardService
{
    private const DEMO_LIMIT = 10;

    public function __construct(
        private readonly AIUsageManagerService $usage,
        private readonly AISettingService $aiSettingService,
    ) {}

    public function check(?string $imageUrl = null): ?array
    {
        if (strtolower((string) config('app.mode')) === 'demo') {
            return $this->checkDemoLimit();
        }

        if (CurrentAuthUser::isAdmin() || ! CurrentAuthUser::isCustomer()) {
            return null;
        }

        return $this->checkCustomerLimit(imageUrl: $imageUrl);
    }

    private function checkDemoLimit(): ?array
    {
        $ip = request()->header('x-forwarded-for') ?: request()->ip();
        $cacheKey = 'demo_ip_usage_'.$ip;
        $count = (int) Cache::get($cacheKey, 0);

        if ($count >= self::DEMO_LIMIT) {
            return [
                'reply' => translate('Demo limit reached. The assistant has limited uses.'),
                'intent' => 'blocked',
            ];
        }

        Cache::forever($cacheKey, $count + 1);

        return null;
    }

    private function checkCustomerLimit(?string $imageUrl): ?array
    {
        $provider = $this->aiSettingService->findBy(params: ['status' => 1]);
        if (! $provider) {
            return null;
        }

        $log = $this->usage->getOrCreateLog(activeProvider: $provider);

        try {
            $this->usage->checkUsageLimits(log: $log, provider: $provider, imageUrl: $imageUrl);
        } catch (UsageLimitException) {
            return [
                'reply' => translate('You have reached your usage limit for the shopping assistant'),
                'intent' => 'blocked',
            ];
        }

        $this->usage->incrementUsage(log: $log, imageUrl: $imageUrl);

        return null;
    }
}
