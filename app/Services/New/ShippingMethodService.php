<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\ShippingMethod;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ShippingMethodService extends BaseService
{
    public const SORT_NEWEST = 'newest';

    public const SORT_OLDEST = 'oldest';

    public const CREATOR_ADMIN = 'admin';

    public const CREATOR_SELLER = 'seller';

    public const IN_HOUSE_CREATOR_ID = 1;

    public const TYPE_ORDER_WISE = 'order_wise';

    public const SHIPPING_METHOD_INHOUSE = 'inhouse_shipping';

    public const SHIPPING_METHOD_SELLERWISE = 'sellerwise_shipping';

    public function __construct(ShippingMethod $shippingMethod)
    {
        parent::__construct($shippingMethod);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        return parent::query()->where('context', $this->businessModule());
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
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $pattern = '%'.addcslashes($searchValue, '\\%_').'%';

                $inner->where('title', 'like', $pattern)
                    ->orWhere('duration', 'like', $pattern);
            }));

        match ($sortBy) {
            self::SORT_OLDEST => $query->oldest('id'),
            default => $query->latest('id'),
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

    public function create(
        array $data,
        mixed $creatorId = null,
        string $creatorType = self::CREATOR_ADMIN,
        string $context = BusinessModule::RETAIL,
    ): ?Model {
        return $this->query()->create([
            ...$this->editableAttributes($data),
            'creator_id' => $creatorId,
            'creator_type' => $creatorType,
            'context' => $context,
            'status' => 1,
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($this->editableAttributes($data));

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        return $record === null ? false : (bool) $record->delete();
    }

    public function getCartShippingOptions(object|array $product, string $shippingType): ?Collection
    {
        if ($this->isAdminResponsibleFor(sellerIs: $product['added_by']) && $shippingType != self::TYPE_ORDER_WISE) {
            return null;
        }

        return $this->getActiveMethodsForSeller(sellerId: $product['user_id'], sellerIs: $product['added_by']);
    }

    public function getActiveMethodsForSeller(mixed $sellerId, ?string $sellerIs): Collection
    {
        $query = $this->query()->where(['status' => 1]);

        return $this->isAdminResponsibleFor(sellerIs: $sellerIs)
            ? $query->where(['creator_type' => self::CREATOR_ADMIN])->get()
            : $query->where(['creator_id' => $sellerId, 'creator_type' => self::CREATOR_SELLER])->get();
    }

    /** @return array{min: mixed, max: mixed} */
    public function getOrderWiseCostRange(object|array $product): array
    {
        $params = match (getWebConfig(name: $this->responsibilitySetting())) {
            self::SHIPPING_METHOD_INHOUSE => ['creator_id' => self::IN_HOUSE_CREATOR_ID, 'creator_type' => self::CREATOR_ADMIN, 'status' => 1],
            self::SHIPPING_METHOD_SELLERWISE => ['creator_id' => $product['user_id'], 'creator_type' => $product['added_by'], 'status' => 1],
            default => null,
        };

        if ($params === null) {
            return ['min' => null, 'max' => null];
        }

        return [
            'min' => $this->query()->where($params)->min('cost'),
            'max' => $this->query()->where($params)->max('cost'),
        ];
    }

    /** @return array{admin: int, seller: array<int, int>} */
    public function getActiveMethodCountsByCreator(): array
    {
        $counts = [self::CREATOR_ADMIN => 0, self::CREATOR_SELLER => []];

        $rows = $this->query()
            ->where('status', 1)
            ->whereIn('creator_type', [self::CREATOR_ADMIN, self::CREATOR_SELLER])
            ->groupBy('creator_type', 'creator_id')
            ->selectRaw('creator_type, creator_id, COUNT(*) AS method_count')
            ->get();

        foreach ($rows as $row) {
            if ($row->creator_type === self::CREATOR_ADMIN) {
                $counts[self::CREATOR_ADMIN] += (int) $row->method_count;

                continue;
            }

            $counts[self::CREATOR_SELLER][(int) $row->creator_id] = (int) $row->method_count;
        }

        return $counts;
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function hasDuplicateTitle(string $title, string $creatorType, mixed $creatorId = null, mixed $excludeId = null): bool
    {
        return $this->query()
            ->where('title', trim($title))
            ->where('creator_type', $creatorType)
            ->when($creatorId !== null, fn (Builder $builder) => $builder->where('creator_id', $creatorId))
            ->when($excludeId !== null, fn (Builder $builder) => $builder->where('id', '!=', $excludeId))
            ->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    protected function responsibilitySetting(): string
    {
        return 'shipping_method';
    }

    public function isInhouseShipping(): bool
    {
        return getWebConfig(name: $this->responsibilitySetting()) == self::SHIPPING_METHOD_INHOUSE;
    }

    private function isAdminResponsibleFor(?string $sellerIs): bool
    {
        return $this->isInhouseShipping() || $sellerIs == self::CREATOR_ADMIN;
    }

    /** @return array<string, mixed> */
    private function editableAttributes(array $data): array
    {
        return [
            'title' => $data['title'],
            'duration' => $data['duration'],
            'cost' => currencyConverter($data['cost']),
        ];
    }
}
