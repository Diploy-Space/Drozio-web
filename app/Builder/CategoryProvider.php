<?php

namespace App\Builder;

use App\Enums\BusinessModule;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Modules\Builder\app\Contracts\ItemProvider as ItemProviderContract;
use Modules\Builder\app\Contracts\CategoryProvider as CategoryProviderContract;
use Modules\Builder\app\ValueObjects\Storefront\CategoryDTO;
use Modules\Builder\app\ValueObjects\StorefrontScope;

class CategoryProvider implements CategoryProviderContract
{
    public function __construct(private readonly ItemProviderContract $itemProvider)
    {
    }

    public function forScope(?StorefrontScope $scope): array
    {
        $categories = Category::query()
            ->select(['id', 'name', 'slug', 'priority', 'icon'])
            ->businessModule(BusinessModule::RETAIL)
            ->where('parent_id', 0)
            ->where('position', 0)
            ->with(['childes' => function ($query) {
                $query->select(['id', 'name', 'slug', 'parent_id', 'priority'])
                    ->businessModule(BusinessModule::RETAIL)
                    ->orderByDesc('priority')
                    ->orderBy('name');
            }])
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();

        $childParentMap = Category::query()
            ->businessModule(BusinessModule::RETAIL)
            ->whereIn('parent_id', $categories->pluck('id')->all())
            ->pluck('parent_id', 'id');

        $countableIds = $categories->pluck('id')
            ->merge($childParentMap->keys())
            ->unique()
            ->values()
            ->all();

        $directCounts = $this->categoryProductCounts($scope, $countableIds);

        return $categories->map(function (Category $category) use ($directCounts, $childParentMap) {
            $children = $category->childes
                ->map(fn (Category $child) => new CategoryDTO(
                    id: (int) $child->id,
                    name: (string) $child->name,
                    slug: $child->slug,
                    image: null,
                    itemCount: (int) ($directCounts[$child->id] ?? 0),
                ))
                ->filter(fn (CategoryDTO $child) => $child->itemCount > 0)
                ->values()
                ->all();

            return new CategoryDTO(
                id: (int) $category->id,
                name: (string) $category->name,
                slug: $category->slug,
                image: getStorageImages(path: $category->icon_full_url ?? null, type: 'category'),
                itemCount: (int) ($directCounts[$category->id] ?? 0),
                children: $children,
            );
        })
            ->filter(fn (CategoryDTO $dto) => $dto->itemCount > 0)
            ->map(fn (CategoryDTO $dto) => $dto->toArray())
            ->values()
            ->all();
    }

    public function normalizeActiveCategoryIds(array $categoryIds = [], mixed $categoryId = null): array
    {
        return collect($categoryIds)
            ->when($categoryId !== null && $categoryId !== '', fn ($collection) => $collection->push($categoryId))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function resolveActiveCategory(array $categories, array $activeCategoryIds): ?array
    {
        return collect($categories)
            ->map(fn (array $category) => [...$category, 'children' => $category['children'] ?? []])
            ->flatMap(fn (array $category) => collect([$category])->merge($category['children']))
            ->first(fn (array $category) => \in_array((int) $category['id'], $activeCategoryIds, true));
    }

    public function categoryProducts(
        ?StorefrontScope $scope,
        array $categoryIds = [],
        int $limit = 12,
        int $offset = 1,
        string $type = 'all',
    ): array {
        $limit = \max(1, \min($limit, 100));
        $offset = \max(1, $offset);

        return $this->itemProvider->listing(
            $scope,
            ['categoryIds' => $categoryIds, 'type' => $type, 'sort' => 'rating'],
            $limit,
            $offset,
        );
    }

    public function search(?StorefrontScope $scope, ?string $query, int $limit = 30): array
    {
        $query = \trim((string) $query);
        $limit = \max(1, \min($limit, 100));

        return Category::with('translations')
            ->businessModule(BusinessModule::RETAIL)
            ->where('parent_id', 0)
            ->when($query !== '', function (Builder $q) use ($query) {
                $q->where(function (Builder $inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                        ->orWhereHas('translations', fn (Builder $t) => $t->where('value', 'like', "%{$query}%"));
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'icon'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug ?? null,
                'image' => getStorageImages(path: $category->icon_full_url ?? null, type: 'category'),
            ])
            ->values()
            ->all();
    }

    public function translatedNamesByIds(array $ids): array
    {
        $ids = collect($ids)->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->all();
        if ($ids === []) {
            return [];
        }

        return Category::query()
            ->businessModule(BusinessModule::RETAIL)
            ->whereIn('id', $ids)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Category $category) => [(int) $category->id => (string) $category->name])
            ->all();
    }

    public function findIdBySlug(?StorefrontScope $scope, string $slug): ?int
    {
        $slug = \trim($slug);
        if ($slug === '') {
            return null;
        }

        $id = Category::query()
            ->businessModule(BusinessModule::RETAIL)
            ->where('slug', $slug)
            ->value('id');

        return $id ? (int) $id : null;
    }

    /** @return \Illuminate\Support\Collection<int,int> */
    private function categoryProductCounts(?StorefrontScope $scope, array $categoryIds)
    {
        if ($categoryIds === []) {
            return collect();
        }

        return collect($categoryIds)->mapWithKeys(function ($categoryId) use ($scope) {
            $count = Product::query()
                ->active()
                ->businessModule(BusinessModule::RETAIL)
                ->tap(fn (Builder $q) => $this->applyShopScope($q, $scope))
                ->where(function (Builder $q) use ($categoryId) {
                    $q->where('category_id', $categoryId)
                        ->orWhereJsonContains('category_ids', ['id' => (string) $categoryId]);
                })
                ->count();

            return [(int) $categoryId => $count];
        });
    }

    private function applyShopScope(Builder $query, ?StorefrontScope $scope): void
    {
        if ($scope === null) {
            return;
        }

        $sellerId = $scope->tenantId;

        $sellerId
            ? $query->where('added_by', 'seller')->where('user_id', $sellerId)
            : $query->where('added_by', 'admin');
    }
}
