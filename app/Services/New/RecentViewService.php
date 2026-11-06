<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\ProductRecentView;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RecentViewService extends BaseService
{
    public const SESSION_KEYS = [
        BusinessModule::RETAIL => 'user_visited_product_ids',
        BusinessModule::WHOLESALE => 'user_visited_wholesale_product_ids',
        BusinessModule::AUCTION => 'user_visited_auction_product_ids',
    ];

    public function __construct(ProductRecentView $productRecentView)
    {
        parent::__construct($productRecentView);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        return parent::query()->businessModule($this->businessModule());
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
        $query = $this->relationQuery($relations)->withCount($withCount);

        $this->applyFilters($query, $filters);

        $query->latest('updated_at');

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    /** @return array<int, int> */
    public function getProductIds(int $userId, int $limit): array
    {
        return $this->query()
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->limit($limit)
            ->pluck('product_id')
            ->all();
    }

    /** @return array<int, int> */
    public function getViewedAtByProductId(int $userId, int $limit): array
    {
        return $this->query()
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->limit($limit)
            ->get(['product_id', 'updated_at'])
            ->mapWithKeys(static fn (ProductRecentView $view): array => [$view->product_id => $view->updated_at->getTimestamp()])
            ->all();
    }

    /**
     * @param  array<int, int|string>  $sessionProductIds
     * @return array<int, int>
     */
    public function getVisitedProductIds(?int $userId, array $sessionProductIds, int $limit): array
    {
        if ($userId !== null) {
            return $this->getProductIds(userId: $userId, limit: $limit);
        }

        return array_map('intval', array_slice(array_reverse($sessionProductIds), 0, $limit));
    }

    public function trackView(int $productId, int $userId): void
    {
        $recentView = $this->query()->firstOrCreate([
            'product_id' => $productId,
            'user_id' => $userId,
            'business_module' => $this->businessModule(),
        ]);

        if (! $recentView->wasRecentlyCreated) {
            $recentView->touch();
        }
    }

    /** @param array<int, int> $productIds */
    public function syncVisited(array $productIds, int $userId): void
    {
        foreach ($productIds as $productId) {
            $this->trackView(productId: (int) $productId, userId: $userId);
        }
    }

    public function sessionKey(): string
    {
        return self::SESSION_KEYS[$this->businessModule()];
    }
}
