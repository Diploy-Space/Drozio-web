<?php

namespace App\Http\Controllers\Web\v6;

use App\Events\ChattingEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Chatting\SendMessageRequest;
use App\Services\New\ChattingService;
use App\Services\New\DeliveryManService;
use App\Services\New\ShopService;
use App\Services\New\VendorService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChattingController extends Controller
{
    private const INBOX_VENDOR = 'vendor';

    private const INBOX_DELIVERY_MAN = 'delivery-man';

    private const INBOX_ADMIN = 'admin';

    private const NOTIFICATION_KEY = 'message_from_customer';

    private const DEFAULT_THEME = 'default';

    private const COUNTERPARTY_COLUMNS = [
        self::INBOX_VENDOR => 'seller_id',
        self::INBOX_DELIVERY_MAN => 'delivery_man_id',
        self::INBOX_ADMIN => 'admin_id',
    ];

    private const MESSAGE_RELATIONS = [
        self::INBOX_VENDOR => ['seller', 'shop'],
        self::INBOX_DELIVERY_MAN => ['deliveryMan'],
        self::INBOX_ADMIN => ['admin'],
    ];

    public function __construct(
        private readonly ChattingService $chattingService,
        private readonly VendorService $vendorService,
        private readonly ShopService $shopService,
        private readonly DeliveryManService $deliveryManService,
    ) {}

    private function defaultInboxType(): string
    {
        return getWebConfig(name: 'business_mode') === 'multi' ? self::INBOX_VENDOR : self::INBOX_DELIVERY_MAN;
    }

    public function index(?string $type = null): View
    {
        $type ??= $this->defaultInboxType();

        $counterpartyTypes = match ($type) {
            self::INBOX_VENDOR => [ChattingService::TYPE_SELLER, ChattingService::TYPE_ADMIN],
            self::INBOX_DELIVERY_MAN => [ChattingService::TYPE_DELIVERY_MAN],
            default => abort(404),
        };

        $customerId = (int) auth('customer')->id();

        $threads = $this->chattingService->getThreadList(
            viewerType: ChattingService::TYPE_CUSTOMER,
            viewerId: $customerId,
            counterpartyType: $counterpartyTypes,
            dataLimit: 'all',
            relations: $type === self::INBOX_VENDOR ? ['seller.shop'] : ['deliveryMan'],
        )->sortByDesc('id')->values();

        $this->attachUnseenMessageCounts(threads: $threads, customerId: $customerId);
        $this->attachThreadCounterparties(threads: $threads);

        $firstThread = $threads->first();
        $inboxType = $firstThread !== null && $firstThread->admin_id !== null ? self::INBOX_ADMIN : $type;
        $lastChatUser = $firstThread === null ? null : $this->findThreadCounterparty(thread: $firstThread, inboxType: $inboxType);

        $chattingMessages = [];

        if ($lastChatUser) {
            $counterpartyId = $this->getThreadCounterpartyId(counterparty: $lastChatUser, inboxType: $inboxType);
            $this->markConversationSeen(customerId: $customerId, inboxType: $inboxType, counterpartyId: $counterpartyId);
            $chattingMessages = $this->getConversation(customerId: $customerId, inboxType: $inboxType, counterpartyId: $counterpartyId, requiresShop: false);
        }

        return view(VIEW_FILE_NAMES['user_inbox'], [
            'isVendorInbox' => $type === self::INBOX_VENDOR,
            'userType' => $inboxType,
            'userData' => $lastChatUser ? $this->getUserData(type: $inboxType, user: $inboxType === self::INBOX_ADMIN ? self::INBOX_ADMIN : $lastChatUser) : '',
            'allChattingUsers' => $threads,
            'lastChatUser' => $lastChatUser,
            'chattingMessages' => $chattingMessages,
        ]);
    }

    public function getMessages(Request $request): JsonResponse
    {
        $counterparty = $this->resolveRequestedCounterparty(request: $request);

        if ($counterparty === null) {
            return response()->json([], 404);
        }

        $customerId = auth('customer')->id();

        if ($customerId !== null) {
            $this->markConversationSeen(customerId: (int) $customerId, inboxType: $counterparty['inboxType'], counterpartyId: $counterparty['id']);
        }

        return response()->json($this->renderConversation(customerId: $customerId, counterparty: $counterparty));
    }

    public function store(SendMessageRequest $request): JsonResponse
    {
        $disallowedFileError = $this->getDisallowedFileError(files: $request->file('file') ?? []);

        if ($disallowedFileError !== null) {
            return response()->json(['status' => 'error', 'message' => $disallowedFileError]);
        }

        $customer = auth('customer')->user();

        if ($customer === null) {
            return response()->json(['status' => 'error', 'message' => translate('Login first for next steps')], 401);
        }

        $counterparty = $this->resolveRequestedCounterparty(request: $request);

        if ($counterparty === null) {
            return response()->json(['status' => 'error', 'message' => translate('Not found')], 404);
        }

        $this->chattingService->sendMessage(
            viewerType: ChattingService::TYPE_CUSTOMER,
            viewerId: (int) $customer->id,
            counterpartyType: $counterparty['serviceType'],
            counterpartyId: (int) $counterparty['id'],
            message: $request['message'],
            attachments: array_merge($request->file('media') ?? [], $request->file('file') ?? []),
            shopId: $counterparty['shopId'],
        );

        if ($counterparty['notificationType'] !== null) {
            event(new ChattingEvent(
                key: self::NOTIFICATION_KEY,
                type: $counterparty['notificationType'],
                userData: $counterparty['recipient'],
                messageForm: $customer,
            ));
        }

        return response()->json($this->renderConversation(customerId: (int) $customer->id, counterparty: $counterparty));
    }

    private function attachUnseenMessageCounts(Collection $threads, int $customerId): void
    {
        $threads->each(function ($thread, int $index) use ($customerId): void {
            $thread->unseen_message_count = $index === 0 ? 0 : $this->countUnseenThreadMessages(thread: $thread, customerId: $customerId);
        });
    }

    private function attachThreadCounterparties(Collection $threads): void
    {
        $threads->each(function ($thread): void {
            [$thread->counterparty_id, $thread->counterparty_name, $thread->is_counterparty_verified] = match (true) {
                $thread->admin_id !== null => [ChattingService::ADMIN_THREAD_ID, getInHouseShopConfig(key: 'name'), isInHouseShopVerified()],
                $thread->seller_id !== null => [$thread->seller_id, $thread->seller?->shop?->name, (bool) $thread->seller?->hasVerifiedBadge()],
                default => [$thread->delivery_man_id, $thread->deliveryMan?->f_name, false],
            };
        });
    }

    private function countUnseenThreadMessages(mixed $thread, int $customerId): int
    {
        [$column, $counterpartyId] = match (true) {
            $thread->admin_id !== null => ['admin_id', $thread->admin_id],
            $thread->seller_id !== null => ['seller_id', $thread->seller_id],
            default => ['delivery_man_id', $thread->delivery_man_id],
        };

        if ($counterpartyId === null) {
            return 0;
        }

        return $this->chattingService->count(filters: [
            'user_id' => $customerId,
            $column => $counterpartyId,
            'sent_by_customer' => 0,
            'seen_by_customer' => 0,
        ]);
    }

    private function findThreadCounterparty(mixed $thread, string $inboxType): mixed
    {
        return match ($inboxType) {
            self::INBOX_ADMIN => ['id' => ChattingService::ADMIN_THREAD_ID],
            self::INBOX_VENDOR => $this->shopService->find(id: $thread->shop_id, relations: ['seller']),
            default => $this->deliveryManService->find(id: $thread->delivery_man_id),
        };
    }

    private function getThreadCounterpartyId(mixed $counterparty, string $inboxType): int|string
    {
        return $inboxType === self::INBOX_VENDOR ? $counterparty['seller_id'] : $counterparty['id'];
    }

    private function resolveRequestedCounterparty(Request $request): ?array
    {
        if ($request->has('delivery_man_id')) {
            $deliveryMan = $this->deliveryManService->find(id: $request['delivery_man_id']);

            return $deliveryMan === null ? null : [
                'inboxType' => self::INBOX_DELIVERY_MAN,
                'serviceType' => ChattingService::TYPE_DELIVERY_MAN,
                'notificationType' => 'delivery_man',
                'id' => $deliveryMan->id,
                'user' => $deliveryMan,
                'recipient' => $deliveryMan,
                'shopId' => null,
                'requiresShop' => false,
            ];
        }

        if ($request->has('vendor_id') && $request['vendor_id'] == ChattingService::ADMIN_THREAD_ID) {
            return [
                'inboxType' => self::INBOX_ADMIN,
                'serviceType' => ChattingService::TYPE_ADMIN,
                'notificationType' => null,
                'id' => ChattingService::ADMIN_THREAD_ID,
                'user' => self::INBOX_ADMIN,
                'recipient' => null,
                'shopId' => null,
                'requiresShop' => false,
            ];
        }

        $vendor = $request['vendor_id'] === null ? null : $this->vendorService->find(id: $request['vendor_id'], relations: ['shop']);

        if ($vendor?->shop === null) {
            return null;
        }

        $vendor->shop->setRelation('seller', $vendor);

        return [
            'inboxType' => self::INBOX_VENDOR,
            'serviceType' => ChattingService::TYPE_SELLER,
            'notificationType' => 'seller',
            'id' => $vendor->id,
            'user' => $vendor->shop,
            'recipient' => $vendor,
            'shopId' => $vendor->shop->id,
            'requiresShop' => true,
        ];
    }

    private function renderConversation(?int $customerId, array $counterparty): array
    {
        $chattingMessages = $customerId === null
            ? new Collection
            : $this->getConversation(customerId: $customerId, inboxType: $counterparty['inboxType'], counterpartyId: $counterparty['id'], requiresShop: $counterparty['requiresShop']);

        return [
            'userData' => $this->getUserData(type: $counterparty['inboxType'], user: $counterparty['user']),
            'chattingMessages' => view(VIEW_FILE_NAMES['user_inbox_message'], [
                'lastChatUser' => $counterparty['user'],
                'userType' => $counterparty['inboxType'],
                'chattingMessages' => $chattingMessages,
            ])->render(),
        ];
    }

    private function getConversation(int $customerId, string $inboxType, int|string $counterpartyId, bool $requiresShop): Collection
    {
        return $this->chattingService->getList(
            dataLimit: 'all',
            relations: self::MESSAGE_RELATIONS[$inboxType],
            filters: [
                'user_id' => $customerId,
                self::COUNTERPARTY_COLUMNS[$inboxType] => $counterpartyId,
                ChattingService::FILTER_HAS_SHOP => $requiresShop,
            ],
            sortBy: theme_root_path() === self::DEFAULT_THEME ? ChattingService::SORT_NEWEST_ID : ChattingService::SORT_OLDEST_ID,
        );
    }

    private function markConversationSeen(int $customerId, string $inboxType, int|string $counterpartyId): void
    {
        $this->chattingService->updateWhere(
            params: ['user_id' => $customerId, self::COUNTERPARTY_COLUMNS[$inboxType] => $counterpartyId],
            data: ['seen_by_customer' => 1],
        );
    }

    private function getUserData(string $type, mixed $user): array
    {
        return match ($type) {
            self::INBOX_VENDOR => [
                'name' => $user['name'],
                'phone' => $user['contact'],
                'image' => getStorageImages(path: $user->image_full_url, type: 'shop'),
                'temporary-close-status' => (int) checkVendorAbility(type: 'vendor', status: 'temporary_close', vendor: $user),
                'vacation-status' => (bool) checkVendorAbility(type: 'vendor', status: 'vacation_status', vendor: $user),
                'is_verified' => $user->hasVerifiedShopBadge(),
            ],
            self::INBOX_DELIVERY_MAN => [
                'name' => $user['f_name'].' '.$user['l_name'],
                'phone' => $user['country_code'].$user['phone'],
                'image' => getStorageImages(path: $user->image_full_url, type: 'avatar'),
                'temporary-close-status' => '',
                'vacation-status' => false,
                'is_verified' => false,
            ],
            default => [
                'name' => getInHouseShopConfig(key: 'name'),
                'phone' => '',
                'image' => getStorageImages(path: getInHouseShopConfig(key: 'image_full_url'), type: 'shop'),
                'temporary-close-status' => (int) checkVendorAbility(type: 'inhouse', status: 'temporary_close'),
                'vacation-status' => (bool) checkVendorAbility(type: 'inhouse', status: 'vacation_status'),
                'is_verified' => isInHouseShopVerified(),
            ],
        };
    }

    private function getDisallowedFileError(array $files): ?string
    {
        $disallowedExtensions = getDisallowedExtensionsListArray();

        foreach ($files as $file) {
            if (! in_array(strtolower($file->getClientOriginalExtension()), $disallowedExtensions)) {
                continue;
            }

            if (config('app.mode') == 'demo') {
                return translate('Uploading ZIP files is currently unavailable in demo mode');
            }

            return translate('Files with extensions like')
                .' ('.implode(', ', array_map(fn ($extension) => '.'.$extension, $disallowedExtensions)).') '
                .translate('Are not supported').'!';
        }

        return null;
    }
}
