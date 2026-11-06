<?php

namespace App\Services\New;

use App\Models\CartShipping;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CartShippingService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public function __construct(CartShipping $cartShipping)
    {
        parent::__construct($cartShipping);
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
                $inner->where('cart_group_id', 'like', "%{$searchValue}%")
                    ->when(is_numeric($searchValue), fn (Builder $query) => $query->orWhere('id', $searchValue));
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

    public function create(array $data): ?Model
    {
        return $this->query()->create(array_filter([
            'cart_group_id' => $data['cart_group_id'] ?? null,
            'shipping_method_id' => isset($data['shipping_method_id']) ? (int) $data['shipping_method_id'] : null,
            'shipping_cost' => isset($data['shipping_cost']) ? currencyConverter($data['shipping_cost']) : null,
        ], static fn ($value) => $value !== null));
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(array_filter([
            'cart_group_id' => $data['cart_group_id'] ?? null,
            'shipping_method_id' => isset($data['shipping_method_id']) ? (int) $data['shipping_method_id'] : null,
            'shipping_cost' => isset($data['shipping_cost']) ? currencyConverter($data['shipping_cost']) : null,
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

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    public function deleteBy(array $params): int
    {
        return $this->query()->where($params)->delete();
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        return $record === null ? false : (bool) $record->delete();
    }

    public function deleteByCartGroupIds(array $cartGroupIds): int
    {
        return $this->query()->whereIn('cart_group_id', $cartGroupIds)->delete();
    }

    public function saveForCartGroup(string $cartGroupId, mixed $shippingMethodId, mixed $shippingCost): Model
    {
        $cartShipping = $this->query()->where('cart_group_id', $cartGroupId)->first() ?? $this->model->newInstance();
        $cartShipping['cart_group_id'] = $cartGroupId;
        $cartShipping['shipping_method_id'] = $shippingMethodId;
        $cartShipping['shipping_cost'] = $shippingCost;
        $cartShipping->save();

        return $cartShipping;
    }

    public function getOrderWiseShippingCost(?string $cartGroupId, array $cartGroupIds, bool $isCheckedOnly): mixed
    {
        $query = $this->query()
            ->whereHas('cart', fn (Builder $cart) => $cart
                ->where('product_type', 'physical')
                ->whereHas('product', fn (Builder $product) => $product->active())
                ->when($isCheckedOnly, fn (Builder $checked) => $checked->where('is_checked', 1)))
            ->when($cartGroupId === null, fn (Builder $builder) => $builder->whereIn('cart_group_id', $cartGroupIds))
            ->when($cartGroupId !== null, fn (Builder $builder) => $builder->where('cart_group_id', $cartGroupId));

        if ($cartGroupId === null) {
            return $query->sum('shipping_cost');
        }

        return $query->first()?->shipping_cost ?? 0;
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
