<?php

namespace App\Services\New\Clearance;

use App\Models\StockClearanceSetup;
use App\Services\BaseService;
use App\Traits\SaleCacheTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StockClearanceSetupService extends BaseService
{
    use SaleCacheTrait;

    public const SETUP_BY_ADMIN = 'admin';

    public const SETUP_BY_VENDOR = 'vendor';

    public const OFFER_ACTIVE_ALWAYS = 'always';

    public const OFFER_ACTIVE_SPECIFIC_TIME = 'specific_time';

    public const FILTER_RUNNING = 'running';

    public const SORT_NEWEST = 'newest';

    public const SORT_OLDEST = 'oldest';

    /** @var array<int, string> */
    private const WRITABLE_COLUMNS = [
        'show_in_homepage',
        'show_in_homepage_once',
        'show_in_shop',
    ];

    /** @var array<int, string> */
    private const SEO_FIELDS = [
        'meta_title',
        'meta_description',
        'meta_index',
        'meta_no_follow',
        'meta_no_image_index',
        'meta_no_archive',
        'meta_no_snippet',
        'meta_max_snippet',
        'meta_max_video_preview',
        'meta_max_image_preview',
    ];

    private const DURATION_FORMAT = 'm/d/Y h:i:s A';

    public function __construct(StockClearanceSetup $stockClearanceSetup)
    {
        parent::__construct($stockClearanceSetup);
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
        $isRunning = ! empty($filters[self::FILTER_RUNNING]);
        unset($filters[self::FILTER_RUNNING]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->whereHas(
                'shop',
                fn (Builder $shop) => $shop->where('name', 'like', "%{$searchValue}%"),
            ))
            ->when($isRunning, fn (Builder $builder) => $builder
                ->whereDate('duration_start_date', '<=', Carbon::now())
                ->whereDate('duration_end_date', '>=', Carbon::now()));

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

    public function find(mixed $id, array $relations = ['seo']): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = ['seo']): ?Model
    {
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data): ?Model
    {
        return DB::transaction(function () use ($data): Model {
            $setup = $this->query()->create($this->setupAttributes($data));

            $this->forgetSaleCache();

            return $setup;
        });
    }

    public function update(mixed $id, array $data, ?UploadedFile $metaImage = null): ?Model
    {
        $setup = $this->query()->find($id);

        if ($setup === null) {
            return null;
        }

        return DB::transaction(function () use ($setup, $data, $metaImage): Model {
            $columns = array_intersect_key($data, array_flip(self::WRITABLE_COLUMNS));

            if ($columns !== []) {
                $setup->update($columns);
            }

            if ($metaImage !== null || array_intersect_key($data, array_flip(self::SEO_FIELDS)) !== []) {
                $this->syncSeoMetaInfo($setup, $data, $metaImage);
            }

            $this->forgetSaleCache();

            return $setup->refresh();
        });
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        $setup = $this->query()->find($id);

        if ($setup === null) {
            return false;
        }

        $isUpdated = (bool) $setup->update(['is_active' => (int) $status]);
        $this->forgetSaleCache();

        return $isUpdated;
    }

    public function updateWhere(array $params, array $data): int
    {
        $affected = $this->query()->where($params)->update($data);

        $this->forgetSaleCache();

        return $affected;
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) DB::transaction(function () use ($params, $data): bool {
            $this->query()->updateOrCreate($params, $this->setupAttributes($data));

            $this->forgetSaleCache();

            return true;
        });
    }

    public function delete(mixed $id): bool
    {
        $setup = $this->query()->find($id);

        if ($setup === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($setup): bool {
            $this->deleteSeoMetaInfo($setup->id);

            $isDeleted = (bool) $setup->delete();

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

    private function setupAttributes(array $data): array
    {
        [$durationStart, $durationEnd] = $this->splitDuration($data['clearance_sale_duration'] ?? null);
        [$offerStart, $offerEnd] = $this->splitOfferRange($data['offer_active_range'] ?? null);
        $offerActiveTime = $data['offer_active_time'] ?? self::OFFER_ACTIVE_ALWAYS;
        $isSpecificTime = $offerActiveTime === self::OFFER_ACTIVE_SPECIFIC_TIME;

        $columns = [
            'shop_id' => $data['shop_id'] ?? null,
            'discount_type' => $data['discount_type'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? null,
            'offer_active_time' => $offerActiveTime,
            'offer_active_range_start' => $isSpecificTime ? $offerStart : null,
            'offer_active_range_end' => $isSpecificTime ? $offerEnd : null,
            'duration_start_date' => $durationStart,
            'duration_end_date' => $durationEnd,
        ];

        if (array_key_exists('show_in_homepage', $data)) {
            $columns['show_in_homepage'] = $data['show_in_homepage'];
        }

        return $columns;
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} */
    private function splitDuration(?string $duration): array
    {
        $dates = explode(' - ', (string) $duration);

        return [
            empty($dates[0]) ? null : Carbon::createFromFormat(self::DURATION_FORMAT, $dates[0]),
            empty($dates[1]) ? null : Carbon::createFromFormat(self::DURATION_FORMAT, $dates[1]),
        ];
    }

    /** @return array{0: ?string, 1: ?string} */
    private function splitOfferRange(?string $range): array
    {
        if (empty($range)) {
            return [null, null];
        }

        $times = explode(' - ', $range);

        return [
            empty($times[0]) ? null : date('H:i:s', strtotime($times[0])),
            empty($times[1]) ? null : date('H:i:s', strtotime($times[1])),
        ];
    }
}
