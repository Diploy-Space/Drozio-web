<?php

namespace Modules\AI\app\Http\Controllers;

use App\Traits\StorageTrait;
use App\Utils\ImageManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\AI\app\Events\ShoppingAssistantTurnRequestedEvent;
use Modules\AI\app\Http\Requests\ShoppingAssistant\SendMessageRequest;
use Modules\AI\app\Http\Requests\ShoppingAssistant\StartSessionRequest;
use Modules\AI\app\Http\Requests\ShoppingAssistant\UploadImageRequest;
use Modules\AI\app\Services\ShoppingAssistant\AiChatSessionService;
use Modules\AI\app\Services\ShoppingAssistant\CartSelectionContext;
use Modules\AI\app\Storefronts\RetailShoppingStorefront;

class ShoppingAssistantController extends Controller
{
    use StorageTrait;

    private const SESSION_LIST_LIMIT = 10;

    public function __construct(
        private readonly AiChatSessionService $sessionService,
        private readonly RetailShoppingStorefront $storefront,
        private readonly CartSelectionContext $cartContext,
    ) {}

    public function startSession(StartSessionRequest $request): JsonResponse
    {
        [$customerId, $guestId] = $this->resolveIdentity($request);

        if (! $customerId && ! $guestId) {
            $guestId = (string) Str::uuid();
        }

        $session = $this->sessionService->create(data: [
            'customer_id' => $customerId,
            'guest_id' => $guestId,
            'business_module' => $this->storefront->businessModule(),
            'locale' => app()->getLocale(),
        ]);

        $response = response()->json([
            'status' => true,
            'session' => $session,
            'guest_id' => $customerId ? null : $guestId,
        ], 201);

        if (! $customerId && $guestId) {
            $response->withCookie(cookie(name: 'guest_id', value: $guestId, minutes: 60 * 24 * 30, httpOnly: false));
        }

        return $response;
    }

    public function listSessions(Request $request): JsonResponse
    {
        [$customerId, $guestId] = $this->resolveIdentity($request);

        $sessions = $this->sessionService->getList(
            dataLimit: self::SESSION_LIST_LIMIT,
            page: 1,
            withCount: ['messages'],
            filters: [
                AiChatSessionService::FILTER_OWNER => $this->owner(customerId: $customerId, guestId: $guestId),
                AiChatSessionService::FILTER_BUSINESS_MODULE => $this->storefront->businessModule(),
                AiChatSessionService::FILTER_HAS_MESSAGES => true,
            ],
        )->getCollection();

        return response()->json([
            'status' => true,
            'sessions' => $sessions,
        ]);
    }

    public function getSession(Request $request, int $id): JsonResponse
    {
        [$customerId, $guestId] = $this->resolveIdentity($request);

        $session = $this->findOwnedSession(sessionId: $id, customerId: $customerId, guestId: $guestId, relations: ['messages']);

        if (! $session) {
            return response()->json(['status' => false, 'message' => translate('Not found')], 404);
        }

        return response()->json(['status' => true, 'session' => $session]);
    }

    public function deleteSession(Request $request, int $id): JsonResponse
    {
        [$customerId, $guestId] = $this->resolveIdentity($request);

        $session = $this->findOwnedSession(sessionId: $id, customerId: $customerId, guestId: $guestId);
        $deleted = $session !== null && $this->sessionService->delete(id: $session->id);

        if (! $deleted) {
            return response()->json(['status' => false, 'message' => translate('Not found')], 404);
        }

        return response()->json(['status' => true, 'message' => translate('Session deleted')]);
    }

    public function uploadImage(UploadImageRequest $request): JsonResponse
    {
        if (in_array($request->ip(), ['127.0.0.1', '::1'])) {
            $url = config('ai.dev_sample_image_url');

            return response()->json(['status' => true, 'url' => $url]);
        }

        $storageType = getWebConfig(name: 'storage_connection_type') ?? 'public';
        $fileName = ImageManager::upload(dir: 'ai/shopping-assistant/', format: 'webp', image: $request->file('image'));

        return response()->json([
            'status' => true,
            'url' => $this->storageLink('ai/shopping-assistant', $fileName, $storageType)['path'],
        ]);
    }

    public function sendMessage(SendMessageRequest $request, int $id): JsonResponse
    {
        [$customerId, $guestId] = $this->resolveIdentity($request);

        if (! $this->findOwnedSession(sessionId: $id, customerId: $customerId, guestId: $guestId)) {
            return response()->json(['status' => false, 'message' => translate('Not found')], 404);
        }

        $customer = auth('customer')->user();
        $this->cartContext->set(
            customer: $customer,
            guestId: $customer ? null : (session('guest_id') ?? $guestId),
            selections: $request->input('cart_selections') ?? $request->input('cart_selection'),
        );

        $turn = new ShoppingAssistantTurnRequestedEvent(
            sessionId: $id,
            content: $request->input('message'),
            imageUrl: $request->input('image_url'),
            storefront: $this->storefront,
        );

        event($turn);

        $result = $turn->result;

        return response()->json([
            'status' => true,
            'reply' => $result['reply'],
            'intent' => $result['intent'],
            'products' => $result['products'],
            'actions' => $result['actions'] ?? [],
        ]);
    }

    private function findOwnedSession(int $sessionId, ?int $customerId, ?string $guestId, array $relations = []): ?Model
    {
        return $this->sessionService->findBy(params: [
            'id' => $sessionId,
            AiChatSessionService::FILTER_OWNER => $this->owner(customerId: $customerId, guestId: $guestId),
            AiChatSessionService::FILTER_BUSINESS_MODULE => $this->storefront->businessModule(),
        ], relations: $relations);
    }

    private function owner(?int $customerId, ?string $guestId): array
    {
        return [
            AiChatSessionService::OWNER_CUSTOMER_ID => $customerId,
            AiChatSessionService::OWNER_GUEST_ID => $guestId,
        ];
    }

    private function resolveIdentity(Request $request): array
    {
        $customer = auth('customer')->user();
        $customerId = $customer?->id;
        $guestId = $customer ? null : ($request->cookie('guest_id') ?? $request->input('guest_id'));

        return [$customerId, $guestId];
    }
}
