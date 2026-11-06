<?php

namespace App\Services\New;

use App\Models\Admin;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class AdminService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }

    public const ROLE_SUPER_ADMIN = 1;

    public const SUPER_ADMIN_ID = 1;

    public const FILTER_EXCLUDE_SUPER_ADMIN = 'exclude_super_admin';

    public const SORT_LATEST = 'latest';

    public const SORT_NEWEST_ID = 'newest-id';

    public const SORT_NAME_ASC = 'a-z';

    private const IMAGE_DIRECTORY = 'admin/';

    private const IMAGE_FORMAT = 'webp';

    public function __construct(Admin $admin)
    {
        parent::__construct($admin);
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
        $excludesSuperAdmin = ! empty($filters[self::FILTER_EXCLUDE_SUPER_ADMIN]);
        unset($filters[self::FILTER_EXCLUDE_SUPER_ADMIN]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%");
            }))
            ->when($excludesSuperAdmin, fn (Builder $builder) => $builder->whereKeyNot(self::SUPER_ADMIN_ID));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            self::SORT_NAME_ASC => $query->orderBy('name'),
            default => $query->latest(),
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
        return $this->query()->with($relations)->where($params)->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data, ?UploadedFile $image = null, array $identityImages = []): ?Model
    {
        return $this->query()->create($data + [
            'image' => $image === null ? null : $this->upload(self::IMAGE_DIRECTORY, self::IMAGE_FORMAT, $image),
            'identify_image' => $this->storeIdentityImages($identityImages),
        ]);
    }

    public function update(mixed $id, array $data, ?UploadedFile $image = null, array $identityImages = []): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        if ($image !== null) {
            $data['image'] = $this->updateFile(
                dir: self::IMAGE_DIRECTORY,
                oldImage: $record->image,
                format: self::IMAGE_FORMAT,
                image: $image,
            );
        }

        if ($identityImages !== []) {
            $this->deleteIdentityImages($record->identify_image);
            $data['identify_image'] = $this->storeIdentityImages($identityImages);
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
    }

    /** @return array{active: int, inactive: int} */
    public function getStatusCounts(?string $searchValue = null, array $filters = []): array
    {
        $excludesSuperAdmin = ! empty($filters[self::FILTER_EXCLUDE_SUPER_ADMIN]);
        unset($filters[self::FILTER_EXCLUDE_SUPER_ADMIN]);

        $query = $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%");
            }))
            ->when($excludesSuperAdmin, fn (Builder $builder) => $builder->whereKeyNot(self::SUPER_ADMIN_ID));

        $this->applyFilters($query, $filters);

        $counts = $query->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'active' => (int) ($counts[1] ?? 0),
            'inactive' => (int) ($counts[0] ?? 0),
        ];
    }

    /** @param array<int, UploadedFile> $identityImages */
    private function storeIdentityImages(array $identityImages): string
    {
        $stored = [];

        foreach ($identityImages as $identityImage) {
            if (! $identityImage instanceof UploadedFile) {
                continue;
            }

            $stored[] = [
                'image_name' => $this->upload(self::IMAGE_DIRECTORY, self::IMAGE_FORMAT, $identityImage),
                'storage' => $this->storageDisk(),
            ];
        }

        return json_encode($stored);
    }

    private function deleteIdentityImages(?string $storedImages): void
    {
        foreach (json_decode((string) $storedImages, true) ?? [] as $storedImage) {
            $imageName = is_string($storedImage) ? $storedImage : ($storedImage['image_name'] ?? null);

            if ($imageName !== null) {
                $this->deleteFile(filePath: self::IMAGE_DIRECTORY.$imageName);
            }
        }
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
}
