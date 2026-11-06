<?php

namespace Modules\AI\app\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Traits\StorageTrait;
use App\Utils\ImageManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AI\app\Events\ShoppingAssistantTurnRequestedEvent;
use Modules\AI\app\Http\Requests\Customer\ShoppingAssistant\StartSessionRequest;
use Modules\AI\app\Http\Requests\ShoppingAssistant\SendMessageRequest;
use Modules\AI\app\Http\Requests\ShoppingAssistant\UploadImageRequest;
use Modules\AI\app\Services\ShoppingAssistant\AiChatSessionService;
use Modules\AI\app\Services\ShoppingAssistant\CartSelectionContext;
use Modules\AI\app\Storefronts\RetailShoppingStorefront;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ShoppingAssistantController extends BaseApiController
{
    use StorageTrait;

    private const SESSION_LIST_LIMIT = 10;

    private const TAG = 'AI shopping assistant';

    private const IMAGE_DIRECTORY = 'ai/shopping-assistant';

    private const IMAGE_FORMAT = 'webp';

    private const DEFAULT_STORAGE_TYPE = 'public';

    private const LOCAL_ADDRESSES = ['127.0.0.1', '::1'];

    private const GUEST_QUERY = ['guest_id' => 'nullable|string'];

    public function __construct(
        private readonly AiChatSessionService $sessionService,
        private readonly RetailShoppingStorefront $storefront,
        private readonly CartSelectionContext $cartContext,
    ) {}

    #[ApiDoc(
        summary: 'Upload an image for the AI shopping assistant',
        description: 'Answers the stored image url to send back with a shopper message.',
        tag: self::TAG,
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function uploadImage(UploadImageRequest $request): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.ai_assistant_image_uploaded'),
            content: ['url' => $this->storeImage($request)],
        );
    }

    #[ApiDoc(
        summary: 'Start an AI shopping assistant session',
        description: 'A guest must send the app-wide guest id from GET api/v6/get-guest-id so the session shares that guest cart.',
        tag: self::TAG,
        status: [201, 422],
        auth: false,
        guard: 'api',
    )]
    public function startSession(StartSessionRequest $request): JsonResponse
    {
        [$customerId, $guestId] = $this->resolveIdentity($request);

        $session = $this->sessionService->create(data: [
            'customer_id' => $customerId,
            'guest_id' => $guestId,
            'business_module' => $this->storefront->businessModule(),
            'locale' => app()->getLocale(),
        ]);

        return $this->responseFormatter(
            config: config('response.created_201'),
            content: [
                'session' => $session,
                'guest_id' => $customerId ? null : $guestId,
            ],
        );
    }

    #[ApiDoc(
        summary: 'AI shopping assistant sessions of the current shopper',
        tag: self::TAG,
        query: self::GUEST_QUERY,
        auth: false,
        guard: 'api',
    )]
    public function listSessions(Request $request): JsonResponse
    {
        [$customerId, $guestId] = $this->resolveIdentity($request);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'sessions' => $this->getSessionList(customerId: $customerId, guestId: $guestId),
            ],
        );
    }

    #[ApiDoc(
        summary: 'One AI shopping assistant session with its messages',
        tag: self::TAG,
        query: self::GUEST_QUERY,
        status: [404],
        auth: false,
        guard: 'api',
    )]
    public function getSession(Request $request, int $id): JsonResponse
    {
        [$customerId, $guestId] = $this->resolveIdentity($request);

        $session = $this->findOwnedSession(sessionId: $id, customerId: $customerId, guestId: $guestId, relations: ['messages']);

        if ($session === null) {
            return $this->responseFormatter(config: config('response.not_found_404'));
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['session' => $session],
        );
    }

    #[ApiDoc(
        summary: 'Delete an AI shopping assistant session',
        tag: self::TAG,
        query: self::GUEST_QUERY,
        status: [404],
        auth: false,
        guard: 'api',
    )]
    public function deleteSession(Request $request, int $id): JsonResponse
    {
        [$customerId, $guestId] = $this->resolveIdentity($request);

        $session = $this->findOwnedSession(sessionId: $id, customerId: $customerId, guestId: $guestId);
        $isDeleted = $session !== null && $this->sessionService->delete(id: $session->id);

        if (! $isDeleted) {
            return $this->responseFormatter(config: config('response.not_found_404'));
        }

        return $this->responseFormatter(config: config('response.default_delete_200'));
    }

    #[ApiDoc(
        summary: 'Send a message to the AI shopping assistant',
        description: 'Answers the assistant reply, the detected intent, the suggested products and the cart actions it prepared.',
        tag: self::TAG,
        status: [404, 422],
        auth: false,
        guard: 'api',
    )]
    public function sendMessage(SendMessageRequest $request, int $id): JsonResponse
    {
        [$customerId, $guestId] = $this->resolveIdentity($request);

        if ($this->findOwnedSession(sessionId: $id, customerId: $customerId, guestId: $guestId) === null) {
            return $this->responseFormatter(config: config('response.not_found_404'));
        }

        $customer = auth('api')->user();

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

        return $this->responseFormatter(
            config: config('response.default_200'),
            content: [
                'reply' => $result['reply'],
                'intent' => $result['intent'],
                'products' => $result['products'],
                'actions' => $result['actions'] ?? [],
            ],
        );
    }

    private function storeImage(UploadImageRequest $request): ?string
    {
        if (in_array($request->ip(), self::LOCAL_ADDRESSES, strict: true)) {
            return config('ai.dev_sample_image_url');
        }

        $storageType = getWebConfig(name: 'storage_connection_type') ?? self::DEFAULT_STORAGE_TYPE;

        $fileName = ImageManager::upload(
            dir: self::IMAGE_DIRECTORY.'/',
            format: self::IMAGE_FORMAT,
            image: $request->file('image'),
        );

        return $this->storageLink(self::IMAGE_DIRECTORY, $fileName, $storageType)['path'];
    }

    private function getSessionList(?int $customerId, ?string $guestId): Collection
    {
        return $this->sessionService->getList(
            dataLimit: self::SESSION_LIST_LIMIT,
            page: 1,
            withCount: ['messages'],
            filters: [
                AiChatSessionService::FILTER_OWNER => $this->owner(customerId: $customerId, guestId: $guestId),
                AiChatSessionService::FILTER_BUSINESS_MODULE => $this->storefront->businessModule(),
                AiChatSessionService::FILTER_HAS_MESSAGES => true,
            ],
        )->getCollection();
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

    /** @return array{0: ?int, 1: ?string} */
    private function resolveIdentity(Request $request): array
    {
        $customer = auth('api')->user();
        $customerId = $customer?->id;

        return [$customerId, $customer ? null : $request->input('guest_id')];
    }
}
