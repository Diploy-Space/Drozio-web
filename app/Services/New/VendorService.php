<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Seller;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use App\Utils\VendorVerificationCriteria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VendorService extends BaseService
{
    use FileManagerTrait {
        upload as private;
        update as private;
        delete as private;
        fileUpload as private;
        setStorageConnectionEnvironment as private;
    }

    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const SORT_NAME_ASC = 'a-z';

    public const SORT_WISHLIST_COUNT = 'most-favorite';

    public const SORT_TOTAL_EARNING = 'total-earning';

    public const FILTER_STATUS_EXCEPT = 'status_except';

    public const FILTER_HAS_EARNING = 'has_earning';

    public const FILTER_HAS_WISHLISTED_PRODUCTS = 'has_wishlisted_products';

    public const FILTER_JOINED_BETWEEN = 'joined_between';

    public const FILTER_HAS_APPROVED_PRODUCTS_IN_BUSINESS_MODULE = 'has_approved_products_in_business_module';

    public const FILTER_VERIFIED = 'is_verified';

    private const WEBSITE_CONFIG_TABLE = 'tenant_domain_configs';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PENDING = 'pending';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    public const IMAGE_DIRECTORY = 'seller/';

    public const DEFAULT_IMAGE = 'def.png';

    private const TERMINAL_ORDER_STATUSES = ['delivered', 'canceled', 'returned', 'failed'];

    private const DELIVERED_ORDER_STATUS = 'delivered';

    private const SELLER_IS_SELLER = 'seller';

    private const WHOLESALE_MINIMUM_ORDER_AMOUNT_STATUS_SETTING = 'wholesale_vendor_can_set_minimum_order_amount';

    private const WHOLESALE_MINIMUM_ORDER_AMOUNT_BY_SELLER_SETTING = 'wholesale_vendor_minimum_order_amount_status';

    public function __construct(Seller $seller)
    {
        parent::__construct($seller);
    }

    public function canSetWholesaleMinimumOrderAmount(): bool
    {
        return function_exists('businessModuleManager')
            && businessModuleManager()->isAvailableInPanel(BusinessModule::WHOLESALE)
            && (bool) getWebConfig(name: self::WHOLESALE_MINIMUM_ORDER_AMOUNT_STATUS_SETTING)
            && (bool) getWebConfig(name: self::WHOLESALE_MINIMUM_ORDER_AMOUNT_BY_SELLER_SETTING);
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
                $inner->where('f_name', 'like', "%{$searchValue}%")
                    ->orWhere('l_name', 'like', "%{$searchValue}%")
                    ->orWhereRaw("CONCAT(f_name, ' ', l_name) like ?", ["%{$searchValue}%"])
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->when(is_numeric($searchValue), fn (Builder $query) => $query->orWhere('id', $searchValue))
                    ->orWhereHas('shop', fn (Builder $shop) => $shop->where('name', 'like', "%{$searchValue}%"));
            }));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest('id'),
            self::SORT_OLDEST => $query->oldest('id'),
            self::SORT_NAME_ASC => $query->orderBy('f_name'),
            self::SORT_WISHLIST_COUNT => $query->orderByDesc('wishlist_count'),
            self::SORT_TOTAL_EARNING => $query->withSum('wallet as total_earning_sum', 'total_earning')
                ->orderByDesc('total_earning_sum'),
            default => $query->latest('id'),
        };

        if (! empty($filters[self::FILTER_STATUS_EXCEPT])) {
            $query->whereNotIn('status', (array) $filters[self::FILTER_STATUS_EXCEPT]);
        }

        if (! empty($filters[self::FILTER_HAS_EARNING])) {
            $query->whereHas('wallet', fn (Builder $wallet) => $wallet->where('total_earning', '>', 0));
        }

        if (! empty($filters[self::FILTER_HAS_WISHLISTED_PRODUCTS])) {
            $query->whereHas('product', fn (Builder $product) => $product->whereHas('wishList'));
        }

        if (! empty($filters[self::FILTER_HAS_APPROVED_PRODUCTS_IN_BUSINESS_MODULE])) {
            $query->whereHas('product', fn (Builder $product) => $product
                ->businessModule($filters[self::FILTER_HAS_APPROVED_PRODUCTS_IN_BUSINESS_MODULE])
                ->where('request_status', ProductService::REQUEST_STATUS_APPROVED));
        }

        unset(
            $filters[self::FILTER_STATUS_EXCEPT],
            $filters[self::FILTER_HAS_EARNING],
            $filters[self::FILTER_HAS_WISHLISTED_PRODUCTS],
            $filters[self::FILTER_HAS_APPROVED_PRODUCTS_IN_BUSINESS_MODULE],
        );

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function getRandomApprovedVendors(int $limit): Collection
    {
        return $this->query()
            ->approved()
            ->whereHas('shop')
            ->with(['shop'])
            ->withCount([
                'product' => fn (Builder $builder) => $builder->active(),
                'reviews as review_count' => fn (Builder $builder) => $builder
                    ->withoutGlobalScope('active')
                    ->where('reviews.status', 1),
            ])
            ->withAvg(['reviews as average_rating' => fn (Builder $builder) => $builder
                ->withoutGlobalScope('active')
                ->where('reviews.status', 1)], 'rating')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
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

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function create(array $data, ?UploadedFile $image = null): ?Model
    {
        return $this->query()->create([
            ...$data,
            ...(isset($data['password']) ? ['password' => bcrypt($data['password'])] : []),
            'image' => $image instanceof UploadedFile
                ? $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $image)
                : ($data['image'] ?? self::DEFAULT_IMAGE),
        ]);
    }

    public function update(mixed $id, array $data, ?UploadedFile $image = null): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(array_filter([
            'f_name' => $data['f_name'] ?? null,
            'l_name' => $data['l_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'app_language' => $data['app_language'] ?? null,
            'cm_firebase_token' => $data['cm_firebase_token'] ?? null,
            'image' => $image instanceof UploadedFile
                ? $this->replaceImage(dir: self::IMAGE_DIRECTORY, oldImage: $record->image, format: 'webp', image: $image)
                : null,
        ], static fn ($value) => $value !== null));

        return $record->refresh();
    }

    public function updatePassword(mixed $id, string $password): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->update(['password' => bcrypt($password)]);
    }

    public function updateBankInfo(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(array_filter([
            'bank_name' => $data['bank_name'] ?? null,
            'branch' => $data['branch'] ?? null,
            'holder_name' => $data['holder_name'] ?? null,
            'account_no' => $data['account_no'] ?? null,
        ], static fn ($value) => $value !== null));

        return $record->refresh();
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
        $joinedBetween = $filters[self::FILTER_JOINED_BETWEEN] ?? [];
        unset($filters[self::FILTER_JOINED_BETWEEN]);

        $query = $this->query()
            ->when($joinedBetween, fn (Builder $builder) => $builder->whereBetween('created_at', $joinedBetween));

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function hasVisibleWebsite(int $vendorId): bool
    {
        if (! Schema::hasTable(self::WEBSITE_CONFIG_TABLE)) {
            return false;
        }

        return (bool) DB::table(self::WEBSITE_CONFIG_TABLE)->where('tenant_id', $vendorId)->value('website_visibility');
    }

    /** @return array<int, string> */
    public function getShopOptions(): array
    {
        return $this->query()
            ->approved()
            ->whereHas('shop')
            ->with('shop:id,seller_id,name')
            ->get(['id'])
            ->pluck('shop.name', 'id')
            ->all();
    }

    public function grantVerifiedBadge(array $ids, ?int $adminId = null): int
    {
        if ($ids === []) {
            return 0;
        }

        return $this->query()
            ->whereIn('id', $ids)
            ->where('status', self::STATUS_APPROVED)
            ->where('is_verified', 0)
            ->update([
                'is_verified' => 1,
                'verified_at' => now(),
                'verified_by' => $adminId,
                'verified_notice_seen_at' => null,
            ]);
    }

    public function revokeVerifiedBadge(mixed $id): bool
    {
        return (bool) $this->query()
            ->where('id', $id)
            ->where('is_verified', 1)
            ->update([
                'is_verified' => 0,
                'verified_at' => null,
                'verified_by' => null,
                'verified_notice_seen_at' => null,
            ]);
    }

    public function markVerifiedNoticeSeen(mixed $id): bool
    {
        return (bool) $this->query()
            ->where('id', $id)
            ->where('is_verified', 1)
            ->whereNull('verified_notice_seen_at')
            ->update(['verified_notice_seen_at' => now()]);
    }

    public function getVerificationCandidates(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
    ): Collection|LengthAwarePaginator {
        $query = $this->verificationCandidateQuery(searchValue: $searchValue)->with($relations);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function countVerificationCandidates(): int
    {
        return $this->verificationCandidateQuery()->toBase()->getCountForPagination();
    }

    /** @return array<string, float|int> */
    public function getVerificationMetrics(mixed $id): array
    {
        $vendor = $this->withVerificationMetrics($this->query())->find($id);

        return $vendor === null ? [] : $this->presentVerificationMetrics($vendor);
    }

    /** @return array<string, float|int> */
    public function presentVerificationMetrics(Model $vendor): array
    {
        return [
            'completed_orders' => (int) $vendor->completed_orders_count,
            'terminal_orders' => (int) $vendor->terminal_orders_count,
            'account_age_years' => $vendor->created_at === null ? 0.0 : round(abs($vendor->created_at->diffInYears(now())), 4),
            'recent_reviews' => (int) $vendor->recent_reviews_count,
            'recent_positive_reviews' => (int) $vendor->recent_positive_reviews_count,
            'previous_reviews' => (int) $vendor->previous_reviews_count,
            'previous_positive_reviews' => (int) $vendor->previous_positive_reviews_count,
            'average_rating' => round((float) $vendor->average_rating, 2),
        ];
    }

    private function verificationCandidateQuery(?string $searchValue = null): Builder
    {
        $thresholds = VendorVerificationCriteria::thresholds();

        return $this->withVerificationMetrics($this->query())
            ->approved()
            ->where('is_verified', 0)
            ->whereHas('shop')
            ->where('created_at', '<=', VendorVerificationCriteria::accountCreatedCutoff())
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('f_name', 'like', "%{$searchValue}%")
                    ->orWhere('l_name', 'like', "%{$searchValue}%")
                    ->orWhereHas('shop', fn (Builder $shop) => $shop->where('name', 'like', "%{$searchValue}%"));
            }))
            ->havingRaw('completed_orders_count >= ?', [$thresholds[VendorVerificationCriteria::COMPLETED_ORDERS]])
            ->havingRaw(
                '(completed_orders_count / NULLIF(terminal_orders_count, 0)) * 100 >= ?',
                [$thresholds[VendorVerificationCriteria::ORDER_COMPLETION_RATE]],
            )
            ->havingRaw('recent_reviews_count > 0')
            ->havingRaw(
                '(recent_positive_reviews_count / NULLIF(recent_reviews_count, 0)) * 100 >= ?',
                [$thresholds[VendorVerificationCriteria::POSITIVE_FEEDBACK_TREND]],
            )
            ->havingRaw(
                '(recent_positive_reviews_count / NULLIF(recent_reviews_count, 0)) >= COALESCE(previous_positive_reviews_count / NULLIF(previous_reviews_count, 0), 0)',
            )
            ->havingRaw('average_rating >= ?', [$thresholds[VendorVerificationCriteria::AVERAGE_RATING]])
            ->orderByDesc('completed_orders_count');
    }

    private function withVerificationMetrics(Builder $query): Builder
    {
        $windowDays = VendorVerificationCriteria::feedbackTrendWindowDays();
        $positiveRating = VendorVerificationCriteria::positiveReviewRating();
        $recentWindowStart = now()->subDays($windowDays);
        $previousWindowStart = now()->subDays($windowDays * 2);

        return $query
            ->withCount([
                'orders as completed_orders_count' => fn (Builder $builder) => $builder
                    ->where('seller_is', self::SELLER_IS_SELLER)
                    ->where('order_status', self::DELIVERED_ORDER_STATUS),
                'orders as terminal_orders_count' => fn (Builder $builder) => $builder
                    ->where('seller_is', self::SELLER_IS_SELLER)
                    ->whereIn('order_status', self::TERMINAL_ORDER_STATUSES),
                'reviews as recent_reviews_count' => fn (Builder $builder) => $builder
                    ->withoutGlobalScope('active')
                    ->where('reviews.status', 1)
                    ->where('reviews.created_at', '>=', $recentWindowStart),
                'reviews as recent_positive_reviews_count' => fn (Builder $builder) => $builder
                    ->withoutGlobalScope('active')
                    ->where('reviews.status', 1)
                    ->where('reviews.created_at', '>=', $recentWindowStart)
                    ->where('reviews.rating', '>=', $positiveRating),
                'reviews as previous_reviews_count' => fn (Builder $builder) => $builder
                    ->withoutGlobalScope('active')
                    ->where('reviews.status', 1)
                    ->whereBetween('reviews.created_at', [$previousWindowStart, $recentWindowStart]),
                'reviews as previous_positive_reviews_count' => fn (Builder $builder) => $builder
                    ->withoutGlobalScope('active')
                    ->where('reviews.status', 1)
                    ->whereBetween('reviews.created_at', [$previousWindowStart, $recentWindowStart])
                    ->where('reviews.rating', '>=', $positiveRating),
            ])
            ->withAvg(['reviews as average_rating' => fn (Builder $builder) => $builder
                ->withoutGlobalScope('active')
                ->where('reviews.status', 1)], 'rating');
    }

    /** @return array<int, int> */
    public function getIdsMatchingAnyNameWord(string $searchValue): array
    {
        return $this->query()
            ->where(function (Builder $matched) use ($searchValue): void {
                foreach (explode(' ', $searchValue) as $word) {
                    $matched->orWhere('f_name', 'like', "%{$word}%")
                        ->orWhere('l_name', 'like', "%{$word}%");
                }
            })
            ->pluck('id')
            ->all();
    }
}
