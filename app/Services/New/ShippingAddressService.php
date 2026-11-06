<?php

namespace App\Services\New;

use App\Models\ShippingAddress;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingAddressService extends BaseService
{
    public const TYPE_HOME = 'home';

    public const SORT_LATEST = 'latest';

    public const TYPE_OFFICE = 'office';

    public const TYPE_OTHER = 'other';

    public const TYPE_OTHERS = 'others';

    public const TYPE_PERMANENT = 'permanent';

    public const SORT_OLDEST = 'oldest';

    public const SORT_OLDEST_ID = 'oldest-id';

    public const REASON_DELIVERY_COUNTRY_UNAVAILABLE = 'delivery_country_unavailable';

    public const REASON_DELIVERY_ZIP_UNAVAILABLE = 'delivery_zip_unavailable';

    private const CHECKOUT_ADDRESS_COLUMNS = [
        'customer_id',
        'is_guest',
        'contact_person_name',
        'address_type',
        'address',
        'city',
        'zip',
        'country',
        'phone',
        'email',
        'latitude',
        'longitude',
        'is_billing',
        'is_saved',
        'created_at',
        'updated_at',
    ];

    public function __construct(ShippingAddress $shippingAddress)
    {
        parent::__construct($shippingAddress);
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
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->where('is_saved', true)
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('contact_person_name', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->orWhere('city', 'like', "%{$searchValue}%")
                    ->when(is_numeric($searchValue), fn (Builder $query) => $query->orWhere('id', $searchValue));
            }));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_OLDEST_ID => $query->orderBy('id'),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
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

    /** @return array<int, string> */
    public static function addressTypes(): array
    {
        return [self::TYPE_HOME, self::TYPE_OFFICE, self::TYPE_OTHER];
    }

    public static function getAddressTypeLabel(?string $addressType): ?string
    {
        $labels = [
            self::TYPE_HOME => translate('Home'),
            self::TYPE_OFFICE => translate('Office'),
            self::TYPE_OTHER => translate('Other'),
            self::TYPE_OTHERS => translate('Others'),
            self::TYPE_PERMANENT => translate('Permanent'),
        ];

        return $addressType === null ? null : ($labels[strtolower($addressType)] ?? $addressType);
    }

    public function find(mixed $id, array $relations = [], ?string $ownerId = null, ?bool $isGuest = null): ?Model
    {
        return $this->ownedQuery(ownerId: $ownerId, isGuest: $isGuest)
            ->with($relations)
            ->find($id);
    }

    public function hasDeliveryLocation(Model $address): bool
    {
        return ! blank($address->country) && ! blank($address->zip);
    }

    public function getDeliveryRestrictionReason(bool $isCountryDeliverable, bool $isZipDeliverable): ?string
    {
        if (getWebConfig(name: 'delivery_country_restriction') && ! $isCountryDeliverable) {
            return self::REASON_DELIVERY_COUNTRY_UNAVAILABLE;
        }

        if (getWebConfig(name: 'delivery_zip_code_area_restriction') && ! $isZipDeliverable) {
            return self::REASON_DELIVERY_ZIP_UNAVAILABLE;
        }

        return null;
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'customer_id' => $data['customer_id'],
            'is_guest' => $data['is_guest'] ?? 0,
            'contact_person_name' => $this->contactPersonName($data),
            'email' => $data['email'] ?? null,
            'address_type' => $data['address_type'] ?? self::TYPE_HOME,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'zip' => $data['zip'] ?? $data['zip_code'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'phone' => $data['phone'] ?? null,
            'latitude' => $data['latitude'] ?? 0,
            'longitude' => $data['longitude'] ?? 0,
            'is_billing' => $data['is_billing'] ?? 0,
        ]);
    }

    public function createAccountAddress(?int $customerId, array $data): bool
    {
        return $this->query()->insert(['customer_id' => $customerId] + $this->accountAddressColumns(data: $data));
    }

    public function updateAccountAddress(mixed $id, array $data, ?string $ownerId = null): int
    {
        return $this->query()
            ->where('id', $id)
            ->when($ownerId !== null, fn (Builder $builder) => $builder->where('customer_id', $ownerId))
            ->update($this->accountAddressColumns(data: $data));
    }

    public function createCheckoutAddress(array $columns): int
    {
        return $this->query()->insertGetId(array_intersect_key($columns, array_flip(self::CHECKOUT_ADDRESS_COLUMNS)));
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(array_filter([
            'contact_person_name' => $this->contactPersonName($data),
            'email' => $data['email'] ?? null,
            'address_type' => $data['address_type'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'zip' => $data['zip'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'phone' => $data['phone'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_billing' => isset($data['is_billing']) ? (int) $data['is_billing'] : null,
        ], static fn ($value) => $value !== null));

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return false;
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    /** @param  array<int, mixed>  $addressIds */
    public function moveGuestAddressesToCustomer(int|string $guestId, int|string $customerId, array $addressIds): int
    {
        $ownedAddressIds = array_values(array_filter($addressIds, static fn (mixed $addressId): bool => ! blank($addressId)));

        if ($ownedAddressIds === []) {
            return 0;
        }

        return $this->query()
            ->where(['customer_id' => $guestId, 'is_guest' => 1])
            ->whereIn('id', $ownedAddressIds)
            ->update(['customer_id' => $customerId, 'is_guest' => 0]);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        return $record !== null && (bool) $record->delete();
    }

    private function ownedQuery(?string $ownerId, ?bool $isGuest): Builder
    {
        return $this->query()
            ->when($ownerId !== null, fn (Builder $builder) => $builder->where('customer_id', $ownerId))
            ->when($isGuest !== null, fn (Builder $builder) => $builder->where('is_guest', (int) $isGuest));
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    private function accountAddressColumns(array $data): array
    {
        return [
            'contact_person_name' => $data['name'] ?? null,
            'address_type' => Str::lower($data['address_type'] ?? ''),
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'zip' => $data['zip'] ?? null,
            'country' => $data['country'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_billing' => $data['is_billing'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function contactPersonName(array $data): ?string
    {
        if (isset($data['contact_person_name'])) {
            return $data['contact_person_name'];
        }

        $name = trim(($data['f_name'] ?? '').' '.($data['l_name'] ?? ''));

        return $name === '' ? null : $name;
    }
}
