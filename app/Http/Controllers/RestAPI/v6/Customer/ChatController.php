<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Events\ChattingEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Chatting\SendMessageRequest;
use App\Http\Resources\V6\Customer\ChattingMessageResource;
use App\Http\Resources\V6\Customer\ChattingResource;
use App\Services\New\ChattingService;
use App\Services\New\DeliveryManService;
use App\Services\New\ShopService;
use App\Services\New\VendorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ChatController extends BaseApiController
{
    private const NOTIFICATION_KEY = 'message_from_customer';

    /** @var array<int, string> */
    private const SHOP_COLUMNS = [
        'id',
        'seller_id',
        'author_type',
        'name',
        'image',
        'vacation_start_date',
        'vacation_end_date',
        'vacation_duration_type',
        'vacation_modules',
        'vacation_status',
        'temporary_close',
    ];

    public function __construct(
        private readonly ChattingService $chattingService,
        private readonly VendorService $vendorService,
        private readonly DeliveryManService $deliveryManService,
        private readonly ShopService $shopService,
    ) {}

    #[ApiDoc(
        summary: 'Chat threads the signed-in customer has',
        description: 'One row per counterparty, carrying its latest message and unseen count. type=seller returns the in-house (admin) thread alongside vendor threads, mirroring v1. Seller and admin rows carry the shop availability fields; a search matches the shop name for sellers and the name for delivery men, and never returns the admin thread.',
        tag: 'Chat',
        query: [
            'type' => 'nullable|string',
            'limit' => 'nullable|string',
            'offset' => 'nullable|integer',
            'search' => 'nullable|string',
        ],
        status: [401, 422],
        guard: 'api',
    )]
    public function getThreads(Request $request): JsonResponse
    {
        $args = [
            'search' => $request->query('search'),
            'type' => $request->query('type'),
        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);
        $counterpartyType = $args['type'] ?? ChattingService::TYPE_SELLER;

        if (! in_array($counterpartyType, ChattingService::counterpartyTypes(), true)) {
            return $this->invalidChatType();
        }

        $customerId = $this->customerId($request);

        $counterpartyTypes = $this->requestedCounterpartyTypes($counterpartyType);

        $threads = $this->chattingService->getThreadList(
            viewerType: ChattingService::TYPE_CUSTOMER,
            viewerId: $customerId,
            counterpartyType: $counterpartyTypes,
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: $this->threadRelations($counterpartyType),
        );

        $this->attachUnseenCounts(threads: $threads, customerId: $customerId, counterpartyTypes: $counterpartyTypes);
        $this->attachCounterpartyShop(threads: $threads, counterpartyTypes: $counterpartyTypes);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ChattingResource::collection($threads),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($threads),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Messages in one chat thread',
        description: 'Marks the counterparty messages seen as a side effect, mirroring how the app opens a thread.',
        tag: 'Chat',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 422],
        guard: 'api',
    )]
    public function getMessages(Request $request, string $type, ?string $id = null): JsonResponse
    {
        if (! in_array($type, ChattingService::counterpartyTypes(), true)) {
            return $this->invalidChatType();
        }

        $args = [
            'search' => $request->query('search'),
        ];

        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);

        $offset = (int) $request->query('offset', 1);
        $customerId = $this->customerId($request);
        [$type, $counterpartyId] = $this->resolveCounterparty(type: $type, id: $id);

        $messages = $this->chattingService->getMessageList(
            viewerType: ChattingService::TYPE_CUSTOMER,
            viewerId: $customerId,
            counterpartyType: $type,
            counterpartyId: $counterpartyId,
            dataLimit: $limit,
            page: $offset,
        );

        $this->chattingService->markSeen(
            viewerType: ChattingService::TYPE_CUSTOMER,
            viewerId: $customerId,
            counterpartyType: $type,
            counterpartyId: $counterpartyId,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ChattingMessageResource::collection($messages),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($messages),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Send a chat message',
        description: 'Sends to a seller, a delivery man or the admin. The counterparty is resolved before anything is uploaded or stored.',
        tag: 'Chat',
        status: [201, 401, 404, 422],
        guard: 'api',
    )]
    public function sendMessage(SendMessageRequest $request, string $type, ?string $id = null): JsonResponse
    {
        if (! in_array($type, ChattingService::counterpartyTypes(), true)) {
            return $this->invalidChatType();
        }

        [$type, $counterpartyId] = $this->resolveCounterparty(type: $type, id: $id);
        $counterparty = $this->findCounterparty(type: $type, id: $counterpartyId);

        if ($type !== ChattingService::TYPE_ADMIN && $counterparty === null) {
            return $this->responseFormatter(config: config('response.chat_recipient_not_found'));
        }

        $message = $this->chattingService->sendMessage(
            viewerType: ChattingService::TYPE_CUSTOMER,
            viewerId: $this->customerId($request),
            counterpartyType: $type,
            counterpartyId: $counterpartyId,
            message: $request['message'],
            attachments: array_merge($request->file('media') ?? [], $request->file('file') ?? []),
            shopId: $type === ChattingService::TYPE_SELLER ? $counterparty->shop?->id : null,
        );

        $this->notifyCounterparty(type: $type, counterparty: $counterparty, sender: $request->user());

        return $this->responseFormatter(
            config: config('response.message_sent'),
            content: ['data' => new ChattingMessageResource($message)],
        );
    }

    #[ApiDoc(
        summary: 'Mark a chat thread seen',
        description: 'Idempotent — marking an already-seen thread still answers 200.',
        tag: 'Chat',
        status: [401, 422],
        guard: 'api',
    )]
    public function markSeen(Request $request, string $type, ?string $id = null): JsonResponse
    {
        if (! in_array($type, ChattingService::counterpartyTypes(), true)) {
            return $this->invalidChatType();
        }

        [$type, $counterpartyId] = $this->resolveCounterparty(type: $type, id: $id);

        $this->chattingService->markSeen(
            viewerType: ChattingService::TYPE_CUSTOMER,
            viewerId: $this->customerId($request),
            counterpartyType: $type,
            counterpartyId: $counterpartyId,
        );

        return $this->responseFormatter(config: config('response.messages_seen'));
    }

    private function attachUnseenCounts(mixed $threads, int $customerId, array $counterpartyTypes): void
    {
        $countsByColumn = [];

        foreach ($counterpartyTypes as $type) {
            $countsByColumn[$this->threadColumn($type)] = $this->chattingService->getUnseenCountsByCounterparty(
                viewerType: ChattingService::TYPE_CUSTOMER,
                viewerId: $customerId,
                counterpartyType: $type,
            );
        }

        $rows = $threads instanceof LengthAwarePaginator ? $threads->getCollection() : $threads;

        $rows->each(function ($thread) use ($countsByColumn): void {
            $thread->unseen_message_count = 0;

            foreach ($countsByColumn as $column => $counts) {
                if ($thread->{$column} !== null) {
                    $thread->unseen_message_count = $counts[$thread->{$column}] ?? 0;

                    return;
                }
            }
        });
    }

    private function threadColumn(string $counterpartyType): string
    {
        return match ($counterpartyType) {
            ChattingService::TYPE_DELIVERY_MAN => 'delivery_man_id',
            ChattingService::TYPE_ADMIN => 'admin_id',
            default => 'seller_id',
        };
    }

    /**
     * @return array<int, string>
     */
    private function requestedCounterpartyTypes(string $counterpartyType): array
    {
        return $counterpartyType === ChattingService::TYPE_SELLER
            ? [ChattingService::TYPE_SELLER, ChattingService::TYPE_ADMIN]
            : [$counterpartyType];
    }

    private function threadRelations(string $counterpartyType): array
    {
        return match ($counterpartyType) {
            ChattingService::TYPE_DELIVERY_MAN => ['deliveryMan:id,f_name,l_name,image'],
            ChattingService::TYPE_ADMIN => [],
            default => ['sellerInfo:id', 'sellerInfo.shop:'.implode(',', self::SHOP_COLUMNS)],
        };
    }

    private function attachCounterpartyShop(mixed $threads, array $counterpartyTypes): void
    {
        if (! in_array(ChattingService::TYPE_SELLER, $counterpartyTypes, true)
            && ! in_array(ChattingService::TYPE_ADMIN, $counterpartyTypes, true)) {
            return;
        }

        $inHouseShop = getInHouseShopConfig();
        $rows = $threads instanceof LengthAwarePaginator ? $threads->getCollection() : $threads;

        $rows->each(function ($thread) use ($inHouseShop): void {
            $shop = $thread->seller_id !== null ? $thread->sellerInfo?->shop : $inHouseShop;

            $thread->counterparty_shop = $shop;
            $thread->availability = $shop === null ? null : $this->shopService->getAvailability(shop: $shop);
        });
    }

    /**
     * @return array{0: string, 1: ?int}
     */
    private function resolveCounterparty(string $type, ?string $id): array
    {
        $counterpartyId = $id === null ? null : (int) $id;

        return $type === ChattingService::TYPE_SELLER && $counterpartyId === ChattingService::ADMIN_THREAD_ID
            ? [ChattingService::TYPE_ADMIN, null]
            : [$type, $counterpartyId];
    }

    private function findCounterparty(string $type, ?int $id): mixed
    {
        return match ($type) {
            ChattingService::TYPE_SELLER => $this->vendorService->find(id: $id, relations: ['shop']),
            ChattingService::TYPE_DELIVERY_MAN => $this->deliveryManService->find(id: $id),
            default => null,
        };
    }

    private function notifyCounterparty(string $type, mixed $counterparty, mixed $sender): void
    {
        if ($counterparty === null) {
            return;
        }

        event(new ChattingEvent(
            key: self::NOTIFICATION_KEY,
            type: $type === ChattingService::TYPE_DELIVERY_MAN ? 'delivery_man' : 'seller',
            userData: $counterparty,
            messageForm: $sender,
        ));
    }

    private function invalidChatType(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.invalid_chat_type'));
    }

    private function customerId(Request $request): int
    {
        return (int) $request->user()->id;
    }
}
