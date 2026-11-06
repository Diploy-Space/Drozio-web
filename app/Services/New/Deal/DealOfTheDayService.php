<?php

namespace App\Services\New\Deal;

use App\Models\DealOfTheDay;
use App\Services\BaseService;
use App\Traits\SaleCacheTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class DealOfTheDayService extends BaseService
{
    use SaleCacheTrait;

    public const DISCOUNT_TYPE_AMOUNT = 'amount';

    public function __construct(DealOfTheDay $dealOfTheDay)
    {
        parent::__construct($dealOfTheDay);
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
            ->withoutGlobalScopes()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('title', 'like', "%{$searchValue}%")
                    ->orWhereIn('id', $this->translatedIds($searchValue, 'title'));
            }))
            ->latest('id');

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function getRunningDeal(array $relations = [], ?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();

        return $this->rememberSaleCache(
            'cache_deal_of_the_day_'.$locale,
            fn (): array => [
                $this->query()
                    ->with([
                        ...$relations,
                        'translations' => fn (MorphMany $translations) => $translations->where('locale', $locale)->where('key', 'title'),
                    ])
                    ->where('status', 1)
                    ->first(),
            ],
        )[0] ?? null;
    }

    public function find(mixed $id, array $relations = ['translations', 'product']): ?Model
    {
        return $this->query()->withoutGlobalScopes()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        $query = $this->query()->withoutGlobalScopes()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data, ?Model $product = null): ?Model
    {
        if ($product === null) {
            return null;
        }

        return DB::transaction(function () use ($data, $product): Model {
            $deal = $this->query()->create([
                ...$this->prepareAttributes($data, $product),
                'status' => 0,
            ]);

            $this->syncTranslations($deal->id, $data, ['title']);
            $this->forgetSaleCache();

            return $deal;
        });
    }

    public function update(mixed $id, array $data, ?Model $product = null): ?Model
    {
        $deal = $this->query()->find($id);

        if ($deal === null || $product === null) {
            return null;
        }

        return DB::transaction(function () use ($deal, $data, $product): Model {
            $deal->update($this->prepareAttributes($data, $product));

            $this->syncTranslations($deal->id, $data, ['title']);
            $this->forgetSaleCache();

            return $deal->refresh();
        });
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        $deal = $this->query()->find($id);

        if ($deal === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($deal, $status): bool {
            if ($status) {
                $this->query()->where('status', 1)->whereKeyNot($deal->getKey())->update(['status' => 0]);
            }

            $isUpdated = (bool) $deal->update(['status' => (int) $status]);

            $this->forgetSaleCache();

            return $isUpdated;
        });
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
        $deal = $this->query()->find($id);

        if ($deal === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($deal): bool {
            $this->deleteTranslations([$deal->id]);
            $isDeleted = (bool) $deal->delete();

            $this->forgetSaleCache();

            return $isDeleted;
        });
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

    private function prepareAttributes(array $data, Model $product): array
    {
        $columns = [
            'product_id' => $product->id,
            'discount' => $product->discount_type === self::DISCOUNT_TYPE_AMOUNT
                ? usdToDefaultCurrency(amount: $product->discount)
                : $product->discount,
            'discount_type' => $product->discount_type,
        ];

        if ($title = $this->defaultLocaleValue($data, 'title')) {
            $columns['title'] = $title;
        }

        return $columns;
    }
}
