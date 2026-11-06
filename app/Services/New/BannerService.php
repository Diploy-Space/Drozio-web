<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Banner;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BannerService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }

    public const RESOURCE_PRODUCT = 'product';

    public const RESOURCE_CATEGORY = 'category';

    public const RESOURCE_SHOP = 'shop';

    public const RESOURCE_BRAND = 'brand';

    public const RESOURCE_CUSTOM = 'custom';

    public const TYPE_POPUP_BANNER = 'Popup Banner';

    public const REASON_RESOURCE_MISSING = 'resource_missing';

    public const REASON_RESOURCE_ABSENT = 'resource_absent';

    public const SORT_NEWEST_ID = 'newest-id';

    public const SORT_OLDEST_ID = 'oldest-id';

    public const SORT_TYPE_ASC = 'type-a-z';

    public const SORT_TYPE_DESC = 'type-z-a';

    private const IMAGE_DIRECTORY = 'banner/';

    public function __construct(Banner $banner)
    {
        parent::__construct($banner);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function isThemeScoped(): bool
    {
        return true;
    }

    protected function query(): Builder
    {
        return parent::query()
            ->businessModule($this->businessModule())
            ->when($this->isThemeScoped(), fn (Builder $builder) => $builder->where('theme', theme_root_path()));
    }

    /** @return array<string, string> */
    public function getBannerTypes(): array
    {
        return match (theme_root_path()) {
            'default' => [
                'Main Banner' => translate('Main Banner'),
                'Popup Banner' => translate('Popup banner'),
                'Footer Banner' => translate('Footer banner'),
                'Main Section Banner' => translate('Main section banner'),
            ],
            'theme_aster' => [
                'Main Banner' => translate('Main Banner'),
                'Popup Banner' => translate('Popup banner'),
                'Footer Banner' => translate('Footer banner'),
                'Main Section Banner' => translate('Main section banner'),
                'Header Banner' => translate('Header Banner'),
                'Sidebar Banner' => translate('Sidebar Banner'),
                'Top Side Banner' => translate('Top Side Banner'),
            ],
            default => [],
        };
    }

    /** @return array<string, string> */
    public function getResourceTypes(): array
    {
        return [
            self::RESOURCE_PRODUCT => translate('Product'),
            self::RESOURCE_CATEGORY => translate('Category'),
            self::RESOURCE_SHOP => translate('Shop'),
            self::RESOURCE_BRAND => translate('Brand'),
            self::RESOURCE_CUSTOM => translate('Custom'),
        ];
    }

    /** @return array<string, string> */
    public function getSortTypes(): array
    {
        return [
            self::SORT_NEWEST_ID => translate('Newest first'),
            self::SORT_OLDEST_ID => translate('Oldest first'),
            self::SORT_TYPE_ASC => translate('Banner type A - Z'),
            self::SORT_TYPE_DESC => translate('Banner type Z - A'),
        ];
    }

    /** @return array<string, string> */
    public function getPublishedTypes(): array
    {
        return [
            '1' => translate('Published'),
            '0' => translate('Unpublished'),
        ];
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
            ->when($searchValue, fn (Builder $builder) => $builder->where(fn (Builder $inner) => $inner
                ->where('url', 'like', "%{$searchValue}%")
                ->orWhere('banner_type', 'like', "%{$searchValue}%")));

        match ($sortBy) {
            self::SORT_OLDEST_ID => $query->orderBy('id'),
            self::SORT_TYPE_ASC => $query->orderBy('banner_type'),
            self::SORT_TYPE_DESC => $query->orderByDesc('banner_type'),
            default => $query->latest('id'),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
          ? $query->get()
          : $query->paginate(perPage: (int) $dataLimit, page: $page)
              ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = ['storage']): ?Model
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

    /** @return array{url: ?string, reason: ?string} */
    public function resolveResource(
        string $resourceType,
        mixed $resourceId = null,
        ?Model $resource = null,
        ?string $customUrl = null,
    ): array {
        if ($resourceType === self::RESOURCE_CUSTOM) {
            return ['url' => $customUrl, 'reason' => null];
        }

        if (empty($resourceId)) {
            return ['url' => null, 'reason' => self::REASON_RESOURCE_MISSING];
        }

        if ($resource === null) {
            return ['url' => null, 'reason' => self::REASON_RESOURCE_ABSENT];
        }

        return ['url' => $this->resolveResourceUrl($resourceType, $resource), 'reason' => null];
    }

    public function create(array $data, ?string $bannerUrl = null, ?UploadedFile $image = null): ?Model
    {
        return DB::transaction(fn (): Model => $this->query()->create([
            ...$this->prepareAttributes($data, $bannerUrl),
            'theme' => theme_root_path(),
            'business_module' => $this->businessModule(),
            'photo' => $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $image),
        ]));
    }

    public function update(mixed $id, array $data, ?string $bannerUrl = null, ?UploadedFile $image = null): ?Model
    {
        $banner = $this->query()->find($id);

        if ($banner === null) {
            return null;
        }

        return DB::transaction(function () use ($banner, $data, $bannerUrl, $image): Model {
            $columns = $this->prepareAttributes($data, $bannerUrl);

            if ($image !== null) {
                $columns['photo'] = $this->updateFile(self::IMAGE_DIRECTORY, $banner->photo, 'webp', $image);
            }

            $banner->update($columns);

            return $banner->refresh();
        });
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        $banner = $this->query()->find($id);

        if ($banner === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($banner, $status): bool {
            if ($status && $banner->banner_type === self::TYPE_POPUP_BANNER) {
                $this->query()
                    ->where('banner_type', self::TYPE_POPUP_BANNER)
                    ->whereKeyNot($banner->getKey())
                    ->update(['published' => 0]);
            }

            return (bool) $banner->update(['published' => (int) $status]);
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
        $banner = $this->query()->find($id);

        if ($banner === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($banner): bool {
            if ($banner->photo) {
                $this->deleteFile(filePath: self::IMAGE_DIRECTORY.$banner->photo);
            }

            return (bool) $banner->delete();
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

    protected function resolveResourceUrl(string $resourceType, Model $resource): string
    {
        return match ($resourceType) {
            self::RESOURCE_PRODUCT => route('product', ['slug' => $resource->slug]),
            self::RESOURCE_SHOP => route('vendor-shop', ['slug' => $resource->slug]),
            self::RESOURCE_CATEGORY => route('products', ['category_id' => $resource->id, 'data_from' => 'category', 'page' => 1]),
            self::RESOURCE_BRAND => route('products', ['brand_id' => $resource->id, 'data_from' => 'brand', 'page' => 1]),
            default => '',
        };
    }

    private function prepareAttributes(array $data, ?string $bannerUrl): array
    {
        $resourceType = $data['resource_type'] ?? null;

        return array_filter([
            'banner_type' => $data['banner_type'] ?? null,
            'resource_type' => $resourceType,
            'resource_id' => $resourceType === null ? null : ($data[$resourceType.'_id'] ?? null),
            'url' => $bannerUrl,
        ], static fn ($value) => $value !== null);
    }
}
