<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Events\ChattingEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Chatting\SendMessageRequest;
use App\Http\Resources\V6\Vendor\ChattingMessageResource;
use App\Http\Resources\V6\Vendor\ChattingResource;
use App\Services\New\ChattingService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryManService;
use App\Services\New\ShopService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ChatController extends BaseApiController
{
    private const NOTIFICATION_KEY = 'message_from_seller';

    /** @var array<int, string> */
    private const COUNTERPARTY_TYPES = [ChattingService::TYPE_CUSTOMER, ChattingService::TYPE_DELIVERY_MAN];

    public function __construct(
        private readonly ChattingService $chattingService,
        private readonly CustomerService $customerService,
        private readonly DeliveryManService $deliveryManService,
        private readonly ShopService $shopService,
    ) {}

    #[ApiDoc(
        summary: 'Chat threads the vendor has',
        description: 'One row per counterparty with its latest message and unseen count. type is customer or delivery-man; search matches the counterparty name.',
        tag: 'Chat (Vendor)',
        query: ['type' => 'nullable|string', 'limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function getThreads(Request $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');
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
            viewerType: ChattingService::TYPE_SELLER,
            viewerId: (int) $seller->id,
            counterpartyType: $counterpartyType,
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: $this->threadRelations($counterpartyType),
        );

        $this->attachUnseenCounts(
            threads: $threads,
            sellerId: (int) $seller->id,
            counterpartyType: $counterpartyType,
        );

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
        description: 'Marks every counterparty message in the thread seen as a side effect, mirroring how the app opens a thread.',
        tag: 'Chat (Vendor)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function getMessages(Request $request, string $type, string $id): JsonResponse
    {
        if (! $this->isValidCounterparty($type)) {
            return $this->invalidChatType();
        }

        $seller = $request->attributes->get('seller');
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $messages = $this->chattingService->getMessageList(
            viewerType: ChattingService::TYPE_SELLER,
            viewerId: (int) $seller->id,
            counterpartyType: $type,
            counterpartyId: (int) $id,
            dataLimit: $limit,
            page: $offset,
        );

        $this->chattingService->markSeen(
            viewerType: ChattingService::TYPE_SELLER,
            viewerId: (int) $seller->id,
            counterpartyType: $type,
            counterpartyId: (int) $id,
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
        description: 'Sends to a customer or to one of the vendor own delivery men. The recipient is resolved before anything is uploaded or stored.',
        tag: 'Chat (Vendor)',
        status: [201, 401, 403, 404, 422],
        guard: 'seller',
    )]
    public function sendMessage(SendMessageRequest $request, string $type, string $id): JsonResponse
    {
        if (! $this->isValidCounterparty($type)) {
            return $this->invalidChatType();
        }

        $seller = $request->attributes->get('seller');
        $counterparty = $this->findCounterparty(type: $type, id: (int) $id, sellerId: (int) $seller->id);

        if ($counterparty === null) {
            return $this->responseFormatter(config: config('response.chat_recipient_not_found'));
        }

        $message = $this->chattingService->sendMessage(
            viewerType: ChattingService::TYPE_SELLER,
            viewerId: (int) $seller->id,
            counterpartyType: $type,
            counterpartyId: (int) $id,
            message: $request['message'],
            attachments: array_merge($request->file('media') ?? [], $request->file('file') ?? []),
            shopId: $this->shopService->findBy(params: ['seller_id' => $seller->id])?->id,
        );

        event(new ChattingEvent(
            key: self::NOTIFICATION_KEY,
            type: $type,
            userData: $counterparty,
            messageForm: $seller,
        ));

        return $this->responseFormatter(
            config: config('response.message_sent'),
            content: ['data' => new ChattingMessageResource($message)],
        );
    }

    #[ApiDoc(
        summary: 'Mark a chat thread seen',
        description: 'Idempotent — marking an already-seen thread still answers 200.',
        tag: 'Chat (Vendor)',
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function markSeen(Request $request, string $type, string $id): JsonResponse
    {
        if (! $this->isValidCounterparty($type)) {
            return $this->invalidChatType();
        }

        $seller = $request->attributes->get('seller');

        $this->chattingService->markSeen(
            viewerType: ChattingService::TYPE_SELLER,
            viewerId: (int) $seller->id,
            counterpartyType: $type,
            counterpartyId: (int) $id,
        );

        return $this->responseFormatter(config: config('response.messages_seen'));
    }

    private function isValidCounterparty(string $type): bool
    {
        return in_array($type, self::COUNTERPARTY_TYPES, true);
    }

    private function attachUnseenCounts(mixed $threads, int $sellerId, string $counterpartyType): void
    {
        $counts = $this->chattingService->getUnseenCountsByCounterparty(
            viewerType: ChattingService::TYPE_SELLER,
            viewerId: $sellerId,
            counterpartyType: $counterpartyType,
        );

        $column = $counterpartyType === ChattingService::TYPE_CUSTOMER ? 'user_id' : 'delivery_man_id';
        $rows = $threads instanceof LengthAwarePaginator ? $threads->getCollection() : $threads;

        $rows->each(function ($thread) use ($counts, $column): void {
            $thread->unseen_message_count = $counts[$thread->{$column}] ?? 0;
        });
    }

    private function threadRelations(string $counterpartyType): array
    {
        return $counterpartyType === ChattingService::TYPE_CUSTOMER
            ? ['customer:id,f_name,l_name,image']
            : ['deliveryMan:id,f_name,l_name,image'];
    }

    private function findCounterparty(string $type, int $id, int $sellerId): mixed
    {
        return $type === ChattingService::TYPE_CUSTOMER
            ? $this->customerService->find(id: $id)
            : $this->deliveryManService->findBy(params: ['id' => $id, 'seller_id' => $sellerId]);
    }

    private function invalidChatType(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.invalid_chat_type'));
    }
}
