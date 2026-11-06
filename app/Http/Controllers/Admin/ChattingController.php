<?php

namespace App\Http\Controllers\Admin;

use App\Events\ChattingEvent;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Chatting\SendMessageRequest;
use App\Services\New\ChattingService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryManService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Throwable;

class ChattingController extends BaseController
{
    private const LIST_VIEW = 'admin-views.chatting.index';

    private const MESSAGES_VIEW = 'admin-views.chatting.messages';

    private const NOTIFICATION_KEY = 'message_from_admin';

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
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $counterpartyType = self::COUNTERPARTY_TYPES[$type] ?? null;
        $emptyInbox = ['userType' => $type, 'conversations' => [], 'unreadTotals' => $this->getUnreadTotals()];

        if ($counterpartyType === null) {
            return view(self::LIST_VIEW, $emptyInbox);
        }

        $threads = $this->getThreadsWithCounterparty(counterpartyType: $counterpartyType);
        $lastChatUser = $this->getActiveCounterparty(
            threads: $threads,
            counterpartyType: $counterpartyType,
            requestedId: (int) $request?->query(self::REQUEST_KEYS[$counterpartyType]),
        );

        if ($lastChatUser === null) {
            return view(self::LIST_VIEW, $emptyInbox);
        }

        $this->markThreadSeen(counterpartyType: $counterpartyType, counterpartyId: $lastChatUser->id);

        return view(self::LIST_VIEW, [
            'userType' => $type,
            'unreadTotals' => $this->getUnreadTotals(),
            'conversations' => $this->getConversationRows(
                threads: $threads,
                counterpartyType: $counterpartyType,
                activeId: $lastChatUser->id,
            ),
            'activeUser' => $this->getCounterpartyViewData(user: $lastChatUser, counterpartyType: $counterpartyType),
            'chattingMessages' => $this->getMessageRows(
                messages: $this->getThreadMessages(counterpartyType: $counterpartyType, counterpartyId: $lastChatUser->id),
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

        $counterpartyId = (int) $request[self::REQUEST_KEYS[$counterpartyType]];
        $counterparty = $this->getCounterparty(counterpartyType: $counterpartyType, counterpartyId: $counterpartyId);

        if ($counterparty === null) {
            return response()->json([]);
        }

        $this->markThreadSeen(counterpartyType: $counterpartyType, counterpartyId: $counterpartyId);

        return response()->json($this->getRenderMessagesView(
            user: $counterparty,
            messages: $this->getThreadMessages(counterpartyType: $counterpartyType, counterpartyId: $counterpartyId),
            counterpartyType: $counterpartyType,
        ));
    }

    /**
     * @throws Throwable
     */
    public function addAdminMessage(SendMessageRequest $request): JsonResponse
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

        $counterpartyId = (int) $request[self::REQUEST_KEYS[$counterpartyType]];
        $counterparty = $this->getCounterparty(counterpartyType: $counterpartyType, counterpartyId: $counterpartyId);

        if ($counterparty === null) {
            return response()->json([]);
        }

        $this->chattingService->sendMessage(
            viewerType: ChattingService::TYPE_ADMIN,
            viewerId: ChattingService::ADMIN_THREAD_ID,
            counterpartyType: $counterpartyType,
            counterpartyId: $counterpartyId,
            message: $request['message'],
            attachments: $this->getAttachments(request: $request),
        );

        event(new ChattingEvent(
            key: self::NOTIFICATION_KEY,
            type: $this->getNotificationType(counterpartyType: $counterpartyType),
            userData: $counterparty,
            messageForm: $this->getMessageSender(),
        ));

        return response()->json($this->getRenderMessagesView(
            user: $counterparty,
            messages: $this->getThreadMessages(counterpartyType: $counterpartyType, counterpartyId: $counterpartyId),
            counterpartyType: $counterpartyType,
        ));
    }

    /**
     * @return array<string, int>
     */
    private function getUnreadTotals(): array
    {
        $totals = [];

        foreach (self::COUNTERPARTY_TYPES as $type => $counterpartyType) {
            $totals[$type] = array_sum($this->getUnreadCounts(counterpartyType: $counterpartyType));
        }

        return $totals;
    }

    /**
     * @return array<int|string, int>
     */
    private function getUnreadCounts(string $counterpartyType): array
    {
        return $this->chattingService->getUnseenCountsByCounterparty(
            viewerType: ChattingService::TYPE_ADMIN,
            viewerId: ChattingService::ADMIN_THREAD_ID,
            counterpartyType: $counterpartyType,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getConversationRows(Collection $threads, string $counterpartyType, int $activeId): array
    {
        $relation = self::COUNTERPARTY_RELATIONS[$counterpartyType];
        $unreadCounts = $this->getUnreadCounts(counterpartyType: $counterpartyType);

        return $threads->map(function (Model $thread) use ($relation, $unreadCounts, $activeId): array {
            $counterparty = $thread->{$relation};

            return [
                ...$this->getCounterpartyIdentity(user: $counterparty),
                'message' => $thread->message,
                'hasAttachment' => $thread->attachment_full_url !== [],
                'sentByViewer' => (bool) $thread->sent_by_admin,
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

    private function getActiveCounterparty(Collection $threads, string $counterpartyType, int $requestedId): ?Model
    {
        $relation = self::COUNTERPARTY_RELATIONS[$counterpartyType];

        $requested = $requestedId > 0
            ? $threads->first(fn (Model $thread): bool => (int) $thread->{$relation}->id === $requestedId)
            : null;

        return ($requested ?? $threads->first())?->{$relation};
    }

    private function getThreadsWithCounterparty(string $counterpartyType): Collection
    {
        $relation = self::COUNTERPARTY_RELATIONS[$counterpartyType];

        return $this->chattingService->getThreadList(
            viewerType: ChattingService::TYPE_ADMIN,
            viewerId: ChattingService::ADMIN_THREAD_ID,
            counterpartyType: $counterpartyType,
            dataLimit: 'all',
            relations: [$relation],
        )->filter(fn (Model $thread): bool => $thread->{$relation} !== null)->values();
    }

    private function getThreadMessages(string $counterpartyType, int $counterpartyId): Collection
    {
        return $this->chattingService->getMessageList(
            viewerType: ChattingService::TYPE_ADMIN,
            viewerId: ChattingService::ADMIN_THREAD_ID,
            counterpartyType: $counterpartyType,
            counterpartyId: $counterpartyId,
            dataLimit: 'all',
        );
    }

    private function markThreadSeen(string $counterpartyType, int $counterpartyId): void
    {
        $this->chattingService->markSeen(
            viewerType: ChattingService::TYPE_ADMIN,
            viewerId: ChattingService::ADMIN_THREAD_ID,
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

    private function getMessageSender(): object
    {
        return (object) [
            'f_name' => ChattingService::TYPE_ADMIN,
            'shop' => (object) ['name' => getInHouseShopConfig(key: 'name')],
        ];
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
                ? route('admin.people.customer.view', $user['id'])
                : route('admin.people.delivery-man.earning-statement-overview', $user['id']),
        ];
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
