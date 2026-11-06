<?php

namespace App\Http\Controllers\Vendor;

use App\Events\ChattingEvent;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\Chatting\SendMessageRequest;
use App\Services\New\ChattingService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryManService;
use App\Services\New\ShopService;
use App\Services\New\VendorService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Throwable;

class ChattingController extends BaseController
{
    private const INBOX_VIEW = 'vendor-views.chatting.inbox';

    private const CHAT_VIEW = 'vendor-views.chatting.index';

    private const MESSAGES_VIEW = 'vendor-views.chatting.messages';

    private const NOTIFICATION_KEY = 'message_from_seller';

    /** @var array<string, string> */
    private const COUNTERPARTY_TYPES = [
        'customer' => ChattingService::TYPE_CUSTOMER,
        'delivery-man' => ChattingService::TYPE_DELIVERY_MAN,
    ];

    /** @var array<string, string> */
    private const COUNTERPARTY_RELATIONS = [
        ChattingService::TYPE_CUSTOMER => 'customer',
        ChattingService::TYPE_DELIVERY_MAN => 'deliveryMan',
    ];

    /** @var array<string, string> */
    private const COUNTERPARTY_EMPTY_IMAGES = [
        'customer' => 'user',
        'delivery-man' => 'deliveryman',
    ];

    /** @var array<string, string> */
    private const REQUEST_KEYS = [
        ChattingService::TYPE_CUSTOMER => 'user_id',
        ChattingService::TYPE_DELIVERY_MAN => 'delivery_man_id',
    ];

    /** @var array<int, string> */
    private const WORD_ICON_EXTENSIONS = ['.pdf', '.doc', '.docx', '.txt'];

    public function __construct(
        private readonly ChattingService $chattingService,
        private readonly CustomerService $customerService,
        private readonly DeliveryManService $deliveryManService,
        private readonly ShopService $shopService,
        private readonly VendorService $vendorService,
    ) {}

    public function index(?Request $request = null, ?string $type = null): View
    {
        $vendorId = $this->getVendorId();

        return view(self::INBOX_VIEW, [
            'inboxSections' => array_map(
                fn (string $counterparty): array => $this->getInboxSection(vendorId: $vendorId, type: $counterparty),
                array_keys(self::COUNTERPARTY_TYPES),
            ),
        ]);
    }

