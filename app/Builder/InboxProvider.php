<?php

namespace App\Builder;

use App\Enums\BusinessModule;
use App\Enums\GlobalConstant;
use App\Events\ChattingEvent;
use App\Models\Chatting;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use App\Traits\FileManagerTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Builder\app\Contracts\InboxProvider as InboxProviderContract;
use Modules\Builder\app\ValueObjects\Storefront\ConversationDTO;
use Modules\Builder\app\ValueObjects\Storefront\MessageDTO;

class InboxProvider implements InboxProviderContract
{
    use FileManagerTrait;

    private const ATTACHMENT_PREVIEW = '📎 Attachment';

    private const ADMIN_CONVERSATION_ID = 0;

    public function conversations(int $customerId, ?int $storeId, ?string $search = null, int $limit = 30, int $offset = 1, ?string $openWith = null): array
    {
        $storeSellerId = $this->storeSellerId($storeId);
        $conversationIds = $this->reachableConversationIds($customerId, $storeSellerId);

        $openId = $this->parseOpenWith($openWith);
        if ($openId !== null && ($storeSellerId === null || $openId === $storeSellerId)
            && !in_array($openId, $conversationIds, true)) {
            $conversationIds[] = $openId;
        }

        $rows = collect($conversationIds)
            ->map(fn (int $conversationId) => $this->buildConversationRow(
                $customerId,
                $conversationId,
                openRequested: $conversationId === $storeSellerId || $conversationId === $openId,
            ))
            ->filter()
            ->values();

        if ($search) {
            $needle = mb_strtolower(trim($search));
            $rows = $rows->filter(fn (array $row) => str_contains(mb_strtolower($row['conversation']->name), $needle));
        }

        $sorted = $this->sortByRecency($rows);

        return [
            'conversations' => $sorted
                ->slice(max(0, ($offset - 1) * $limit), $limit)
                ->map(fn (array $row) => $row['conversation']->toArray())
                ->values()
                ->all(),
            'total'         => $sorted->count(),
        ];
    }

