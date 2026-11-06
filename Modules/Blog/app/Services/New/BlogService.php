<?php

namespace Modules\Blog\app\Services\New;

use App\Models\Storage as StorageModel;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Modules\Blog\app\Models\Blog;
use Modules\Blog\app\Models\BlogSeo;
use Modules\Blog\app\Models\BlogTranslation;

class BlogService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }

    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const FILTER_PUBLISH_DATE = 'publish_date';

    private const PAGE_EDIT = 'edit';

    private const IMAGE_DIRECTORY = 'blog/image/';

    private const META_IMAGE_DIRECTORY = 'blog/meta/';

    private const SLUG_SUFFIX_LENGTH = 6;

    private const READABLE_ID_OFFSET = 100000;

    private const DATE_RANGE_SEPARATOR = ' - ';

    private const DATE_RANGE_FORMAT = 'm/d/Y';

    /** @var array<int, string> */
    private const TRANSLATED_FIELDS = ['title', 'description'];

    private const INDEXABLE_CHUNK_SIZE = 1000;

    public function __construct(Blog $blog)
    {
        parent::__construct($blog);
    }

    protected function query(): Builder
    {
        return parent::query()->withoutGlobalScopes();
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
                    ->orWhereIn('id', $this->translatedIds($searchValue, 'title'))
                    ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$searchValue}%"));
            }));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_OLDEST => $query->oldest(),
            default => $query->latest(),
        };

        $this->applyFilters($query, $this->applyKeyedFilters($query, $filters));

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

    public function find(mixed $id, array $relations = ['translations', 'seoInfo']): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBySlug(string $slug, array $relations = ['translations', 'seoInfo']): ?Model
    {
        return $this->query()->with($relations)->where('slug', $slug)->first();
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function create(array $data, ?UploadedFile $image = null, ?UploadedFile $metaImage = null): ?Model
    {
        return DB::transaction(function () use ($data, $image, $metaImage): Model {
            $isDraft = (int) ($data['is_draft'] ?? 0);
            $imagePath = $image === null ? null : $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $image);
            $storageType = $image === null ? null : $this->storageDisk();

            $blog = $this->query()->create([
                'title' => $this->defaultLocaleValue($data, 'title'),
                'slug' => $this->slug($data),
                'readable_id' => $this->nextReadableId(),
                'description' => $this->defaultLocaleValue($data, 'description') ?? '',
                'category_id' => $data['blog_category'] ?? null,
                'writer' => $data['writer'] ?? null,
                'publish_date' => $data['publish_date'] ?? now(),
                'status' => $data['status'] ?? 1,
                'is_draft' => $isDraft,
                'click_count' => 0,
                'is_published' => $isDraft ? 0 : 1,
                'image' => $isDraft ? '' : $imagePath,
                'image_storage_type' => $isDraft ? null : $storageType,
                'draft_image' => $isDraft ? $imagePath : '',
                'draft_image_storage_type' => $isDraft ? $storageType : null,
                'draft_data' => $this->draftData($data, $isDraft),
            ]);

            $this->syncBlogTranslations($blog->id, $data, $isDraft);
            $this->syncSeoInfo($blog, $data, $metaImage, $image);

            return $blog;
        });
    }

    public function update(mixed $id, array $data, ?UploadedFile $image = null, ?UploadedFile $metaImage = null): ?Model
    {
        $blog = $this->query()->with('seoInfo')->find($id);

        if ($blog === null) {
            return null;
        }

        return DB::transaction(function () use ($blog, $data, $image, $metaImage): Model {
            $isDraft = (int) ($data['is_draft'] ?? 0);

            $blog->update($isDraft
                ? $this->draftColumns($blog, $data, $image)
                : $this->publishedColumns($blog, $data, $image));

            $this->syncBlogTranslations($blog->id, $data, $isDraft);

            if (! $isDraft && $this->hasSeoInput($data)) {
                $this->syncSeoInfo($blog, $data, $metaImage);
            }

            return $blog->refresh();
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
        $blog = $this->query()->with('seoInfo')->find($id);

        if ($blog === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($blog): bool {
            $this->deleteImage($blog->image);
            $this->deleteReplacedImage($blog->draft_image, $blog->image);
            $this->deleteSeoInfo($blog);
            $this->deleteTranslations([$blog->id]);

            return (bool) $blog->delete();
        });
    }

    public function clearDraft(mixed $id): bool
    {
        $blog = $this->query()->with('seoInfo')->find($id);

        if ($blog === null) {
            return false;
        }

        if (! $blog->is_published) {
            return $this->delete($blog->id);
        }

        $this->deleteReplacedImage($blog->draft_image, $blog->image);

        return (bool) $blog->update([
            'is_draft' => 0,
            'draft_data' => json_encode([]),
            'draft_image' => '',
        ]);
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $this->applyKeyedFilters($query, $filters));

        return $query->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function applyKeyedFilters(Builder $query, array $filters): array
    {
        $range = $this->publishDateRange($filters[self::FILTER_PUBLISH_DATE] ?? null);

        if ($range !== null) {
            $query->whereBetween(self::FILTER_PUBLISH_DATE, $range);
        }

        unset($filters[self::FILTER_PUBLISH_DATE]);

        return $filters;
    }

    /** @return array<int, Carbon>|null */
    private function publishDateRange(?string $publishDate): ?array
    {
        if (empty($publishDate)) {
            return null;
        }

        $dates = explode(self::DATE_RANGE_SEPARATOR, $publishDate);

        if (count($dates) !== 2) {
            return null;
        }

        return [
            Carbon::createFromFormat(self::DATE_RANGE_FORMAT, $dates[0])->startOfDay(),
            Carbon::createFromFormat(self::DATE_RANGE_FORMAT, $dates[1])->endOfDay(),
        ];
    }

    /** @return array<string, mixed> */
    private function draftColumns(Model $blog, array $data, ?UploadedFile $image): array
    {
        $imagePath = $image === null
            ? $blog->image ?? $blog->draft_image
            : $this->updateFile(
                self::IMAGE_DIRECTORY,
                $blog->draft_image !== $blog->image ? $blog->draft_image : null,
                'webp',
                $image,
            );

        return [
            'title' => $blog->title,
            'slug' => $blog->slug,
            'description' => $blog->description,
            'category_id' => $blog->category_id,
            'writer' => $blog->writer,
            'publish_date' => $blog->publish_date,
            'is_draft' => 1,
            'draft_data' => $this->draftData($data, 1),
            'draft_image' => $imagePath,
            'draft_image_storage_type' => $image === null ? $blog->draft_image_storage_type : $this->storageDisk(),
        ];
    }

    /** @return array<string, mixed> */
    private function publishedColumns(Model $blog, array $data, ?UploadedFile $image): array
    {
        if ($image !== null) {
            $imagePath = $this->updateFile(self::IMAGE_DIRECTORY, $blog->image, 'webp', $image);
        } elseif (($data['page'] ?? null) === self::PAGE_EDIT) {
            $imagePath = $blog->image;
            $this->deleteReplacedImage($blog->draft_image, $blog->image);
        } else {
            $imagePath = $blog->draft_image;
            $this->deleteReplacedImage($blog->image, $blog->draft_image);
        }

        if ($blog->is_draft) {
            $this->promoteDraftImageStorage($blog->id);
        }

        $title = $this->defaultLocaleValue($data, 'title');

        return [
            'title' => $title,
            'slug' => $title !== $blog->title ? $this->slug($data) : $blog->slug,
            'description' => $this->defaultLocaleValue($data, 'description'),
            'category_id' => $data['blog_category'] ?? null,
            'writer' => $data['writer'] ?? null,
            'publish_date' => $data['publish_date'] ?? now(),
            'image' => $imagePath,
            'image_storage_type' => $image === null ? $blog->image_storage_type : $this->storageDisk(),
            'is_draft' => 0,
            'is_published' => 1,
            'draft_data' => json_encode([]),
            'draft_image' => '',
            'draft_image_storage_type' => '',
            'status' => 1,
        ];
    }

    private function draftData(array $data, int $isDraft): string
    {
        return json_encode($isDraft ? [
            'title' => $this->defaultLocaleValue($data, 'title'),
            'description' => $this->defaultLocaleValue($data, 'description'),
            'category_id' => $data['blog_category'] ?? null,
            'writer' => $data['writer'] ?? null,
            'publish_date' => $data['publish_date'] ?? now(),
        ] : []);
    }

    private function slug(array $data): string
    {
        return Str::slug($this->defaultLocaleValue($data, 'title'), '-').'-'.Str::random(self::SLUG_SUFFIX_LENGTH);
    }

    private function nextReadableId(): int
    {
        $autoIncrement = DB::table('INFORMATION_SCHEMA.TABLES')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $this->model->getTable())
            ->value('AUTO_INCREMENT');

        $readableId = $autoIncrement === null
            ? ((int) $this->query()->max('readable_id') ?: self::READABLE_ID_OFFSET) + 1
            : self::READABLE_ID_OFFSET + (int) $autoIncrement;

        while ($this->exists(['readable_id' => $readableId])) {
            $readableId++;
        }

        return $readableId;
    }

    private function hasSeoInput(array $data): bool
    {
        return array_key_exists('meta_title', $data) || array_key_exists('meta_description', $data);
    }

    private function syncSeoInfo(
        Model $blog,
        array $data,
        ?UploadedFile $metaImage = null,
        ?UploadedFile $fallbackImage = null,
    ): void {
        $existing = $blog->seoInfo;

        $this->blogSeoQuery()->updateOrCreate(['blog_id' => $blog->id], [
            'title' => $data['meta_title'] ?? $existing?->title,
            'description' => $data['meta_description'] ?? $existing?->description,
            'index' => ($data['meta_index'] ?? null) === 'index' ? '' : 'noindex',
            'no_follow' => empty($data['meta_no_follow']) ? '' : 'nofollow',
            'no_image_index' => empty($data['meta_no_image_index']) ? '' : 'noimageindex',
            'no_archive' => empty($data['meta_no_archive']) ? '' : 'noarchive',
            'no_snippet' => $data['meta_no_snippet'] ?? 0,
            'max_snippet' => $data['meta_max_snippet'] ?? 0,
            'max_snippet_value' => $data['meta_max_snippet_value'] ?? 0,
            'max_video_preview' => $data['meta_max_video_preview'] ?? 0,
            'max_video_preview_value' => $data['meta_max_video_preview_value'] ?? 0,
            'max_image_preview' => $data['meta_max_image_preview'] ?? 0,
            'max_image_preview_value' => $data['meta_max_image_preview_value'] ?? 0,
            'image' => $this->resolveSeoImage($existing, $metaImage, $fallbackImage),
        ]);
    }

    private function resolveSeoImage(?Model $existing, ?UploadedFile $metaImage, ?UploadedFile $fallbackImage): ?string
    {
        if ($metaImage !== null) {
            return $this->updateFile(self::META_IMAGE_DIRECTORY, $existing?->image, 'png', $metaImage);
        }

        if ($fallbackImage !== null) {
            return $this->updateFile(self::META_IMAGE_DIRECTORY, $existing?->image, 'webp', $fallbackImage);
        }

        return $existing?->image;
    }

    private function deleteSeoInfo(Model $blog): void
    {
        if ($blog->seoInfo === null) {
            return;
        }

        $this->deleteImage($blog->seoInfo->image, self::META_IMAGE_DIRECTORY);
        $blog->seoInfo->delete();
    }

    private function deleteImage(?string $image, string $directory = self::IMAGE_DIRECTORY): void
    {
        if (empty($image)) {
            return;
        }

        $this->deleteFile(filePath: $directory.$image);
    }

    private function deleteReplacedImage(?string $image, ?string $keptImage): void
    {
        if ($image !== $keptImage) {
            $this->deleteImage($image);
        }
    }

    private function promoteDraftImageStorage(mixed $id): void
    {
        (new StorageModel)->newQuery()
            ->where([
                'data_type' => $this->model::class,
                'data_id' => $id,
                'key' => 'draft_image',
            ])
            ->update(['key' => 'image']);
    }

    private function syncBlogTranslations(mixed $id, array $data, int $isDraft): void
    {
        foreach ($data['lang'] ?? [] as $index => $locale) {
            if ($locale === 'en') {
                continue;
            }

            foreach (self::TRANSLATED_FIELDS as $field) {
                if (! isset($data[$field][$index])) {
                    continue;
                }

                $this->blogTranslationQuery()->updateOrInsert(
                    [
                        'translation_type' => $this->model::class,
                        'translation_id' => $id,
                        'locale' => $locale,
                        'key' => $field,
                        'is_draft' => $isDraft,
                    ],
                    ['value' => $data[$field][$index]]
                );
            }
        }
    }

    protected function translatedIds(string $searchValue, string $key = 'title'): array
    {
        return $this->blogTranslationQuery()
            ->where('key', $key)
            ->where('value', 'like', "%{$searchValue}%")
            ->pluck('translation_id')
            ->toArray();
    }

    protected function deleteTranslations(array|Builder|int|string $ids): void
    {
        $this->blogTranslationQuery()
            ->whereIn('translation_id', $ids)
            ->delete();
    }

    private function blogTranslationQuery(): Builder
    {
        return (new BlogTranslation)->newQuery()->where('translation_type', $this->model::class);
    }

    private function blogSeoQuery(): Builder
    {
        return (new BlogSeo)->newQuery();
    }
}
