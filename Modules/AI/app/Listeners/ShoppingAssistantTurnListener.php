<?php

namespace Modules\AI\app\Listeners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\AI\app\Contracts\ShoppingStorefrontInterface;
use Modules\AI\app\Events\ShoppingAssistantTurnRequestedEvent;
use Modules\AI\app\Services\ShoppingAssistant\AgentLoopService;
use Modules\AI\app\Services\ShoppingAssistant\AiChatMessageService;
use Modules\AI\app\Services\ShoppingAssistant\AiChatSessionService;
use Modules\AI\app\Services\ShoppingAssistant\AssistantUsageGuardService;
use Modules\AI\app\Services\ShoppingAssistant\QueryGuardService;
use Modules\AI\app\Services\ShoppingAssistant\ShownProductStore;
use Throwable;

class ShoppingAssistantTurnListener
{
    private const CONTEXT_TURNS = 5;

    private const RECALL_LOOKBACK = 40;

    private const PRODUCT_TOOLS = ['search_products', 'get_similar_products', 'recall_shown_products', 'search_offers'];

    public function __construct(
        private readonly AiChatSessionService $sessionService,
        private readonly AiChatMessageService $messageService,
        private readonly AgentLoopService $agentLoop,
        private readonly QueryGuardService $guard,
        private readonly ShownProductStore $shownProducts,
        private readonly AssistantUsageGuardService $usageGuard,
    ) {}

    public function handle(ShoppingAssistantTurnRequestedEvent $event): void
    {
        $event->result = $this->runTurn(
            sessionId: $event->sessionId,
            content: $event->content,
            imageUrl: $event->imageUrl,
            storefront: $event->storefront,
        );
    }

    private function runTurn(int $sessionId, string $content, ?string $imageUrl, ShoppingStorefrontInterface $storefront): array
    {
        $session = $this->sessionService->find(id: $sessionId);

        if (! $session) {
            return $this->reply(reply: $this->fallbackReply(), intent: 'error');
        }

        $recent = $this->messageService->getLatestConversationMessages(sessionId: $sessionId, count: self::CONTEXT_TURNS * 2);
        $history = $recent->map(fn ($message) => ['role' => $message->role, 'content' => $message->content])->values()->toArray();

        $this->recordMessage(sessionId: $sessionId, role: AiChatMessageService::ROLE_USER, content: $content, meta: [
            'image_url' => $imageUrl,
        ]);

        if (empty($session->title)) {
            $this->sessionService->update(id: $sessionId, data: ['title' => mb_strimwidth($content, 0, 60, '…')]);
        }

        if ($blocked = $this->guard->check($content, $history)) {
            return $this->recordBlockedTurn(sessionId: $sessionId, blocked: $blocked);
        }

        if ($limited = $this->usageGuard->check($imageUrl)) {
            return $this->recordBlockedTurn(sessionId: $sessionId, blocked: $limited);
        }

        $locale = app()->getLocale() ?: $session->locale;
        if ($locale) {
            app()->setLocale($locale);
        }

        $recallSource = $this->messageService->getAssistantMessagesAmongLatest(sessionId: $sessionId, count: self::RECALL_LOOKBACK);
        $this->shownProducts->reset();
        $this->shownProducts->seed($this->onlyActive(products: $this->shownProductsFrom(messages: $recallSource), storefront: $storefront));

        $systemPrompt = $storefront->systemPrompt().$this->buildProductReference(messages: $recallSource);

        try {
            $result = $this->agentLoop->run(
                history: $history,
                newMessage: $content,
                systemPrompt: $systemPrompt,
                tools: $storefront->tools(),
                businessModule: $storefront->businessModule(),
                imageUrl: $imageUrl,
            );
        } catch (Throwable $exception) {
            Log::error('AI shopping assistant turn failed', [
                'session_id' => $sessionId,
                'message' => $exception->getMessage(),
            ]);

            $fallback = $this->fallbackReply();
            $this->recordMessage(
                sessionId: $sessionId,
                role: AiChatMessageService::ROLE_ASSISTANT,
                content: $fallback,
                meta: ['products' => [], 'tools_used' => [], 'error' => true],
            );
            $this->touchSession(sessionId: $sessionId);

            return $this->reply(reply: $fallback, intent: 'error');
        }

        $result['reply'] = $this->sanitizeReply(reply: $result['reply']);

        $this->recordMessage(
            sessionId: $sessionId,
            role: AiChatMessageService::ROLE_ASSISTANT,
            content: $result['reply'],
            meta: ['products' => $result['products'], 'tools_used' => $result['tools_used']],
        );

        $this->touchSession(sessionId: $sessionId);

        $intent = array_intersect(self::PRODUCT_TOOLS, $result['tools_used'])
            ? 'product_search'
            : 'smalltalk';

        return $this->reply(
            reply: $result['reply'],
            intent: $intent,
            products: $result['products'],
            actions: $result['actions'] ?? [],
        );
    }

