<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Review;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ReviewService extends BaseService
{
    use FileManagerTrait {
        delete as private deleteFile;
    }

    public const IMAGE_DIRECTORY = 'review/';

    public const IMAGE_FORMAT = 'webp';

    public const SORT_LATEST = 'latest';

    public const SORT_NEWEST_ID = 'newest-id';

    public const SORT_OLDEST_ID = 'oldest-id';

    public const SORT_RATING_DESC = 'rating-high';

    public const SORT_RECENTLY_UPDATED = 'recently-updated';

    public const FILTER_PRODUCT_SELLER_ID = 'product_seller_id';

    public const FILTER_DATE_FROM = 'date_from';

    public const FILTER_DATE_TO = 'date_to';

    public const FILTER_INCLUDE_INACTIVE = 'include_inactive';

    public const FILTER_MATCHING_PRODUCT_IDS = 'matching_product_ids';

    public const FILTER_MATCHING_CUSTOMER_IDS = 'matching_customer_ids';

    private const ACTIVE_GLOBAL_SCOPE = 'active';

    private const STATUS_ACTIVE = 1;

    private const REVIEW_ID_PADDING_LIMIT = 10;

    private const POSITIVE_RATING = 4;

    /** @var array{avg_rating: float, total_review: int} */
    private const EMPTY_SUMMARY = ['avg_rating' => 0.0, 'total_review' => 0, 'total_comment' => 0];

    public function __construct(Review $review)
    {
        parent::__construct($review);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        $module = $this->businessModule();

        return parent::query()->where(function (Builder $review) use ($module): void {
            $review->whereHas('product', fn (Builder $product) => $product->businessModule($module));

            if ($module === BusinessModule::RETAIL) {
                $review->orWhereDoesntHave('product');
            }
        });
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
        $query = $this->query()->with($relations)->withCount($withCount);

        $filters = $this->applyKeyedFilters($query, $filters, $searchValue);

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            self::SORT_OLDEST_ID => $query->orderBy('id'),
            self::SORT_RATING_DESC => $query->orderByDesc('rating'),
            self::SORT_RECENTLY_UPDATED => $query->latest('updated_at'),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    protected function applyKeyedFilters(Builder $query, array $filters, ?string $searchValue = null): array
    {
        $productSellerId = $filters[self::FILTER_PRODUCT_SELLER_ID] ?? null;
        $dateFrom = $filters[self::FILTER_DATE_FROM] ?? null;
        $dateTo = $filters[self::FILTER_DATE_TO] ?? null;
        $matchingProductIds = $filters[self::FILTER_MATCHING_PRODUCT_IDS] ?? null;
        $matchingCustomerIds = $filters[self::FILTER_MATCHING_CUSTOMER_IDS] ?? null;

        $query
            ->when(
                ! empty($filters[self::FILTER_INCLUDE_INACTIVE]),
                fn (Builder $builder) => $builder->withoutGlobalScope(self::ACTIVE_GLOBAL_SCOPE),
            )
            ->when($productSellerId, fn (Builder $builder) => $this->whereProductSeller($builder, $productSellerId))
            ->when($dateFrom, fn (Builder $builder) => $builder->where('created_at', '>=', trim($dateFrom).' 00:00:00'))
            ->when($dateTo, fn (Builder $builder) => $builder->where('created_at', '<=', trim($dateTo).' 23:59:59'));

        $this->applySearch(
            query: $query,
            searchValue: $searchValue,
            matchingProductIds: $matchingProductIds,
            matchingCustomerIds: $matchingCustomerIds,
        );

        unset(
            $filters[self::FILTER_PRODUCT_SELLER_ID],
            $filters[self::FILTER_DATE_FROM],
            $filters[self::FILTER_DATE_TO],
            $filters[self::FILTER_INCLUDE_INACTIVE],
            $filters[self::FILTER_MATCHING_PRODUCT_IDS],
            $filters[self::FILTER_MATCHING_CUSTOMER_IDS],
        );

        return $filters;
    }

    private function applySearch(
        Builder $query,
        ?string $searchValue,
        ?array $matchingProductIds,
        ?array $matchingCustomerIds,
    ): void {
        $hasMatchingIds = $matchingProductIds !== null || $matchingCustomerIds !== null;

        if ($searchValue === null && ! $hasMatchingIds) {
            return;
        }

        $query->where(function (Builder $inner) use ($searchValue, $hasMatchingIds, $matchingProductIds, $matchingCustomerIds): void {
            if ($searchValue !== null) {
                $inner->where('comment', 'like', "%{$searchValue}%")
                    ->when(is_numeric($searchValue), fn (Builder $query) => $query->orWhere('id', $searchValue)->orWhere('order_id', $searchValue));
            }

            if ($hasMatchingIds) {
                $inner->orWhereIn('product_id', $matchingProductIds ?? [])
                    ->orWhereIn('customer_id', $matchingCustomerIds ?? []);
            }
        });
    }

    public function getInfiniteScrollList(
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        int $page = 1,
        array $relations = [],
        array $filters = [],
    ): Paginator {
        $query = $this->query()->with($relations)->latest();

        $this->applyFilters($query, $filters);

        return $query->simplePaginate(perPage: (int) $dataLimit, page: $page);
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

    public function create(array $data): ?Model
    {
        return $this->query()->create($data);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateSavedStatus(mixed $id, bool $isSaved): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['is_saved' => (int) $isSaved]);
    }

    /** @return array<string, int> */
    public function getSellerReviewCountsByBusinessModule(int $sellerId): array
    {
        return $this->model->newQuery()
            ->withoutGlobalScope(self::ACTIVE_GLOBAL_SCOPE)
            ->join('products', 'products.id', '=', 'reviews.product_id')
            ->where('reviews.status', self::STATUS_ACTIVE)
            ->where('products.added_by', ProductService::ADDED_BY_SELLER)
            ->where('products.user_id', $sellerId)
            ->selectRaw('products.business_module, COUNT(*) as total')
            ->groupBy('products.business_module')
            ->pluck('total', 'business_module')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    /**
     * @return array{avg_rating: float, total_review: int}
     */
    public function getRatingSummary(array $filters = [], int $precision = 2, bool $includeInactive = false): array
    {
        $query = $includeInactive
            ? $this->query()->withoutGlobalScope(self::ACTIVE_GLOBAL_SCOPE)
            : $this->query()->active();

        $productSellerId = $filters[self::FILTER_PRODUCT_SELLER_ID] ?? null;
        unset($filters[self::FILTER_PRODUCT_SELLER_ID]);

        $query->when($productSellerId, fn (Builder $builder) => $this->whereProductSeller($builder, $productSellerId));

        $this->applyFilters($query, $filters);

        $row = $query->selectRaw("SUM(rating) AS total_rating, COUNT(*) AS total_review, SUM(CASE WHEN comment IS NOT NULL AND comment != '' THEN 1 ELSE 0 END) AS total_comment")->first();
        $totalReview = (int) ($row->total_review ?? 0);

        return [
            'avg_rating' => $totalReview === 0 ? 0.0 : round(((int) $row->total_rating) / $totalReview, $precision),
            'total_review' => $totalReview,
            'total_comment' => (int) ($row->total_comment ?? 0),
        ];
    }

    /**
     * @param  array<int, int>  $sellerIds
     * @return array<int, ?float>
     */
    public function getAverageRatingBySellers(array $sellerIds): array
    {
        $ratings = array_fill_keys($sellerIds, null);

        if ($sellerIds === []) {
            return $ratings;
        }

        $rows = $this->query()
            ->withoutGlobalScope(self::ACTIVE_GLOBAL_SCOPE)
            ->join('products', 'products.id', '=', 'reviews.product_id')
            ->where('reviews.status', 1)
            ->where('products.added_by', ProductService::ADDED_BY_SELLER)
            ->whereIn('products.user_id', $sellerIds)
            ->groupBy('products.user_id')
            ->selectRaw('products.user_id AS seller_id, SUM(reviews.rating) AS total_rating, COUNT(*) AS total_review')
            ->toBase()
            ->get();

        foreach ($rows as $row) {
            $ratings[(int) $row->seller_id] = round(((int) $row->total_rating) / (int) $row->total_review, 2);
        }

        return $ratings;
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array{rating_count: int, total_rating: int, average_rating: ?float, positive_percentage: float|int}
     */
    public function getRatingBreakdownForProducts(array $productIds): array
    {
        $row = $this->query()
            ->active()
            ->whereIn('product_id', $productIds)
            ->selectRaw($this->ratingBreakdownSelect('rating'))
            ->toBase()
            ->first();

        return $this->ratingBreakdown($row);
    }

    /**
     * @param  array<int, int>  $sellerIds
     * @return array<int, array{rating_count: int, total_rating: int, average_rating: ?float, positive_percentage: float|int}>
     */
    public function getRatingBreakdownBySellers(array $sellerIds): array
    {
        $breakdowns = array_fill_keys($sellerIds, $this->ratingBreakdown(null));

        if ($sellerIds === []) {
            return $breakdowns;
        }

        $rows = $this->query()
            ->withoutGlobalScope(self::ACTIVE_GLOBAL_SCOPE)
            ->join('products', 'products.id', '=', 'reviews.product_id')
            ->where('reviews.status', 1)
            ->whereNull('reviews.delivery_man_id')
            ->where('products.added_by', ProductService::ADDED_BY_SELLER)
            ->where('products.status', 1)
            ->where('products.request_status', ProductService::REQUEST_STATUS_APPROVED)
            ->whereIn('products.user_id', $sellerIds)
            ->groupBy('products.user_id')
            ->selectRaw('products.user_id AS seller_id, '.$this->ratingBreakdownSelect('reviews.rating'))
            ->toBase()
            ->get();

        foreach ($rows as $row) {
            $breakdowns[(int) $row->seller_id] = $this->ratingBreakdown($row);
        }

        return $breakdowns;
    }

    private function ratingBreakdownSelect(string $ratingColumn): string
    {
        return "COUNT(*) AS rating_count, SUM({$ratingColumn}) AS total_rating, SUM(CASE WHEN {$ratingColumn} >= ".self::POSITIVE_RATING.' THEN 1 ELSE 0 END) AS positive_count';
    }

    /**
     * @return array{rating_count: int, total_rating: int, average_rating: ?float, positive_percentage: float|int}
     */
    private function ratingBreakdown(?object $row): array
    {
        $ratingCount = (int) ($row->rating_count ?? 0);
        $totalRating = (int) ($row->total_rating ?? 0);

        return [
            'rating_count' => $ratingCount,
            'total_rating' => $totalRating,
            'average_rating' => $ratingCount === 0 ? null : $totalRating / $ratingCount,
            'positive_percentage' => $ratingCount === 0 ? 0 : ((int) ($row->positive_count ?? 0) * 100) / $ratingCount,
        ];
    }

    private function whereProductSeller(Builder $query, int|string $sellerId): Builder
    {
        return $query->whereHas('product', static fn (Builder $product): Builder => $product
            ->where('added_by', ProductService::ADDED_BY_SELLER)
            ->where('user_id', $sellerId));
    }

    /** @return array{active: int, inactive: int} */
    public function getStatusCounts(?string $searchValue = null, array $filters = []): array
    {
        $query = $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->where('comment', 'like', "%{$searchValue}%"));

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

        $filters = $this->applyKeyedFilters($query, $filters);
        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * @param  array<int|string, array<int, int>>  $productIdsByGroup
     * @return array<int|string, array{avg_rating: float, total_review: int}>
     */
    public function getRatingSummaryByGroup(array $productIdsByGroup): array
    {
        $summaries = array_fill_keys(array_keys($productIdsByGroup), self::EMPTY_SUMMARY);
        $productIds = array_merge(...array_values($productIdsByGroup)) ?: [];

        if ($productIds === []) {
            return $summaries;
        }

        $rows = $this->query()
            ->active()
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(rating) AS total_rating, COUNT(*) AS total_review')
            ->get()
            ->keyBy('product_id');

        foreach ($productIdsByGroup as $group => $ids) {
            $totalRating = 0;
            $totalReview = 0;

            foreach ($ids as $productId) {
                $row = $rows->get($productId);
                $totalRating += (int) ($row->total_rating ?? 0);
                $totalReview += (int) ($row->total_review ?? 0);
            }

            $summaries[$group] = [
                'avg_rating' => $totalReview === 0 ? 0.0 : round($totalRating / $totalReview, 2),
                'total_review' => $totalReview,
            ];
        }

        return $summaries;
    }

    public function hasDisallowedExtension(array $files): ?string
    {
        $disallowedExtensions = getDisallowedExtensionsListArray();

        foreach ($files as $file) {
            $extension = $file->getClientOriginalExtension();

            if (in_array($extension, $disallowedExtensions, true)) {
                return $extension;
            }
        }

        return null;
    }

    public function generateReviewId(int|string $orderId): string
    {
        return $this->generateUnusedReviewId(orderId: $orderId, isPositionPadded: true);
    }

    public function createOrUpdateForProduct(
        int $customerId,
        int|string $productId,
        int|string $orderId,
        ?string $comment,
        int $rating,
        mixed $reviewId = null,
        array $images = [],
    ): ?Model {
        return DB::transaction(function () use ($customerId, $productId, $orderId, $comment, $rating, $reviewId, $images): ?Model {
            $review = $this->findBy(params: ['customer_id' => $customerId, 'id' => $reviewId], relations: []);

            $data = [
                'id' => $reviewId ?: $this->generateReviewId(orderId: $orderId),
                'customer_id' => $customerId,
                'product_id' => $productId,
                'order_id' => $orderId,
                'comment' => $comment,
                'rating' => $rating,
                'attachment' => $images === []
                    ? $review?->attachment
                    : $this->mergeAttachments(storedAttachments: $review?->attachment, images: $images),
                'updated_at' => now(),
                'created_at' => $review?->created_at ?? now(),
            ];

            return $reviewId ? $this->update(id: $reviewId, data: $data) : $this->create(data: $data);
        });
    }

    public function upsertDeliverymanReview(
        int $customerId,
        int $deliveryManId,
        int|string $orderId,
        ?string $comment,
        int $rating,
    ): bool {
        $identity = [
            'delivery_man_id' => $deliveryManId,
            'customer_id' => $customerId,
            'order_id' => $orderId,
        ];

        $data = $identity + [
            'comment' => $comment,
            'rating' => $rating,
            'updated_at' => now(),
        ];

        if ($this->findBy(params: $identity, relations: []) === null) {
            $data['created_at'] = now();
        }

        return $this->unscopedQuery()->updateOrInsert($identity, $data);
    }

    public function removeAttachment(mixed $reviewId, string $fileName): bool
    {
        $review = $this->find(id: $reviewId, relations: []);

        if ($review === null) {
            return false;
        }

        $remainingAttachments = [];

        foreach ($review->attachment ?? [] as $attachment) {
            if (($attachment['file_name'] ?? $attachment) !== $fileName) {
                $remainingAttachments[] = $attachment;

                continue;
            }

            $this->deleteFile(self::IMAGE_DIRECTORY.$fileName);
        }

        $review->attachment = $remainingAttachments;

        return $review->save();
    }

    public function getRatingDistribution(array $filters = []): Collection
    {
        $query = $this->query();

        $this->applyFilters($query, $this->applyKeyedFilters($query, $filters));

        return $query->select('rating')
            ->selectRaw('count(*) as total')
            ->groupBy('rating')
            ->get();
    }

    public function getAverageRating(int|string $productId, bool $includeInactive = false): ?float
    {
        $average = $this->query()
            ->when($includeInactive, fn (Builder $builder) => $builder->withoutGlobalScope(self::ACTIVE_GLOBAL_SCOPE))
            ->where('product_id', $productId)
            ->whereNull('delivery_man_id')
            ->avg('rating');

        return $average === null ? null : (float) $average;
    }

    public function getOverallRatingForProduct(int|string $productId): float
    {
        $ratings = $this->query()->where('product_id', $productId)->pluck('rating');

        return $ratings->isEmpty() ? 0.0 : (float) number_format($ratings->sum() / $ratings->count(), 2);
    }

    public function findCustomerProductReview(
        int $customerId,
        int|string $productId,
        int|string $orderId,
        array $relations = ['reply'],
    ): ?Model {
        return $this->query()
            ->with($relations)
            ->where(['product_id' => $productId, 'customer_id' => $customerId, 'order_id' => $orderId])
            ->whereNull('delivery_man_id')
            ->orderByDesc('id')
            ->first();
    }

    public function submitProductReview(
        int $customerId,
        int|string $productId,
        int|string $orderId,
        ?string $comment,
        int|string $rating,
        array $images = [],
    ): Model {
        $attachments = $this->mergeAttachments(storedAttachments: [], images: $images);

        $review = $this->query()
            ->where(['customer_id' => $customerId, 'product_id' => $productId, 'order_id' => $orderId])
            ->whereNull('delivery_man_id')
            ->first();

        if ($review !== null) {
            $review->update([
                'customer_id' => $customerId,
                'product_id' => $productId,
                'comment' => $comment,
                'rating' => $rating,
                'attachment' => $attachments,
            ]);

            return $review->refresh();
        }

        return $this->create(data: [
            'id' => $this->generateSequentialReviewId(orderId: $orderId),
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'product_id' => $productId,
            'comment' => $comment,
            'rating' => $rating,
            'attachment' => $attachments,
        ]);
    }

    public function updateCustomerProductReview(
        int $customerId,
        mixed $id,
        int|string $orderId,
        ?string $comment,
        int|string $rating,
        ?array $images = null,
    ): ?Model {
        $review = $this->query()->where('customer_id', $customerId)->find($id);

        if ($review === null) {
            return null;
        }

        $review->order_id = $orderId;
        $review->comment = $comment;
        $review->rating = $rating;

        if ($images !== null) {
            $review->attachment = $this->mergeAttachments(storedAttachments: $review->attachment, images: $images);
        }

        $review->save();

        return $review->refresh();
    }

    public function findForSeller(int $sellerId, mixed $id, array $relations = []): ?Model
    {
        return $this->whereProductSeller(
            $this->query()->withoutGlobalScope(self::ACTIVE_GLOBAL_SCOPE)->with($relations),
            $sellerId,
        )->find($id);
    }

    public function updateStatusForSeller(int $sellerId, mixed $id, int|string $status): bool
    {
        $review = $this->findForSeller(sellerId: $sellerId, id: $id);

        if ($review === null) {
            return false;
        }

        $review->status = $status;

        return $review->save();
    }

    public function getProductReviewStats(
        int $sellerId,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->withoutGlobalScope(self::ACTIVE_GLOBAL_SCOPE)
            ->whereNull('delivery_man_id')
            ->whereHas('product', static fn (Builder $product): Builder => $product
                ->where('added_by', ProductService::ADDED_BY_SELLER)
                ->where('user_id', $sellerId)
                ->where('request_status', 1))
            ->groupBy('product_id')
            ->select('product_id')
            ->selectRaw('COUNT(*) AS reviews_count, AVG(rating) AS ratings_average')
            ->orderByDesc('reviews_count')
            ->orderBy('product_id');

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    private function generateSequentialReviewId(int|string $orderId): string
    {
        return $this->generateUnusedReviewId(orderId: $orderId, isPositionPadded: false);
    }

    private function generateUnusedReviewId(int|string $orderId, bool $isPositionPadded): string
    {
        $position = $this->unscopedQuery()->where('order_id', $orderId)->count();

        do {
            $position++;
            $reviewId = $orderId.($isPositionPadded && $position <= self::REVIEW_ID_PADDING_LIMIT ? '0'.$position : $position);
        } while ($this->unscopedQuery()->where('id', $reviewId)->exists());

        return $reviewId;
    }

    private function mergeAttachments(?array $storedAttachments, array $images): array
    {
        $attachments = $storedAttachments ?? [];

        foreach ($images as $image) {
            $attachments[] = [
                'file_name' => $this->uploadAttachment(image: $image),
                'storage' => getWebConfig(name: 'storage_connection_type') ?? 'public',
            ];
        }

        return $attachments;
    }

    private function uploadAttachment(UploadedFile $image): string
    {
        return $this->upload(dir: self::IMAGE_DIRECTORY, format: self::IMAGE_FORMAT, image: $image);
    }

    private function unscopedQuery(): QueryBuilder
    {
        return $this->model->newQuery()->getQuery();
    }
}
