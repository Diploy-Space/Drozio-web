<?php

namespace App\Services\New;

use App\Models\RobotsMetaContent;
use App\Services\BaseService;
use App\Utils\ImageManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class RobotsMetaContentService extends BaseService
{
    public const FILTER_EXCLUDED_PAGE_NAMES = 'excluded_page_names';

    public const DEFAULT_PAGE_NAME = 'default';

    public const PAGE_NAME_CONTACTS = 'contacts';

    public const PAGE_NAME_HELP_TOPIC = 'helpTopic';

    public const PAGE_NAME_CATEGORIES = 'categories';

    public const PAGE_NAME_BRANDS = 'brands';

    public const PAGE_NAME_VENDORS = 'vendors';

    public const PAGE_NAME_FEATURED_PRODUCTS = 'featured-products';

    public const PAGE_NAME_LATEST_PRODUCTS = 'latest-products';

    public const PAGE_NAME_BEST_SELLING_PRODUCTS = 'best-selling-products';

    public const PAGE_NAME_TOP_RATED_PRODUCTS = 'top-rated-products';

    public const PAGE_NAME_MOST_FAVORITE_PRODUCTS = 'most-favorite-products';

    public const PAGE_NAME_DISCOUNTED_PRODUCTS = 'discounted-products';

    public const PAGE_NAME_TRACK_ORDER = 'track-order';

    private const IMAGE_DIRECTORY = 'robots-meta-content/';

    private const IMAGE_FORMAT = 'webp';

    private const REPLACEMENT_IMAGE_FORMAT = 'png';

    public function __construct(RobotsMetaContent $robotsMetaContent)
    {
        parent::__construct($robotsMetaContent);
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
        $excludedPageNames = $filters[self::FILTER_EXCLUDED_PAGE_NAMES] ?? [];
        unset($filters[self::FILTER_EXCLUDED_PAGE_NAMES]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where('page_title', 'like', "%{$searchValue}%"))
            ->when($excludedPageNames, fn (Builder $builder) => $builder->whereNotIn('page_name', $excludedPageNames));

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

    public function findByPageNameOrDefault(string $pageName): ?Model
    {
        return $this->findBy(params: ['page_name' => $pageName])
            ?? $this->findBy(params: ['page_name' => self::DEFAULT_PAGE_NAME]);
    }

    public function create(array $data, ?UploadedFile $image = null): ?Model
    {
        return $this->query()->create($data + [
            'meta_image' => $image === null ? null : ImageManager::upload(dir: self::IMAGE_DIRECTORY, format: self::IMAGE_FORMAT, image: $image),
        ]);
    }

    public function update(mixed $id, array $data, ?UploadedFile $image = null): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        if ($image !== null) {
            $data['meta_image'] = ImageManager::update(
                dir: self::IMAGE_DIRECTORY,
                old_image: $record->meta_image,
                format: self::REPLACEMENT_IMAGE_FORMAT,
                image: $image,
            );
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
    }
}
