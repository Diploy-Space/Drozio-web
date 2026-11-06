<?php

namespace App\Models;

use App\Enums\BusinessModule;
use App\Traits\StorageTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id Primary
 * @property int $user_id
 * @property int $seller_id
 * @property int $admin_id
 * @property int $delivery_man_id
 * @property string $message
 * @property string attachment
 * @property bool $sent_by_customer
 * @property bool $sent_by_seller
 * @property bool $sent_by_admin
 * @property bool $sent_by_delivery_man
 * @property bool $seen_by_customer
 * @property bool $seen_by_seller
 * @property bool $seen_by_admin
 * @property bool $seen_by_delivery_man
 * @property bool $status
 * @property string $notification_receiver
 * @property bool $seen_notification
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int $shop_id
 */
class Chatting extends Model
{
    use StorageTrait;

    private const ATTACHMENT_DIRECTORY = 'chatting';

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'seller_id' => 'integer',
        'admin_id' => 'integer',
        'message' => 'string',
        'delivery_man_id' => 'integer',
        'sent_by_customer' => 'boolean',
        'sent_by_seller' => 'boolean',
        'sent_by_admin' => 'boolean',
        'sent_by_delivery_man' => 'boolean',
        'seen_by_customer' => 'boolean',
        'seen_by_seller' => 'boolean',
        'seen_by_admin' => 'boolean',
        'seen_by_delivery_man' => 'boolean',
        'status' => 'boolean',
        'notification_receiver' => 'string',
        'seen_notification' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'shop_id' => 'integer',
    ];

    protected $fillable = [
        'business_module',
        'user_id',
        'seller_id',
        'admin_id',
        'delivery_man_id',
        'message',
        'attachment',
        'sent_by_customer',
        'sent_by_seller',
        'sent_by_admin',
        'sent_by_delivery_man',
        'seen_by_customer',
        'seen_by_seller',
        'seen_by_admin',
        'seen_by_delivery_man',
        'status',
        'notification_receiver',
        'seen_notification',
        'shop_id',
    ];

    public function scopeBusinessModule($query, string $module = BusinessModule::RETAIL)
    {
        return $query->where('business_module', $module);
    }

    protected $guarded = [];

    public function sellerInfo(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function getAttachmentFullUrlAttribute(): array
    {
        return array_map(
            fn (array $file): array => $this->storageLink(self::ATTACHMENT_DIRECTORY, $file['file_name'], $file['storage']),
            $this->attachmentFiles(),
        );
    }

    public function attachmentFullUrlWithSize(): array
    {
        return array_map(function (array $file): array {
            $link = $this->storageLink(self::ATTACHMENT_DIRECTORY, $file['file_name'], $file['storage']);

            return $link + ['size' => $link['status'] === 200 ? $this->attachmentSize(file: $file) : 0];
        }, $this->attachmentFiles());
    }

    private function attachmentSize(array $file): int
    {
        $disk = $file['storage'] === 's3' && config('filesystems.disks.default') === 's3' ? 's3' : 'public';

        return (int) Storage::disk($disk)->size(self::ATTACHMENT_DIRECTORY.'/'.$file['file_name']);
    }

    /** @return array<int, array{file_name: string, storage: string}> */
    private function attachmentFiles(): array
    {
        $files = [];

        foreach (json_decode((string) $this->attachment) ?: [] as $item) {
            $item = (array) $item;
            $fileName = $item['file_name'] ?? $item['image_name'] ?? (is_string($item[0] ?? null) ? $item[0] : null);

            if (is_string($fileName) && $fileName !== '') {
                $files[] = ['file_name' => $fileName, 'storage' => $item['storage'] ?? 'public'];
            }
        }

        return $files;
    }
}