    public function getChatView(Request $request, string $type): View|RedirectResponse
    {
        $counterpartyType = self::COUNTERPARTY_TYPES[$type] ?? null;

        if ($counterpartyType === null) {
            return redirect()->route('vendor.messages.index');
        }

        $vendorId = $this->getVendorId();
        $threads = $this->getThreadsWithCounterparty(vendorId: $vendorId, counterpartyType: $counterpartyType);
        $activeUser = $this->getRequestedCounterparty(
            threads: $threads,
            counterpartyType: $counterpartyType,
            requestedId: (int) $request->query(self::REQUEST_KEYS[$counterpartyType]),
        );

        if ($activeUser === null) {
            return redirect()->route('vendor.messages.index');
        }

        $this->markThreadSeen(vendorId: $vendorId, counterpartyType: $counterpartyType, counterpartyId: $activeUser->id);

        return view(self::CHAT_VIEW, [
            'userType' => $type,
            'listTitle' => $this->getCounterpartyTitle(type: $type),
            'searchPlaceholder' => $this->getSearchPlaceholder(type: $type),
            'inboxRoute' => route('vendor.messages.index'),
            'conversations' => $this->getConversationRows(
                threads: $threads,
                vendorId: $vendorId,
                type: $type,
                counterpartyType: $counterpartyType,
                activeId: $activeUser->id,
            ),
            'activeUser' => $this->getCounterpartyViewData(user: $activeUser, counterpartyType: $counterpartyType),
            'chattingMessages' => $this->getMessageRows(
                messages: $this->getThreadMessages(
                    vendorId: $vendorId,
                    counterpartyType: $counterpartyType,
                    counterpartyId: $activeUser->id,
                ),
                counterpartyType: $counterpartyType,
            ),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function getMessageByUser(Request $request): JsonResponse
    {
        $counterpartyType = $this->getRequestedCounterpartyType(request: $request);

        if ($counterpartyType === null) {
            return response()->json([]);
        }

        $vendorId = $this->getVendorId();
        $counterpartyId = (int) $request[self::REQUEST_KEYS[$counterpartyType]];
        $counterparty = $this->getCounterparty(counterpartyType: $counterpartyType, counterpartyId: $counterpartyId);

        if ($counterparty === null) {
            return response()->json([]);
        }

        $this->markThreadSeen(vendorId: $vendorId, counterpartyType: $counterpartyType, counterpartyId: $counterpartyId);

        return response()->json($this->getRenderMessagesView(
            user: $counterparty,
            messages: $this->getThreadMessages(vendorId: $vendorId, counterpartyType: $counterpartyType, counterpartyId: $counterpartyId),
            counterpartyType: $counterpartyType,
        ));
    }

    /**
     * @throws Throwable
     */
    public function addVendorMessage(SendMessageRequest $request): JsonResponse
    {
        $disallowedExtensionResponse = $this->getDisallowedExtensionResponse(
            files: $this->getUploadedFiles(request: $request, key: 'file'),
        );

        if ($disallowedExtensionResponse !== null) {
            return $disallowedExtensionResponse;
        }

        $counterpartyType = $this->getRequestedCounterpartyType(request: $request);

        if ($counterpartyType === null) {
            return response()->json([]);
        }

        $vendorId = $this->getVendorId();
        $counterpartyId = (int) $request[self::REQUEST_KEYS[$counterpartyType]];
        $counterparty = $this->getCounterparty(counterpartyType: $counterpartyType, counterpartyId: $counterpartyId);

        if ($counterparty === null) {
            return response()->json([]);
        }

        $this->chattingService->sendMessage(
            viewerType: ChattingService::TYPE_SELLER,
            viewerId: $vendorId,
            counterpartyType: $counterpartyType,
            counterpartyId: $counterpartyId,
            message: $request['message'],
            attachments: $this->getAttachments(request: $request),
            shopId: $this->shopService->findBy(params: ['seller_id' => $vendorId])?->id,
        );

        event(new ChattingEvent(
            key: self::NOTIFICATION_KEY,
            type: $this->getNotificationType(counterpartyType: $counterpartyType),
            userData: $counterparty,
            messageForm: $this->vendorService->find(id: $vendorId),
        ));

        return response()->json($this->getRenderMessagesView(
            user: $counterparty,
            messages: $this->getThreadMessages(vendorId: $vendorId, counterpartyType: $counterpartyType, counterpartyId: $counterpartyId),
            counterpartyType: $counterpartyType,
        ));
    }

    public function getNewNotification(): JsonResponse
    {
        $filters = $this->getUnseenNotificationFilters(vendorId: $this->getVendorId());
        $newMessagesExist = $this->chattingService->getList(dataLimit: 'all', filters: $filters)->count();

        $this->chattingService->updateWhere(params: $filters, data: ['seen_notification' => 1]);

        return response()->json([
            'newMessagesExist' => $newMessagesExist,
            'message' => $newMessagesExist > 1
                ? $newMessagesExist.' '.translate('New Message')
                : translate('New Message'),
        ]);
    }

    private function getVendorId(): int
    {
        return (int) auth('seller')->id();
    }

    /** @return array<string, mixed> */
    private function getUnseenNotificationFilters(int $vendorId): array
    {
        return [
            'seller_id' => $vendorId,
            'seen_by_seller' => 0,
            'notification_receiver' => ChattingService::TYPE_SELLER,
            'seen_notification' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getInboxSection(int $vendorId, string $type): array
    {
        $counterpartyType = self::COUNTERPARTY_TYPES[$type];
        $conversations = $this->getConversationRows(
            threads: $this->getThreadsWithCounterparty(vendorId: $vendorId, counterpartyType: $counterpartyType),
            vendorId: $vendorId,
            type: $type,
            counterpartyType: $counterpartyType,
            activeId: 0,
        );

        return [
            'type' => $type,
            'title' => $this->getCounterpartyTitle(type: $type),
            'searchPlaceholder' => $this->getSearchPlaceholder(type: $type),
            'emptyImage' => self::COUNTERPARTY_EMPTY_IMAGES[$type],
            'emptyText' => $type === 'delivery-man'
                ? 'No delivery man conversation yet'
                : 'No customer conversation yet',
            'unreadCount' => array_sum(array_column($conversations, 'unreadCount')),
            'conversations' => $conversations,
        ];
    }

    private function getCounterpartyTitle(string $type): string
    {
        return $type === 'delivery-man' ? translate('Delivery men') : translate('Customers');
    }

    private function getSearchPlaceholder(string $type): string
    {
        return $type === 'delivery-man' ? translate('Search delivery men') : translate('Search customers');
    }

    /**
     * @return array<int|string, int>
     */
    private function getUnreadCounts(int $vendorId, string $counterpartyType): array
    {
        return $this->chattingService->getUnseenCountsByCounterparty(
            viewerType: ChattingService::TYPE_SELLER,
            viewerId: $vendorId,
            counterpartyType: $counterpartyType,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getConversationRows(Collection $threads, int $vendorId, string $type, string $counterpartyType, int $activeId): array
    {
        $relation = self::COUNTERPARTY_RELATIONS[$counterpartyType];
        $requestKey = self::REQUEST_KEYS[$counterpartyType];
        $unreadCounts = $this->getUnreadCounts(vendorId: $vendorId, counterpartyType: $counterpartyType);

        return $threads->map(function (Model $thread) use ($relation, $requestKey, $type, $unreadCounts, $activeId): array {
            $counterparty = $thread->{$relation};

            return [
                ...$this->getCounterpartyIdentity(user: $counterparty),
                'type' => $type,
                'chatRoute' => route('vendor.messages.chat', ['type' => $type, $requestKey => $counterparty->id]),
                'message' => $thread->message,
                'hasAttachment' => $thread->attachment_full_url !== [],
                'sentByViewer' => (bool) $thread->sent_by_seller,
                'time' => $thread->created_at,
                'unreadCount' => $unreadCounts[$counterparty->id] ?? 0,
                'isActive' => $counterparty->id === $activeId,
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMessageRows(Collection $messages, string $counterpartyType): array
    {
        $rows = [];
        $ordered = $messages->values();

        foreach ($ordered as $index => $message) {
            $isIncoming = (bool) ($message->sent_by_customer || $message->sent_by_delivery_man);
            $older = $ordered->get($index + 1);
            $newer = $ordered->get($index - 1);
            $attachments = $this->chattingService->getAttachmentGroups(model: $message);

            $rows[] = [
                'id' => $message->id,
                'text' => $message->message,
                'isIncoming' => $isIncoming,
                'isSeen' => (bool) ($counterpartyType === ChattingService::TYPE_CUSTOMER
                    ? $message->seen_by_customer
                    : $message->seen_by_delivery_man),
                'time' => $message->created_at->format('h:i A'),
                'fullTime' => $this->getMessageTimestamp(sentAt: $message->created_at),
                'dateLabel' => $this->getMessageDateLabel(sentAt: $message->created_at),
                'startsDay' => $older === null || ! $older->created_at->isSameDay($message->created_at),
                'showAvatar' => $isIncoming && ($newer === null || ! ($newer->sent_by_customer || $newer->sent_by_delivery_man)),
                'documents' => $this->getDocumentRows(documents: $attachments['documents']),
                'media' => $this->getMediaRows(media: $attachments['media']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function getDocumentRows(array $documents): array
    {
        return array_map(static fn (array $document): array => [
            ...$document,
            'icon' => dynamicAsset(path: 'public/assets/back-end/img/'.(
                in_array($document['extension'], self::WORD_ICON_EXTENSIONS, true) ? 'word-icon' : 'default-icon'
            ).'.png'),
        ], $documents);
    }

    /**
     * @param  array<int, array<string, mixed>>  $media
     * @return array<int, array<string, mixed>>
     */
    private function getMediaRows(array $media): array
    {
        return array_map(static fn (array $file): array => [
            ...$file,
            'preview' => getStorageImages(path: $file['link'], type: 'backend-basic'),
        ], $media);
    }

    private function getMessageDateLabel(Carbon $sentAt): string
    {
        return match (true) {
            $sentAt->isToday() => translate('Today'),
            $sentAt->isYesterday() => translate('Yesterday'),
            default => $sentAt->format('M d, Y'),
        };
    }

    private function getMessageTimestamp(Carbon $sentAt): string
    {
        return match (true) {
            $sentAt->isToday() => translate('Today').' '.$sentAt->format('h:i A'),
            $sentAt->isYesterday() => translate('Yesterday').' '.$sentAt->format('h:i A'),
            $sentAt->lt(now()->subDays(6)) => $sentAt->format('M d, Y h:i A'),
            default => $sentAt->format('l h:i A'),
        };
    }

    /**
     * @return array<string, string>
     */
    private function getCounterpartyIdentity(Model $user): array
    {
        return [
            'id' => $user['id'],
            'name' => trim($user['f_name'].' '.$user['l_name']),
            'phone' => trim($user['country_code'].$user['phone']),
            'image' => getStorageImages(path: $user->image_full_url, type: 'backend-profile'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getCounterpartyViewData(Model $user, string $counterpartyType): array
    {
        return [
            ...$this->getCounterpartyIdentity(user: $user),
            'detailsRoute' => $counterpartyType === ChattingService::TYPE_CUSTOMER
                ? route('vendor.retail.orders.list', ['status' => 'all', 'customer_id' => $user['id']])
                : '#',
        ];
    }

    private function getRequestedCounterparty(Collection $threads, string $counterpartyType, int $requestedId): ?Model
    {
        if ($requestedId < 1) {
            return null;
        }

        $relation = self::COUNTERPARTY_RELATIONS[$counterpartyType];

        return $threads->first(
            fn (Model $thread): bool => (int) $thread->{$relation}->id === $requestedId
        )?->{$relation};
    }

    private function getThreadsWithCounterparty(int $vendorId, string $counterpartyType): Collection
    {
        $relation = self::COUNTERPARTY_RELATIONS[$counterpartyType];

        return $this->chattingService->getThreadList(
            viewerType: ChattingService::TYPE_SELLER,
            viewerId: $vendorId,
            counterpartyType: $counterpartyType,
            dataLimit: 'all',
            relations: [$relation],
        )->filter(fn (Model $thread): bool => $thread->{$relation} !== null)->values();
    }

    private function getThreadMessages(int $vendorId, string $counterpartyType, int $counterpartyId): Collection
    {
        return $this->chattingService->getMessageList(
            viewerType: ChattingService::TYPE_SELLER,
            viewerId: $vendorId,
            counterpartyType: $counterpartyType,
            counterpartyId: $counterpartyId,
            dataLimit: 'all',
        );
    }

    private function markThreadSeen(int $vendorId, string $counterpartyType, int $counterpartyId): void
    {
        $this->chattingService->markSeen(
            viewerType: ChattingService::TYPE_SELLER,
            viewerId: $vendorId,
            counterpartyType: $counterpartyType,
            counterpartyId: $counterpartyId,
        );
    }

    private function getCounterparty(string $counterpartyType, int $counterpartyId): ?Model
    {
        return $counterpartyType === ChattingService::TYPE_CUSTOMER
            ? $this->customerService->find(id: $counterpartyId)
            : $this->deliveryManService->find(id: $counterpartyId);
    }

    private function getRequestedCounterpartyType(Request $request): ?string
    {
        foreach (self::REQUEST_KEYS as $counterpartyType => $requestKey) {
            if ($request->has($requestKey)) {
                return $counterpartyType;
            }
        }

        return null;
    }

    private function getNotificationType(string $counterpartyType): string
    {
        return $counterpartyType === ChattingService::TYPE_CUSTOMER ? 'customer' : 'delivery_man';
    }

    /** @return array<int, UploadedFile> */
    private function getAttachments(Request $request): array
    {
        return array_merge(
            $this->getUploadedFiles(request: $request, key: 'media'),
            $this->getUploadedFiles(request: $request, key: 'file'),
        );
    }

    /** @return array<int, UploadedFile> */
    private function getUploadedFiles(Request $request, string $key): array
    {
        $files = $request->file($key);

        return match (true) {
            $files === null => [],
            is_array($files) => $files,
            default => [$files],
        };
    }

    /** @param array<int, UploadedFile> $files */
    private function getDisallowedExtensionResponse(array $files): ?JsonResponse
    {
        $disallowedExtensions = getDisallowedExtensionsListArray();

        foreach ($files as $file) {
            if (! in_array(strtolower($file->getClientOriginalExtension()), $disallowedExtensions)) {
                continue;
            }

            return response()->json([
                'status' => 'error',
                'message' => config('app.mode') === 'demo'
                    ? translate('Uploading ZIP files is currently unavailable in demo mode')
                    : translate('Files with extensions like').
                        ' ('.implode(', ', array_map(fn ($extension) => '.'.$extension, $disallowedExtensions)).') '.
                        translate('Are not supported').'!',
            ]);
        }

        return null;
    }

    /**
     * @return array{userData: array<string, string>, chattingMessages: string}
     *
     * @throws Throwable
     */
    private function getRenderMessagesView(Model $user, Collection $messages, string $counterpartyType): array
    {
        $counterparty = $this->getCounterpartyViewData(user: $user, counterpartyType: $counterpartyType);

        return [
            'userData' => $counterparty,
            'chattingMessages' => view(self::MESSAGES_VIEW, [
                'counterparty' => $counterparty,
                'chattingMessages' => $this->getMessageRows(messages: $messages, counterpartyType: $counterpartyType),
            ])->render(),
        ];
    }
}
