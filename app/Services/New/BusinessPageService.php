<?php

namespace App\Services\New;

use App\Models\BusinessPage;
use App\Services\BaseService;
use App\Utils\ImageManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class BusinessPageService extends BaseService
{
    public const SORT_DEFAULT_FIRST = 'default-first';

    public const TYPE_DEFAULT = 'default';

    public const TYPE_PAGES = 'pages';

    public const MAX_PAGES = 17;

    /** @var array<int, string> The pages the panel ships, each on a fixed slug the storefront links to. */
    public const DEFAULT_PAGE_SLUGS = [
        'about-us',
        'terms-and-conditions',
        'privacy-policy',
        'refund-policy',
        'return-policy',
        'cancellation-policy',
        'shipping-policy',
    ];

    /** @var array<int, string> Default pages the storefront always needs, so their status stays on. */
    public const ALWAYS_ACTIVE_PAGE_SLUGS = ['terms-and-conditions', 'about-us', 'privacy-policy'];

    private const IMAGE_DIRECTORY = 'business-pages/';

    private const BANNER_FILE_TYPE = 'banner';

    private const INDEXABLE_CHUNK_SIZE = 1000;

    public function __construct(BusinessPage $businessPage)
    {
        parent::__construct($businessPage);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('title', 'like', "%{$searchValue}%"));

        match ($sortBy) {
            self::SORT_DEFAULT_FIRST => $query->orderByDesc('default_status'),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function getIndexableSlugs(): LazyCollection
    {
        return $this->query()
            ->select(['id', 'slug', 'updated_at'])
            ->where('status', 1)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->lazyById(self::INDEXABLE_CHUNK_SIZE);
    }

    public function find(mixed $id, array $relations = ['banner']): ?Model
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

    public function create(array $data, ?UploadedFile $banner = null): ?Model
    {
        return DB::transaction(function () use ($data, $banner): Model {
            $record = $this->query()->create([
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'description' => $data['description'],
                'status' => (int) ($data['status'] ?? 0),
            ]);

            if ($banner !== null) {
                $this->syncBanner(page: $record, banner: $banner);
            }

            return $record;
        });
    }

    public function update(mixed $id, array $data, ?UploadedFile $banner = null): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        return DB::transaction(function () use ($record, $data, $banner): Model {
            $record->update([
                'title' => $data['title'],
                'slug' => $this->resolveSlug($data),
                'description' => $data['description'],
                'status' => $this->resolveStatus($data),
            ]);

            if ($banner !== null) {
                $this->syncBanner(page: $record, banner: $banner);
            }

            return $record->refresh();
        });
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

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
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

    public function deleteBanner(mixed $id): bool
    {
        $record = $this->find(id: $id);
        $fileName = $record?->banner?->file_name;

        if (empty($fileName)) {
            return false;
        }

        if (empty(ImageManager::delete(self::IMAGE_DIRECTORY.$fileName)['success'])) {
            return false;
        }

        $record->banner()->update(['file_name' => '']);

        return true;
    }

    private function resolveSlug(array $data): string
    {
        $postedSlug = $data['slug'] ?? null;

        return in_array($postedSlug, self::DEFAULT_PAGE_SLUGS, true)
            ? Str::slug($postedSlug)
            : Str::slug($data['title']);
    }

    private function resolveStatus(array $data): int
    {
        return in_array($data['slug'] ?? null, self::ALWAYS_ACTIVE_PAGE_SLUGS, true)
            ? 1
            : (int) ($data['status'] ?? 0);
    }

    private function syncBanner(Model $page, UploadedFile $banner): void
    {
        $storedName = (string) ($page->banner?->file_name ?? '');

        $page->banner()->updateOrCreate(
            ['file_type' => self::BANNER_FILE_TYPE],
            [
                'file_name' => $storedName === ''
                    ? ImageManager::upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $banner)
                    : ImageManager::update(dir: self::IMAGE_DIRECTORY, old_image: $storedName, format: 'webp', image: $banner),
                'storage_disk' => $this->storageDisk(),
            ],
        );
    }
}