    public function conversation(int $customerId, ?int $storeId, int $conversationId, int $limit = 30, int $offset = 1): ?array
    {
        $storeSellerId = $this->storeSellerId($storeId);
        if ($storeSellerId !== null && $conversationId !== $storeSellerId) {
            return null;
        }

        $total = $this->threadQuery($customerId, $conversationId)->count();
        if ($total === 0 && !Shop::where('seller_id', $conversationId)->exists()) {
            return null;
        }

        $this->threadQuery($customerId, $conversationId)
            ->where('sent_by_customer', 0)
            ->where('seen_by_customer', 0)
            ->update(['seen_by_customer' => 1]);

        $page = max(1, $offset);
        $messages = $this->threadQuery($customerId, $conversationId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get()
            ->reverse()
            ->map(fn (Chatting $chat) => $this->mapMessage($chat)->toArray())
            ->values()
            ->all();

        $row = $this->buildConversationRow($customerId, $conversationId, openRequested: true);
        if ($row === null) {
            return null;
        }

        return array_merge($row['conversation']->toArray(), [
            'messages'   => $messages,
            'pagination' => [
                'page'    => $page,
                'perPage' => $limit,
                'total'   => $total,
                'hasMore' => $page * $limit < $total,
            ],
        ]);
    }

    public function sendMessage(int $customerId, ?int $storeId, array $input, ?array $files = null): array
    {
        $conversationId = $this->resolveRecipientConversationId($input, $storeId);
        if ($conversationId === null) {
            return ['error' => translate('Invalid conversation') ?: 'Invalid conversation.'];
        }

        $message = trim((string) ($input['message'] ?? ''));
        $attachments = $this->uploadAttachments($files ?? []);
        if ($message === '' && $attachments === []) {
            return ['error' => translate('Write a message or attach a file') ?: 'Write a message or attach a file.'];
        }

        $isAdminThread = $conversationId === self::ADMIN_CONVERSATION_ID;
        $shop = Shop::where('seller_id', $conversationId)->first();

        $chat = Chatting::create([
            'business_module'       => BusinessModule::RETAIL,
            'user_id'               => $customerId,
            'seller_id'             => $isAdminThread ? null : $conversationId,
            'admin_id'              => $isAdminThread ? self::ADMIN_CONVERSATION_ID : null,
            'shop_id'               => $shop?->id,
            'message'               => $message,
            'attachment'            => json_encode($attachments),
            'sent_by_customer'      => 1,
            'seen_by_customer'      => 1,
            'seen_by_seller'        => 0,
            'seen_by_admin'         => $isAdminThread ? 0 : null,
            'notification_receiver' => $isAdminThread ? 'admin' : 'seller',
            'created_at'            => now(),
        ]);

        $vendor = $isAdminThread ? null : Seller::with('shop')->find($conversationId);
        $customer = User::find($customerId);
        if ($vendor && $customer) {
            event(new ChattingEvent(
                key: 'message_from_customer',
                type: 'seller',
                userData: $vendor,
                messageForm: $customer,
            ));
        }

        return ['conversationId' => $conversationId, 'status' => (bool) $chat->exists];
    }

    public function mostRecentConversationId(int $customerId, ?int $storeId, ?string $openWith = null): ?int
    {
        $openId = $this->parseOpenWith($openWith);
        if ($openId !== null) {
            return $openId;
        }

        $storeSellerId = $this->storeSellerId($storeId);
        if ($storeSellerId !== null) {
            return $storeSellerId;
        }

        $latest = Chatting::query()
            ->businessModule(BusinessModule::RETAIL)
            ->where('user_id', $customerId)
            ->whereNull('delivery_man_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (!$latest) {
            return null;
        }

        return $latest->seller_id !== null
            ? (int) $latest->seller_id
            : ($latest->admin_id !== null ? self::ADMIN_CONVERSATION_ID : null);
    }

    private function storeSellerId(?int $storeId): ?int
    {
        if (!$storeId) {
            return null;
        }

        $sellerId = Shop::where('id', $storeId)->value('seller_id');

        return $sellerId === null ? null : (int) $sellerId;
    }

    private function resolveRecipientConversationId(array $input, ?int $storeId): ?int
    {
        $storeSellerId = $this->storeSellerId($storeId);

        $candidate = $this->intOrNull($input['conversationId'] ?? null);
        if ($candidate === null && ($input['receiverType'] ?? null) === 'vendor') {
            $candidate = $this->intOrNull($input['receiverId'] ?? null);
        }
        $candidate ??= $storeSellerId;

        if ($candidate === null || $candidate < 0) {
            return null;
        }
        if ($storeSellerId !== null && $candidate !== $storeSellerId) {
            return null;
        }

        return $candidate;
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function reachableConversationIds(int $customerId, ?int $storeSellerId): array
    {
        if ($storeSellerId === self::ADMIN_CONVERSATION_ID) {
            return [self::ADMIN_CONVERSATION_ID];
        }

        $conversationIds = Chatting::query()
            ->businessModule(BusinessModule::RETAIL)
            ->where('user_id', $customerId)
            ->whereNotNull('seller_id')
            ->when($storeSellerId !== null, fn ($query) => $query->where('seller_id', $storeSellerId))
            ->select('seller_id')
            ->distinct()
            ->pluck('seller_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($storeSellerId === null && $this->threadQuery($customerId, self::ADMIN_CONVERSATION_ID)->exists()) {
            $conversationIds[] = self::ADMIN_CONVERSATION_ID;
        }

        if ($storeSellerId !== null && !in_array($storeSellerId, $conversationIds, true)) {
            $conversationIds[] = $storeSellerId;
        }

        return $conversationIds;
    }

    private function parseOpenWith(?string $openWith): ?int
    {
        if ($openWith === null || $openWith === '' || $openWith === 'vendor' || \str_starts_with($openWith, 'dm:')) {
            return null;
        }

        return is_numeric($openWith) ? (int) $openWith : null;
    }

    private function threadQuery(int $customerId, int $conversationId): Builder
    {
        $query = Chatting::query()->businessModule(BusinessModule::RETAIL)->where('user_id', $customerId);

        return $conversationId === self::ADMIN_CONVERSATION_ID
            ? $query->whereNull('seller_id')->where('admin_id', self::ADMIN_CONVERSATION_ID)
            : $query->where('seller_id', $conversationId);
    }

    /** @return array{conversation: ConversationDTO, lastActivityAt: ?Carbon}|null */
    private function buildConversationRow(int $customerId, int $conversationId, bool $openRequested): ?array
    {
        $latest = $this->threadQuery($customerId, $conversationId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (!$latest && !$openRequested) {
            return null;
        }

        $shop = Shop::where('seller_id', $conversationId)->first();
        $name = $shop?->name ?? translate('Shop');

        $unread = $this->threadQuery($customerId, $conversationId)
            ->where('sent_by_customer', 0)
            ->where('seen_by_customer', 0)
            ->count();

        $lastActivityAt = $latest?->created_at ? Carbon::parse($latest->created_at) : null;

        $summary = [
            'id'          => $conversationId,
            'type'        => 'vendor',
            'name'        => $name,
            'avatar'      => $shop && !empty($shop->image) ? getStorageImages(path: $shop->image_full_url, type: 'shop') : null,
            'initials'    => $this->initials($name),
            'unread'      => $unread,
            'lastMessage' => $this->lastMessagePreview($latest),
            'lastTime'    => $lastActivityAt?->format('h:iA, d M y') ?? '',
        ];

        if (!$latest) {
            $summary['pristine'] = true;
        }

        return [
            'conversation'   => ConversationDTO::fromArray($summary),
            'lastActivityAt' => $lastActivityAt,
        ];
    }

    /**
     * @param Collection<int,array{conversation: ConversationDTO, lastActivityAt: ?Carbon}> $rows
     * @return Collection<int,array{conversation: ConversationDTO, lastActivityAt: ?Carbon}>
     */
    private function sortByRecency(Collection $rows): Collection
    {
        return $rows
            ->sortByDesc(fn (array $row) => $row['lastActivityAt']?->getTimestamp() ?? 0)
            ->values();
    }

    private function lastMessagePreview(?Chatting $latest): string
    {
        if (!$latest) {
            return '';
        }

        $message = trim((string) ($latest->message ?? ''));
        if ($message !== '') {
            return $message;
        }

        return $latest->attachment_full_url ? self::ATTACHMENT_PREVIEW : '';
    }

    private function mapMessage(Chatting $chat): MessageDTO
    {
        $attachments = collect($chat->attachment_full_url ?? [])
            ->map(fn ($image) => getStorageImages(path: $image, type: 'backend-basic'))
            ->filter()
            ->map(function (string $url) {
                $name = basename(parse_url($url, PHP_URL_PATH) ?: $url);
                $extension = '.' . strtolower(pathinfo($name, PATHINFO_EXTENSION));
                return [
                    'url'  => $url,
                    'name' => $name,
                    'type' => in_array($extension, GlobalConstant::IMAGE_EXTENSION, true) ? 'image' : 'file',
                ];
            })
            ->values()
            ->all();

        return MessageDTO::fromArray([
            'id'          => (int) $chat->id,
            'sender'      => $chat->sent_by_customer ? 'me' : 'them',
            'text'        => $chat->message !== null ? (string) $chat->message : null,
            'attachments' => $attachments,
            'time'        => $chat->created_at ? Carbon::parse($chat->created_at)->format('h:iA') : '',
            'createdAt'   => $chat->created_at ? Carbon::parse($chat->created_at)->toIso8601String() : null,
            'order'       => null,
        ]);
    }

    private function uploadAttachments(array $files): array
    {
        $storage = getWebConfig(name: 'storage_connection_type') ?? 'public';
        $out = [];
        foreach ($files as $file) {
            if (!$file) {
                continue;
            }
            $extension = '.' . strtolower((string) $file->getClientOriginalExtension());
            try {
                $fileName = in_array($extension, GlobalConstant::IMAGE_EXTENSION, true)
                    ? $this->upload(dir: 'chatting/', format: 'webp', image: $file)
                    : $this->fileUpload(dir: 'chatting/', format: $file->getClientOriginalExtension(), file: $file);
                $out[] = ['file_name' => $fileName, 'storage' => $storage];
            } catch (\Throwable) {
            }
        }
        return $out;
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        return $initials !== '' ? $initials : 'S';
    }
}
