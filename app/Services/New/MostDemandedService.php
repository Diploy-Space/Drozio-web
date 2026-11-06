<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\MostDemanded;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class MostDemandedService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }

    public const FILTER_ACTIVE_PRODUCT = 'active_product';

    public const STATUS_ACTIVE = 1;

    public const SORT_NEWEST_ID = 'newest-id';

    private const BANNER_DIRECTORY = 'most-demanded/';

    private const BANNER_FORMAT = 'webp';

    public function __construct(MostDemanded $mostDemanded)
    {
        parent::__construct($mostDemanded);
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
            ->when($searchValue, fn (Builder $builder) => $builder->whereHas(
                'product',
                fn (Builder $product) => $product->where('name', 'like', "%{$searchValue}%"),
            ));

        match ($sortBy) {
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            default => null,
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
        $query = $this->query()->with($relations);

        if (! empty($params[self::FILTER_ACTIVE_PRODUCT])) {
            $query->whereHas('product', fn (Builder $product) => $product->active()->businessModule(BusinessModule::RETAIL));
        }

        unset($params[self::FILTER_ACTIVE_PRODUCT]);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function create(array $data, ?UploadedFile $banner = null): ?Model
    {
        return $this->query()->create($data + [
            'banner' => $banner === null ? null : $this->upload(self::BANNER_DIRECTORY, self::BANNER_FORMAT, $banner),
        ]);
    }

    public function update(mixed $id, array $data, ?UploadedFile $banner = null): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        if ($banner !== null) {
            $data['banner'] = $this->updateFile(
                dir: self::BANNER_DIRECTORY,
                oldImage: $record->banner,
                format: self::BANNER_FORMAT,
                image: $banner,
            );
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        if ($status) {
            $this->query()->where('status', self::STATUS_ACTIVE)->update(['status' => 0]);
        }

        $record = $this->query()->find($id);

        return $record !== null && $record->update(['status' => (int) $status]);
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        return $record !== null && (bool) $record->delete();
    }
}
