<?php

namespace App\Services\New;

use App\Models\DeliveryMan;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class DeliveryManService extends BaseService
{
    use FileManagerTrait {
        update as private updateFile;
        delete as private deleteFile;
    }

    public const IMAGE_DIRECTORY = 'delivery-man/';

    public const STATUS_ACTIVE = 1;

    public const STATUS_INACTIVE = 0;

    public const SELLER_ADMIN = 0;

    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const SORT_NAME_ASC = 'a-z';

    public const SORT_MOST_DELIVERED = 'most-delivered';

    public const SORT_TOP_RATED = 'top-rated';

    public const FILTER_HAS_DELIVERED_ORDERS = 'has_delivered_orders';

    public const FILTER_DELIVERED_FOR_SELLER_ID = 'delivered_for_seller_id';

    public function __construct(DeliveryMan $deliveryMan)
    {
        parent::__construct($deliveryMan);
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $hasDeliveredOrders = ! empty($filters[self::FILTER_HAS_DELIVERED_ORDERS]);
        $deliveredForSellerId = $filters[self::FILTER_DELIVERED_FOR_SELLER_ID] ?? null;
        unset($filters[self::FILTER_HAS_DELIVERED_ORDERS], $filters[self::FILTER_DELIVERED_FOR_SELLER_ID]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($hasDeliveredOrders, fn (Builder $builder) => $builder->whereHas('deliveredOrders'))
            ->when($deliveredForSellerId !== null, fn (Builder $builder) => $builder->whereHas(
                'deliveredOrders',
                fn (Builder $order) => $order->where(['seller_is' => OrderService::SELLER_IS_SELLER, 'seller_id' => $deliveredForSellerId]),
            ))
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('f_name', 'like', "%{$searchValue}%")
                    ->orWhere('l_name', 'like', "%{$searchValue}%")
                    ->orWhereRaw('CONCAT(f_name, " ", l_name) like ?', ["%{$searchValue}%"])
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('full_phone', 'like', "%{$searchValue}%");
            }));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_OLDEST => $query->oldest('id'),
            self::SORT_NAME_ASC => $query->orderBy('f_name'),
            self::SORT_MOST_DELIVERED => $query->orderByDesc('delivered_orders_count'),
            self::SORT_TOP_RATED => $query->withAvg('review', 'rating')->orderByDesc('review_avg_rating'),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    /** @param array<int, UploadedFile> $identityImages */
    public function create(array $data, ?UploadedFile $image = null, array $identityImages = []): ?Model
    {
        return $this->query()->create([
            ...$data,
            ...(isset($data['password']) ? ['password' => bcrypt($data['password'])] : []),
            'image' => $image instanceof UploadedFile
                ? $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $image)
                : ($data['image'] ?? null),
            'identity_image' => $this->uploadedIdentityImages($identityImages),
        ]);
    }

    /**
     * @param  array<int, UploadedFile>  $images
     * @return array<int, array{image_name: string, storage: string}>
     */
    private function uploadedIdentityImages(array $images): array
    {
        $stored = [];

        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $stored[] = [
                    'image_name' => $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $image),
                    'storage' => $this->storageDisk(),
                ];
            }
        }

        return $stored;
    }

    /**
     * @param  array<int, UploadedFile>  $images
     * @return array<int, array{image_name: string, storage: string}>
     */
    private function replacedIdentityImages(Model $record, array $images): array
    {
        $this->removeIdentityImages($record->identity_image ?? []);

        return $this->uploadedIdentityImages($images);
    }

    private function removeIdentityImages(array $images): void
    {
        foreach ($images as $image) {
            $imageName = is_string($image) ? $image : ($image['image_name'] ?? null);

            if ($imageName !== null) {
                $this->deleteFile(filePath: self::IMAGE_DIRECTORY.$imageName);
            }
        }
    }

    /** @param array<int, UploadedFile> $identityImages */
    public function update(mixed $id, array $data, ?UploadedFile $image = null, array $identityImages = []): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(array_filter([
            'f_name' => $data['f_name'] ?? null,
            'l_name' => $data['l_name'] ?? null,
            'address' => $data['address'] ?? null,
            'email' => $data['email'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'phone' => $data['phone'] ?? null,
            'full_phone' => $data['full_phone'] ?? null,
            'identity_number' => $data['identity_number'] ?? null,
            'identity_type' => $data['identity_type'] ?? null,
            'identity_image' => $identityImages === [] ? null : $this->replacedIdentityImages($record, $identityImages),
            'bank_name' => $data['bank_name'] ?? null,
            'branch' => $data['branch'] ?? null,
            'account_no' => $data['account_no'] ?? null,
            'holder_name' => $data['holder_name'] ?? null,
            'app_language' => $data['app_language'] ?? null,
            'fcm_token' => $data['fcm_token'] ?? null,
            'is_active' => $data['is_active'] ?? null,
            'password' => isset($data['password']) ? bcrypt($data['password']) : null,
            'image' => $image === null
                ? null
                : $this->updateFile(dir: self::IMAGE_DIRECTORY, oldImage: $record->image, format: 'webp', image: $image),
        ], static fn ($value) => $value !== null));

        return $record->refresh();
    }

    public function updateOnlineStatus(mixed $id, bool $isOnline): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['is_online' => (int) $isOnline]);
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['is_active' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    /** @return array{active: int, inactive: int} */
    public function getStatusCounts(?string $searchValue = null, array $filters = []): array
    {
        $query = $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('f_name', 'like', "%{$searchValue}%")
                    ->orWhere('l_name', 'like', "%{$searchValue}%");
            }));

        $this->applyFilters($query, $filters);

        $counts = $query->select('is_active')
            ->selectRaw('count(*) as total')
            ->groupBy('is_active')
            ->pluck('total', 'is_active');

        return [
            'active' => (int) ($counts[self::STATUS_ACTIVE] ?? 0),
            'inactive' => (int) ($counts[self::STATUS_INACTIVE] ?? 0),
        ];
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        if ($record->image !== null) {
            $this->deleteFile(filePath: self::IMAGE_DIRECTORY.$record->image);
        }

        $this->removeIdentityImages($record->identity_image ?? []);

        return (bool) $record->delete();
    }

    public function countWithUnsettledWallet(int $sellerId): int
    {
        return $this->query()
            ->where('seller_id', $sellerId)
            ->whereHas('wallet', static function (Builder $query): Builder {
                return $query->where('current_balance', '!=', 0)
                    ->orWhere('cash_in_hand', '!=', 0);
            })
            ->count();
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }
}
