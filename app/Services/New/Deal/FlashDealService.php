<?php

namespace App\Services\New\Deal;

use App\Enums\DealType;
use App\Models\FlashDeal;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use App\Traits\SaleCacheTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FlashDealService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }
    use SaleCacheTrait;

    public const FILTER_RUNNING = 'running';

    public const PRIORITY_TYPE = 'flash_deal_priority';

    protected const IMAGE_DIRECTORY = 'deal/';

    protected const DEFAULT_BANNER = 'def.webp';

    public function __construct(FlashDeal $flashDeal)
    {
        parent::__construct($flashDeal);
    }

    protected function dealType(): string
    {
        return DealType::FLASH;
    }

    public function getPriorityType(): string
    {
        return self::PRIORITY_TYPE;
    }

    protected function query(): Builder
    {
        return parent::query()->withoutGlobalScopes()->dealType($this->dealType());
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
                $inner->where('title', 'like', "%{$searchValue}%")
                    ->orWhereIn('id', $this->translatedIds($searchValue, 'title'));
            }))
            ->latest('id');

        if (! empty($filters[self::FILTER_RUNNING])) {
            $query->whereDate('start_date', '<=', now())->whereDate('end_date', '>=', now());
        }

        unset($filters[self::FILTER_RUNNING]);

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function getRunningDeals(array $relations = [], array $withCount = [], ?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();

        return $this->rememberSaleCache(
            'cache_running_deals_'.$this->dealType().'_'.$locale,
            fn (): Collection => $this->getList(
                dataLimit: 'all',
                relations: [
                    ...$relations,
                    'translations' => fn (MorphMany $translations) => $translations->where('locale', $locale)->where('key', 'title'),
                ],
                withCount: $withCount,
                filters: ['status' => 1, self::FILTER_RUNNING => true],
            ),
        );
    }

    protected function saleCacheBoundary(): ?Carbon
    {
        $now = Carbon::now();

        $boundaries = $this->query()
            ->where('status', 1)
            ->whereDate('end_date', '>=', $now->toDateString())
            ->get(['start_date', 'end_date'])
            ->flatMap(fn (Model $deal): array => [
                Carbon::parse($deal->start_date)->startOfDay(),
                Carbon::parse($deal->end_date)->endOfDay(),
            ])
            ->filter(fn (Carbon $boundary): bool => $boundary->greaterThan($now));

        return $boundaries->isEmpty() ? null : $boundaries->min();
    }

    public function find(mixed $id, array $relations = ['translations', 'seo']): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
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

    public function create(array $data, ?UploadedFile $image = null, ?UploadedFile $metaImage = null): ?Model
    {
        return DB::transaction(function () use ($data, $image, $metaImage): Model {
            $title = $this->defaultLocaleValue($data, 'title');

            $deal = $this->query()->create([
                ...$this->prepareAttributes($data),
                ...array_filter([
                    'title' => $title,
                    'slug' => $title === null ? null : Str::slug($title),
                ], static fn ($value) => $value !== null),
                'banner' => $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $image) ?: self::DEFAULT_BANNER,
                'deal_type' => $this->dealType(),
                'status' => 0,
            ]);

            $this->syncTranslations($deal->id, $data, ['title']);
            $this->syncSeoMetaInfo($deal, $data, $metaImage, $image);
            $this->forgetSaleCache();

            return $deal;
        });
    }

    public function update(mixed $id, array $data, ?UploadedFile $image = null, ?UploadedFile $metaImage = null): ?Model
    {
        $deal = $this->query()->find($id);

        if ($deal === null) {
            return null;
        }

        return DB::transaction(function () use ($deal, $data, $image, $metaImage): Model {
            $columns = $this->prepareAttributes($data);

            if ($title = $this->defaultLocaleValue($data, 'title')) {
                $columns['title'] = $title;
                $columns['slug'] = Str::slug($title);
            }

            if ($image !== null) {
                $columns['banner'] = $this->updateFile(self::IMAGE_DIRECTORY, $deal->banner, 'webp', $image);
            }

            $deal->update($columns);

            $this->syncTranslations($deal->id, $data, ['title']);
            $this->syncSeoMetaInfo($deal, $data, $metaImage);
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
            $this->query()->where('status', 1)->whereKeyNot($deal->getKey())->update(['status' => 0]);
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

    public function delete(mixed $id): bool
    {
        $deal = $this->query()->find($id);

        if ($deal === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($deal): bool {
            if ($deal->banner && $deal->banner !== self::DEFAULT_BANNER) {
                $this->deleteFile(filePath: self::IMAGE_DIRECTORY.$deal->banner);
            }

            $this->deleteTranslations([$deal->id]);
            $this->deleteSeoMetaInfo($deal->id);

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

    private function prepareAttributes(array $data): array
    {
        return array_filter([
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'background_color' => $data['background_color'] ?? null,
            'text_color' => $data['text_color'] ?? null,
            'featured' => isset($data['featured']) ? (int) (bool) $data['featured'] : null,
        ], static fn ($value) => $value !== null);
    }
}