    private function recordBlockedTurn(int $sessionId, array $blocked): array
    {
        $this->recordMessage(
            sessionId: $sessionId,
            role: AiChatMessageService::ROLE_ASSISTANT,
            content: $blocked['reply'],
            meta: ['products' => [], 'tools_used' => []],
        );
        $this->touchSession(sessionId: $sessionId);

        return $this->reply(reply: $blocked['reply'], intent: $blocked['intent']);
    }

    private function reply(string $reply, string $intent, array $products = [], array $actions = []): array
    {
        return [
            'reply' => $reply,
            'intent' => $intent,
            'products' => $products,
            'actions' => $actions,
        ];
    }

    private function fallbackReply(): string
    {
        return translate('Sorry, I am having trouble. Please try again in a moment.');
    }

    private function touchSession(int $sessionId): void
    {
        $this->sessionService->update(id: $sessionId, data: ['last_activity_at' => now()]);
    }

    private function recordMessage(int $sessionId, string $role, string $content, array $meta = []): void
    {
        $this->messageService->create(data: [
            'session_id' => $sessionId,
            'role' => $role,
            'content' => $content,
            'meta' => $meta,
        ]);
    }

    private function onlyActive(array $products, ShoppingStorefrontInterface $storefront): array
    {
        if (empty($products)) {
            return [];
        }

        $ids = array_values(array_filter(array_map(fn ($product) => $product['id'] ?? null, $products)));
        $activeSet = array_flip($storefront->getActiveProductIds(productIds: $ids));

        return array_values(array_filter($products, fn ($product) => isset($product['id'], $activeSet[$product['id']])));
    }

    private function shownProductsFrom(Collection $messages): array
    {
        $products = [];
        foreach ($messages as $message) {
            if ($message->role !== AiChatMessageService::ROLE_ASSISTANT) {
                continue;
            }
            foreach (($message->meta['products'] ?? []) as $product) {
                if (isset($product['id'])) {
                    $products[] = $product;
                }
            }
        }

        return $products;
    }

    private function buildProductReference(Collection $messages): string
    {
        $products = [];
        foreach ($messages as $message) {
            if ($message->role !== AiChatMessageService::ROLE_ASSISTANT) {
                continue;
            }
            foreach (($message->meta['products'] ?? []) as $product) {
                if (isset($product['id'], $product['name'])) {
                    $products[$product['id']] = $product['name']
                        .(isset($product['unit_price_formatted']) ? ' ('.$product['unit_price_formatted'].')' : '');
                }
            }
        }

        if (empty($products)) {
            return '';
        }

        $list = collect($products)->map(fn ($label, $id) => "#{$id} {$label}")->implode('; ');

        return "\n\nINTERNAL CONTEXT (do not reveal): products already shown to this customer — "
            .'when they refer to one by name, position, or "it"/"that one", pass its id to add_to_cart and do '
            .'NOT search again. To re-display any of these (e.g. "show the other iPhones", "the rest"), call '
            .'recall_shown_products — these products are still available, never say otherwise. NEVER repeat ids, '
            ."this list, or any bracketed/internal data to the customer; reply only in natural language.\n".$list;
    }

    private function sanitizeReply(string $reply): string
    {
        foreach (['INTERNAL CONTEXT', '[Products shown'] as $marker) {
            $pos = stripos($reply, $marker);
            if ($pos !== false) {
                $reply = substr($reply, 0, $pos);
            }
        }
        $reply = preg_replace('/\s*\[id:\s*\d+[^\]]*\]/i', '', $reply);
        $reply = preg_replace('/(?:#\d+\s+[^;\n]+;\s*){2,}/', '', $reply);

        return trim(preg_replace('/[ \t]{2,}/', ' ', $reply));
    }
}
