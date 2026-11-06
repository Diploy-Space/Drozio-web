<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman;

use App\Events\ChattingEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Chatting\SendMessageRequest;
use App\Http\Resources\V6\Deliveryman\ChattingMessageResource;
use App\Http\Resources\V6\Deliveryman\ChattingResource;
use App\Services\New\ChattingService;
use App\Services\New\CustomerService;
use App\Services\New\ShopService;
use App\Services\New\VendorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ChatController extends BaseApiController
{
    private const NOTIFICATION_KEY = 'message_from_delivery_man';

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
        private readonly CustomerService $customerService,
        private readonly VendorService $vendorService,
        private readonly ShopService $shopService,
    ) {}

    #[ApiDoc(
        summary: 'Chat threads the delivery man has',
        description: 'One row per counterparty with its latest message and unseen count. type is customer, seller or admin; seller rows carry the shop availability fields, the admin row is named for the in-house shop, and search matches the customer name or the shop name.',
        tag: 'Delivery-man Chat',
        query: ['type' => 'nullable|string', 'limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function getThreads(Request $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $args = [

            'search' => $request->query('search'),

            'type' => $request->query('type'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);
        $counterpartyType = $args['type'] ?? ChattingService::TYPE_CUSTOMER;

        if (! $this->isValidCounterparty($counterpartyType)) {
            return $this->invalidChatType();
        }

        $threads = $this->chattingService->getThreadList(
            viewerType: ChattingService::TYPE_DELIVERY_MAN,
            viewerId: (int) $deliveryman->id,
            counterpartyType: $counterpartyType,
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: $this->threadRelations($counterpartyType),
        );

        $this->attachUnseenCounts(
            threads: $threads,
            deliverymanId: (int) $deliveryman->id,
            counterpartyType: $counterpartyType,
        );

        $this->attachCounterpartyShop(threads: $threads, counterpartyType: $counterpartyType);

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
        tag: 'Delivery-man Chat',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function getMessages(Request $request, string $type, ?string $id = null): JsonResponse
    {
        if (! $this->isValidCounterparty($type)) {
            return $this->invalidChatType();
        }

        $deliveryman = $request->attributes->get('delivery_man');
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);
        $counterpartyId = $id === null ? null : (int) $id;

        $messages = $this->chattingService->getMessageList(
            viewerType: ChattingService::TYPE_DELIVERY_MAN,
            viewerId: (int) $deliveryman->id,
            counterpartyType: $type,
            counterpartyId: $counterpartyId,
            dataLimit: $limit,
            page: $offset,
        );

        $this->chattingService->markSeen(
            viewerType: ChattingService::TYPE_DELIVERY_MAN,
            viewerId: (int) $deliveryman->id,
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
        description: 'Sends to a customer, a seller or the admin. The counterparty is resolved before anything is uploaded or stored.',
        tag: 'Delivery-man Chat',
        status: [201, 401, 403, 404, 422],
        guard: 'deliveryman',
    )]
    public function sendMessage(SendMessageRequest $request, string $type, ?string $id = null): JsonResponse
    {
        if (! $this->isValidCounterparty($type)) {
            return $this->invalidChatType();
        }

        $deliveryman = $request->attributes->get('delivery_man');
        $counterpartyId = $id === null ? null : (int) $id;
        $counterparty = $this->findCounterparty(type: $type, id: $counterpartyId);

        if ($type !== ChattingService::TYPE_ADMIN && $counterparty === null) {
            return $this->responseFormatter(config: config('response.chat_recipient_not_found'));
        }

        $message = $this->chattingService->sendMessage(
            viewerType: ChattingService::TYPE_DELIVERY_MAN,
            viewerId: (int) $deliveryman->id,
            counterpartyType: $type,
            counterpartyId: $counterpartyId,
            message: $request['message'],
            attachments: array_merge($request->file('media') ?? [], $request->file('file') ?? []),
            shopId: $type === ChattingService::TYPE_SELLER ? $counterparty->shop?->id : null,
        );

        $this->notifyCounterparty(type: $type, counterparty: $counterparty, sender: $deliveryman);

        return $this->responseFormatter(
            config: config('response.message_sent'),
            content: ['data' => new ChattingMessageResource($message),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Mark a chat thread seen',
        description: 'Idempotent — marking an already-seen thread still answers 200.',
        tag: 'Delivery-man Chat',
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function markSeen(Request $request, string $type, ?string $id = null): JsonResponse
    {
        if (! $this->isValidCounterparty($type)) {
            return $this->invalidChatType();
        }

        $deliveryman = $request->attributes->get('delivery_man');

        $this->chattingService->markSeen(
            viewerType: ChattingService::TYPE_DELIVERY_MAN,
            viewerId: (int) $deliveryman->id,
            counterpartyType: $type,
            counterpartyId: $id === null ? null : (int) $id,
        );

        return $this->responseFormatter(config: config('response.messages_seen'));
    }

    private function isValidCounterparty(string $type): bool
    {
        return in_array($type, ChattingService::counterpartyTypesFor(ChattingService::TYPE_DELIVERY_MAN), true);
    }

    private function attachUnseenCounts(mixed $threads, int $deliverymanId, string $counterpartyType): void
    {
        $counts = $this->chattingService->getUnseenCountsByCounterparty(
            viewerType: ChattingService::TYPE_DELIVERY_MAN,
            viewerId: $deliverymanId,
            counterpartyType: $counterpartyType,
        );

        $column = match ($counterpartyType) {
            ChattingService::TYPE_CUSTOMER => 'user_id',
            ChattingService::TYPE_ADMIN => 'admin_id',
            default => 'seller_id',
        };

        $rows = $threads instanceof LengthAwarePaginator ? $threads->getCollection() : $threads;

        $rows->each(function ($thread) use ($counts, $column): void {
            $thread->unseen_message_count = $counts[$thread->{$column}] ?? 0;
        });
    }

    private function threadRelations(string $counterpartyType): array
    {
        return match ($counterpartyType) {
            ChattingService::TYPE_CUSTOMER => ['customer:id,f_name,l_name,image'],
            ChattingService::TYPE_ADMIN => [],
            default => ['sellerInfo:id', 'sellerInfo.shop:'.implode(',', self::SHOP_COLUMNS)],
        };
    }

    private function attachCounterpartyShop(mixed $threads, string $counterpartyType): void
    {
        if (! in_array($counterpartyType, [ChattingService::TYPE_SELLER, ChattingService::TYPE_ADMIN], true)) {
            return;
        }

        $rows = $threads instanceof LengthAwarePaginator ? $threads->getCollection() : $threads;

        if ($counterpartyType === ChattingService::TYPE_ADMIN) {
            $inHouseShop = getInHouseShopConfig();

            $rows->each(function ($thread) use ($inHouseShop): void {
                $thread->counterparty_shop = $inHouseShop;
            });

            return;
        }

        $rows->each(function ($thread): void {
            $shop = $thread->sellerInfo?->shop;

            $thread->counterparty_shop = $shop;
            $thread->availability = $shop === null ? null : $this->shopService->getAvailability(shop: $shop);
        });
    }

    private function findCounterparty(string $type, ?int $id): mixed
    {
        return match ($type) {
            ChattingService::TYPE_CUSTOMER => $this->customerService->find(id: $id),
            ChattingService::TYPE_SELLER => $this->vendorService->find(id: $id, relations: ['shop']),
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
            type: $type,
            userData: $counterparty,
            messageForm: $sender,
        ));
    }

    private function invalidChatType(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.invalid_chat_type'));
    }
}
