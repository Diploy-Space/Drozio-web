<?php

namespace Modules\AI\app\Services\ShoppingAssistant;

use Illuminate\Support\Facades\Cache;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Modules\AI\AIProviders\AIProviderManager;

class AgentLoopService
{
    public function __construct(
        private readonly ProductCollector $collector,
        private readonly CartActionCollector $cartAction,
        private readonly ImageAnalysisService $imageAnalysis,
        private readonly AIProviderManager $providerManager,
    ) {}

    public function run(
        array $history,
        string $newMessage,
        string $systemPrompt,
        array $tools,
        string $businessModule,
        ?string $imageUrl = null,
    ): array {
        $cacheKey = $this->resolveCacheKey(history: $history, newMessage: $newMessage, imageUrl: $imageUrl, businessModule: $businessModule);

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $this->collector->reset();
        $this->cartAction->reset();
        $this->configureProvider();

        $messages = $this->toMessageObjects(history: $history);

        $agent = new AnonymousAgent($systemPrompt, $messages, $tools);

        $prompt = $imageUrl
            ? $this->buildImageAwarePrompt(newMessage: $newMessage, imageUrl: $imageUrl)
            : $newMessage;

        $response = $agent->prompt($prompt);

        $result = [
            'reply' => $response->text,
            'products' => $this->collector->all(),
            'tools_used' => $this->collector->toolsUsed(),
            'actions' => $this->cartAction->all(),
        ];

        if ($this->isCustomerAgnosticTurn($result)) {
            Cache::put(key: $cacheKey, value: $result, ttl: now()->addMinutes(10));
        }

        return $result;
    }

    private function isCustomerAgnosticTurn(array $result): bool
    {
        return empty($result['actions'])
            && ! in_array('recall_shown_products', $result['tools_used'], strict: true);
    }

    private function resolveCacheKey(array $history, string $newMessage, ?string $imageUrl, string $businessModule): string
    {
        $normalized = strtolower(trim($newMessage));
        $currency = (string) session('currency_code', '');

        if (empty($history)) {
            return 'ai_response_global:'.$businessModule.':'.md5(
                app()->getLocale().'|'.$currency.'|'.$normalized.'|'.($imageUrl ?? '')
            );
        }

        return 'ai_response:'.$businessModule.':'.md5(
            json_encode(array_slice($history, -6))
            .'|'.$currency
            .'|'.$normalized
            .'|'.($imageUrl ?? '')
        );
    }

    private function buildImageAwarePrompt(string $newMessage, string $imageUrl): string
    {
        $analysis = $this->imageAnalysis->extractProductKeywords($imageUrl);

        $hasDetails = ! empty($analysis['keywords']) || ! empty($analysis['category']) || ! empty($analysis['attributes']);

        $parts = [$newMessage];

        if (! $hasDetails) {
            $parts[] = 'The uploaded image could not be analysed for product details. '
                .'Ask the customer to briefly describe the product they are looking for.';

            return implode("\n", $parts);
        }

        if (! empty($analysis['keywords'])) {
            $parts[] = 'Visible in image: '.implode(', ', $analysis['keywords']).'.';
        }

        if (! empty($analysis['category'])) {
            $parts[] = 'Category: '.$analysis['category'].'.';
        }

        if (! empty($analysis['attributes'])) {
            $attrs = collect($analysis['attributes'])
                ->map(fn ($v, $k) => "{$k}: {$v}")
                ->implode(', ');
            $parts[] = 'Attributes: '.$attrs.'.';
        }

        $parts[] = 'Call search_products now using these image details (use the product type/category as the query) '
            .'to find matching or similar products from the catalog.';

        return implode("\n", $parts);
    }

    private function configureProvider(): void
    {
        $setting = $this->providerManager->getActiveAIProvider();

        $providerName = match (strtolower($setting->ai_name)) {
            'claude', 'anthropic' => 'anthropic',
            default => 'openai',
        };

        config(["ai.providers.{$providerName}.key" => $setting->api_key]);
        config(['ai.default' => $providerName]);

        if ($setting->organization_id) {
            config(["ai.providers.{$providerName}.organization" => $setting->organization_id]);
        }
    }

    private function toMessageObjects(array $history): array
    {
        return collect($history)
            ->filter(fn ($m) => in_array($m['role'], ['user', 'assistant'], strict: true))
            ->map(fn ($m) => match ($m['role']) {
                'user' => new UserMessage($m['content']),
                'assistant' => new AssistantMessage($m['content']),
            })
            ->values()
            ->all();
    }
}
