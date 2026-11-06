<?php

namespace App\Services;

use App\Models\SeoMetaInfo;
use App\Models\Translation;
use App\Traits\FileManagerTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

abstract class BaseService
{
    use FileManagerTrait {
        upload as private;
        update as private;
        update as protected replaceImage;
        delete as private;
        delete as protected removeFile;
        fileUpload as private;
        setStorageConnectionEnvironment as private;
    }

    private const SEO_IMAGE_DIRECTORY = 'seo-meta-info/';

    private ?Translation $translation = null;

    private ?SeoMetaInfo $seoMetaInfo = null;

    public function __construct(protected readonly Model $model) {}

    protected function storageDisk(): string
    {
        return config('filesystems.disks.default') ?? 'public';
    }

    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Asking for the translations relation means every locale, so the model's
     * single-locale display scope is dropped for that caller.
     *
     * @param  array<mixed>  $relations
     */
    protected function relationQuery(array $relations): Builder
    {
        $wantsEveryTranslation = in_array('translations', $relations, true) || array_key_exists('translations', $relations);

        return $this->query()
            ->when($wantsEveryTranslation, fn (Builder $builder) => $builder->withoutGlobalScope('translate'))
            ->with($relations);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $column => $value) {
            match (true) {
                $value === null => $query->whereNull($column),
                is_array($value) => $query->whereIn($column, $value),
                default => $query->where($column, $value),
            };
        }

        return $query;
    }

    protected function listQueryString(?string $searchValue = null, ?string $sortBy = null, array $filters = []): array
    {
        return array_filter(
            ['searchValue' => $searchValue, 'sortBy' => $sortBy, ...$filters],
            static fn ($value) => is_scalar($value),
        );
    }

    protected function defaultLocaleValue(array $data, string $field): ?string
    {
        $values = $data[$field] ?? null;

        if (empty($values)) {
            return null;
        }

        $index = array_search('en', $data['lang'] ?? [], true);

        if ($index === false) {
            $index = array_key_first($values);
        }

        return $values[$index] ?: null;
    }

    protected function syncTranslations(mixed $id, array $data, array $fields = ['name'], bool $skipBlankValues = false): void
    {
        foreach ($data['lang'] ?? [] as $index => $locale) {
            if ($locale === 'en') {
                continue;
            }

            foreach ($fields as $field) {
                if (! isset($data[$field][$index]) || ($skipBlankValues && $data[$field][$index] === '')) {
                    continue;
                }

                $this->translation()->newQuery()->updateOrInsert(
                    [
                        'translationable_type' => $this->model::class,
                        'translationable_id' => $id,
                        'locale' => $locale,
                        'key' => $field,
                    ],
                    ['value' => $data[$field][$index]]
                );
            }
        }
    }

    protected function translatedIds(string $searchValue, string $key = 'name'): array
    {
        return $this->translation()->newQuery()
            ->where('translationable_type', $this->model::class)
            ->where('key', $key)
            ->where('value', 'like', "%{$searchValue}%")
            ->pluck('translationable_id')
            ->toArray();
    }

    protected function deleteTranslations(array|Builder $ids): void
    {
        $this->translation()->newQuery()
            ->where('translationable_type', $this->model::class)
            ->whereIn('translationable_id', $ids)
            ->delete();
    }

    protected function syncSeoMetaInfo(
        Model $model,
        array $data,
        ?UploadedFile $metaImage = null,
        ?UploadedFile $fallbackImage = null,
    ): void {
        $existing = $this->seoMetaInfo()->newQuery()
            ->where('seoable_type', $model::class)
            ->where('seoable_id', $model->getKey())
            ->first();

        $this->seoMetaInfo()->newQuery()->updateOrInsert(
            ['seoable_type' => $model::class, 'seoable_id' => $model->getKey()],
            [
                'title' => $data['meta_title'] ?? $existing?->title,
                'description' => $data['meta_description'] ?? $existing?->description,
                'index' => ($data['meta_index'] ?? 'index') === 'index' ? '' : 'noindex',
                'no_follow' => ! empty($data['meta_no_follow']) ? 'nofollow' : '',
                'no_image_index' => ! empty($data['meta_no_image_index']) ? 'noimageindex' : '',
                'no_archive' => ! empty($data['meta_no_archive']) ? 'noarchive' : '',
                'no_snippet' => $data['meta_no_snippet'] ?? 0,
                'max_snippet' => $data['meta_max_snippet'] ?? 0,
                'max_snippet_value' => $data['meta_max_snippet_value'] ?? 0,
                'max_video_preview' => $data['meta_max_video_preview'] ?? 0,
                'max_video_preview_value' => $data['meta_max_video_preview_value'] ?? 0,
                'max_image_preview' => $data['meta_max_image_preview'] ?? 0,
                'max_image_preview_value' => $data['meta_max_image_preview_value'] ?? 0,
                'image' => $this->resolveSeoImage($existing, $metaImage, $fallbackImage),
                'updated_at' => now(),
            ]
        );
    }

    protected function deleteSeoMetaInfo(array|int|string $ids): void
    {
        $this->seoMetaInfo()->newQuery()
            ->where('seoable_type', $this->model::class)
            ->whereIn('seoable_id', (array) $ids)
            ->delete();
    }

    private function resolveSeoImage(?Model $existing, ?UploadedFile $metaImage, ?UploadedFile $fallbackImage): ?string
    {
        if ($metaImage !== null) {
            return $existing
                ? $this->replaceImage(self::SEO_IMAGE_DIRECTORY, $existing->image, 'png', $metaImage)
                : $this->upload(dir: self::SEO_IMAGE_DIRECTORY, format: 'webp', image: $metaImage);
        }

        if ($fallbackImage !== null) {
            return $this->upload(dir: self::SEO_IMAGE_DIRECTORY, format: 'webp', image: $fallbackImage);
        }

        return $existing?->image;
    }

    private function translation(): Translation
    {
        return $this->translation ??= new Translation;
    }

    private function seoMetaInfo(): SeoMetaInfo
    {
        return $this->seoMetaInfo ??= new SeoMetaInfo;
    }
}
