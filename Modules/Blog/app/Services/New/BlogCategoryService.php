<?php

namespace Modules\Blog\app\Services\New;

use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Blog\app\Models\BlogCategory;
use Modules\Blog\app\Models\BlogTranslation;

class BlogCategoryService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const SORT_RECENTLY_UPDATED = 'recently-updated';

    private const SLUG_SUFFIX_LENGTH = 6;

    private const TRANSLATED_FIELD = 'name';

    public function __construct(BlogCategory $blogCategory)
    {
        parent::__construct($blogCategory);
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
                $inner->where('name', 'like', "%{$searchValue}%")
                    ->orWhereIn('id', $this->translatedIds($searchValue));
            }));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest('id'),
            default => $query->latest('updated_at'),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function find(mixed $id, array $relations = ['translations']): ?Model
    {
        return $this->query()->withoutGlobalScopes()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = ['translations']): ?Model
    {
        return $this->query()->withoutGlobalScopes()->with($relations)->where($params)->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data): ?Model
    {
        return DB::transaction(function () use ($data): Model {
            $record = $this->query()->create([
                'name' => $this->defaultName($data),
                'slug' => $this->slug($data),
                'status' => 1,
                'click_count' => 0,
            ]);

            $this->syncBlogTranslations($record->id, $data);

            return $record;
        });
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->withoutGlobalScopes()->find($id);

        if ($record === null) {
            return null;
        }

        return DB::transaction(function () use ($record, $data): Model {
            $record->update([
                'name' => $this->defaultName($data),
                'slug' => $this->slug($data),
            ]);

            $this->syncBlogTranslations($record->id, $data);

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
        $record = $this->query()->withoutGlobalScopes()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($record): bool {
            $this->blogTranslationQuery()
                ->where('translation_id', $record->id)
                ->delete();

            return (bool) $record->delete();
        });
    }

    public function exists(array $params): bool
    {
        return $this->query()->withoutGlobalScopes()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /** @return array<int, array{locale: string, value: string}> */
    public function getLanguageValues(Model $category): array
    {
        $translations = $category->translations->keyBy('locale');

        $values = [];
        foreach (getWebConfig(name: 'pnc_language') ?? [] as $locale) {
            $values[] = [
                'locale' => $locale,
                'value' => $locale === 'en'
                    ? ($category->name ?? '')
                    : ($translations->get($locale)?->value ?? ''),
            ];
        }

        return $values;
    }

    /** @return array<int, string> */
    public function getOptions(): array
    {
        $locale = getDefaultLanguage();
        $categories = $this->query()->withoutGlobalScopes()->with('translations')->get();

        $options = [];
        foreach ($categories as $category) {
            $translated = $category->translations
                ->firstWhere(fn ($translation) => $translation->key === self::TRANSLATED_FIELD && $translation->locale === $locale);

            $options[$category->id] = $locale === 'en'
                ? $category->name
                : ($translated?->value ?? $category->name);
        }

        return $options;
    }

    public function nameExists(string $name, mixed $exceptId = null): bool
    {
        return $this->query()->withoutGlobalScopes()
            ->where('name', $name)
            ->when($exceptId, fn (Builder $builder) => $builder->whereKeyNot($exceptId))
            ->exists();
    }

    private function defaultName(array $data): string
    {
        return $data[self::TRANSLATED_FIELD]['en'] ?? reset($data[self::TRANSLATED_FIELD]);
    }

    private function slug(array $data): string
    {
        return Str::slug($this->defaultName($data), '-').'-'.Str::random(self::SLUG_SUFFIX_LENGTH);
    }

    private function syncBlogTranslations(mixed $id, array $data): void
    {
        foreach ($data[self::TRANSLATED_FIELD] ?? [] as $locale => $value) {
            if ($locale === 'en' || $value === null) {
                continue;
            }

            $this->blogTranslationQuery()->updateOrInsert(
                [
                    'translation_type' => $this->model::class,
                    'translation_id' => $id,
                    'locale' => $locale,
                    'key' => self::TRANSLATED_FIELD,
                ],
                ['value' => $value]
            );
        }
    }

    private function blogTranslationQuery(): Builder
    {
        return (new BlogTranslation)->newQuery()->where('translation_type', $this->model::class);
    }

    protected function translatedIds(string $searchValue, string $key = self::TRANSLATED_FIELD): array
    {
        return $this->blogTranslationQuery()
            ->where('key', $key)
            ->where('value', 'like', "%{$searchValue}%")
            ->pluck('translation_id')
            ->toArray();
    }
}
