<?php

namespace App\Services\New;

use App\Events\RestockProductNotificationEvent;
use App\Models\Product;
use App\Models\RestockProduct;
use App\Models\RestockProductCustomer;
use App\Models\Translation;
use App\Services\BaseService;
use App\Traits\BusinessModuleScopeTrait;
use App\Traits\FileManagerTrait;
use App\Utils\Helpers;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator as CollectionPaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

abstract class ProductService extends BaseService
{
    use BusinessModuleScopeTrait;
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }

    public const SHIFT_BRAND = 'brand';

    public const SHIFT_CATEGORY = 'category';

    public const SHIFT_SUB_CATEGORY = 'sub_category';

    public const SHIFT_SUB_SUB_CATEGORY = 'sub_sub_category';

    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const SORT_BEST_SELLING = 'best-selling';

    public const SORT_MOST_FAVORITE = 'most-favorite';

    public const SORT_MOST_REVIEWED = 'most-reviewed';

    public const SORT_STOCK_ASC = 'quantity_asc';

    public const SORT_STOCK_DESC = 'quantity_desc';

    public const SORT_ORDERS_ASC = 'order_asc';

    public const SORT_ORDERS_DESC = 'order_desc';

    public const SORT_ID_ASC = 'default';

    public const STOCK_SORTS = [
        self::SORT_STOCK_ASC,
        self::SORT_STOCK_DESC,
        self::SORT_ORDERS_ASC,
        self::SORT_ORDERS_DESC,
        self::SORT_ID_ASC,
    ];

    public const SORT_RANDOM = 'random';

    public const SORT_PRICE_ASC = 'price_asc';

    public const SORT_NAME_ASC = 'a-z';

    protected const CUSTOMER_REVIEW_COUNT_ALIAS = 'customer_reviews_count';

    protected const REVIEW_STATUS_ACTIVE = 1;

    public const TAX_TYPE_PERCENT = 'percent';

    public const TAX_TYPE_FLAT = 'flat';

    public const TAX_MODEL_EXCLUDE = 'exclude';

    public const BASE_LANGUAGE = 'en';

    public const ADDED_BY_ADMIN = 'admin';

    public const ADDED_BY_SELLER = 'seller';

    public const ADDED_BY_IN_HOUSE = 'in_house';

    public const DELIVERY_STATUS_DELIVERED = 'delivered';

    /** @var array<int, string> */
    public const STOCK_RESTORING_ORDER_STATUSES = ['returned', 'failed', 'canceled'];

    public const PRODUCT_TYPE_PHYSICAL = 'physical';

    public const PRODUCT_TYPE_DIGITAL = 'digital';

    public const DISCOUNT_TYPE_FLAT = 'flat';

    public const DISCOUNT_TYPE_PERCENT = 'percent';

    public const REQUEST_STATUS_PENDING = 0;

    public const REQUEST_STATUS_APPROVED = 1;

    public const REQUEST_STATUS_DENIED = 2;

    public const REQUEST_STATE_ALL = 'all';

    public const REQUEST_STATE_NEW = 'new';

    public const REQUEST_STATE_SHIPPING_UPDATE = 'update';

    public const REQUEST_STATE_APPROVED = 'approved';

    public const REQUEST_STATE_DENIED = 'denied';

    public const IMAGE_DIRECTORY = 'product/';

    public const THUMBNAIL_DIRECTORY = 'product/thumbnail/';

    public const META_DIRECTORY = 'product/meta/';

    public const REASON_INVALID_FILE = 'invalid-file';

    public const REASON_EMPTY_FILE = 'empty-file';

    public const REASON_UNKNOWN_COLUMN = 'unknown-column';

    public const REASON_MISSING_VALUE = 'missing-value';

    private const DIGITAL_PRODUCT_STOCK = 999999999;

    protected const SEARCH_NOISE_CHARACTERS = ['\'', '"', ',', ';', '<', '>', '?', '\\'];

    public const FILTER_ACTIVE = 'active';

    public const FILTER_CREATED_BETWEEN = 'created_between';

    public const FILTER_EXCLUDE_IDS = 'exclude_ids';

    public const FILTER_FLASH_DEAL = 'flash_deal_id';

    public const FILTER_FEATURED_DEAL = 'featured_deal_id';

    public const FILTER_DISCOUNTED_OR_IDS = 'discounted_or_ids';

    public const SORT_NEWEST_ID = 'newest-id';

    public const SORT_OLDEST_ID = 'oldest-id';

    public const OWNER_IN_HOUSE = 'in-house';

    public const BARCODE_LAYOUT_DEFAULT = 'default';

    public const BARCODE_LAYOUT_COMPACT = 'compact';

    public const BARCODE_LAYOUTS = [self::BARCODE_LAYOUT_DEFAULT, self::BARCODE_LAYOUT_COMPACT];

    public const BARCODE_MAX_QUANTITY = 270;

    public const BARCODE_DEFAULT_QUANTITY = 4;

    public const REFUND_STATUS_APPROVED = 'approved';

    public const IMAGE_TYPE_PRODUCT = 'product';

    private const BARCODE_LABELS_PER_PAGE = [
        self::BARCODE_LAYOUT_DEFAULT => 21,
        self::BARCODE_LAYOUT_COMPACT => 1,
    ];

    /** @var array<string, string> */
    private const PRIORITY_FALLBACK_SORTS = [
        'top_rated_product_list_priority' => 'reviews_avg_rating',
        'best_selling_product_list_priority' => 'order_details_count',
        'new_arrival_product_list_priority' => 'order_details_count',
    ];

    /** @var array<int, string> */
    public const PRIORITY_SORTS = [
        'flash_deal_priority',
        'feature_deal_priority',
        'stock_clearance_product_list_priority',
        'featured_product_priority',
        'top_rated_product_list_priority',
        'best_selling_product_list_priority',
        'new_arrival_product_list_priority',
    ];

    private const INDEXABLE_CHUNK_SIZE = 1000;

    /** @var array<int, string> */
    private const ASSISTANT_GENERIC_QUERY_TERMS = [
        'product', 'products', 'item', 'items', 'thing', 'things', 'stuff',
        'good', 'goods', 'merchandise', 'catalog', 'catalogue',
        'trending', 'popular', 'bestseller', 'bestsellers', 'best-seller',
        'all', 'everything', 'anything', 'something', 'available',
        'vendor', 'vendors', 'store', 'stores', 'shop', 'shops',
        'seller', 'sellers', 'brand', 'brands',
    ];

    /** @var array<int, string> */
    private const ASSISTANT_STOP_WORDS = [
        'the', 'and', 'for', 'with', 'this', 'that', 'any', 'some', 'one',
        'please', 'want', 'need', 'buy', 'show', 'find', 'get', 'looking',
        'have', 'you', 'your', 'are', 'phone', 'mobile', 'smartphone', 'device',
    ];

    public function __construct(Product $product)
    {
        parent::__construct($product);
    }

    abstract protected function businessModule(): string;

    /** @return array{image_names: array, colored_image_names: array} */
    public function processImages(array $data, array $files): array
    {
        $storage = $this->storageDisk();
        $images = [];

        foreach ($data['existing_images'] ?? [] as $imageName) {
            $images[] = ['image_name' => $imageName, 'storage' => $storage];
        }

        foreach ($files['images'] ?? [] as $file) {
            $images[] = [
                'image_name' => $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $file),
                'storage' => $storage,
            ];
        }

        return ['image_names' => $images, 'colored_image_names' => []];
    }

    /** @return array{image_names: array, colored_image_names: array} */
    public function processUpdateImages(array $data, array $files, Model $product): array
    {
        $keptNames = $data['existing_images'] ?? [];
        $storage = $this->storageDisk();

        $images = array_values(array_filter(
            $this->storedGallery($product),
            static fn (array $image): bool => in_array($image['image_name'], $keptNames, true),
        ));

        foreach ($files['images'] ?? [] as $file) {
            $images[] = [
                'image_name' => $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $file),
                'storage' => $storage,
            ];
        }

        return ['image_names' => $images, 'colored_image_names' => []];
    }

    protected function query(): Builder
    {
        return parent::query()->businessModule($this->businessModule());
    }

    public function isCodeTaken(string $code, mixed $exceptId = null): bool
    {
        return $this->model->newQuery()
            ->where('code', $code)
            ->when($exceptId !== null, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function listFilters(
        string $state,
        ?string $addedBy = null,
        mixed $sellerId = null,
        array $catalogFilters = [],
        array $statuses = [],
    ): array {
        $statuses = $this->supportsStatusFilter($state) ? $statuses : [];
        $stateForStatusFilter = $statuses === [] ? $state : self::REQUEST_STATE_APPROVED;

        return array_filter([
            ...$catalogFilters,
            ...$this->stateFilters($stateForStatusFilter),
            'added_by' => $addedBy,
            'seller_id' => is_numeric($sellerId) ? (int) $sellerId : null,
            'status' => $statuses,
        ], static fn ($value): bool => $value !== null && $value !== []);
    }

    public function supportsStatusFilter(string $state): bool
    {
        return in_array($state, [self::REQUEST_STATE_ALL, self::REQUEST_STATE_APPROVED], true);
    }

    public function resolveOwner(?string $type): string
    {
        return match ($type) {
            'in-house', self::ADDED_BY_IN_HOUSE, self::ADDED_BY_ADMIN => self::ADDED_BY_IN_HOUSE,
            default => self::ADDED_BY_SELLER,
        };
    }

    public function resolveRequestState(mixed $value): string
    {
        return match ((string) $value) {
            (string) self::REQUEST_STATUS_PENDING, 'new-request', self::REQUEST_STATE_NEW => self::REQUEST_STATE_NEW,
            (string) self::REQUEST_STATUS_APPROVED, self::REQUEST_STATE_APPROVED => self::REQUEST_STATE_APPROVED,
            (string) self::REQUEST_STATUS_DENIED, self::REQUEST_STATE_DENIED => self::REQUEST_STATE_DENIED,
            self::REQUEST_STATE_SHIPPING_UPDATE => self::REQUEST_STATE_SHIPPING_UPDATE,
            default => self::REQUEST_STATE_ALL,
        };
    }

    private function stateFilters(string $state): array
    {
        return match ($state) {
            self::REQUEST_STATE_NEW => ['request_status' => self::REQUEST_STATUS_PENDING],
            self::REQUEST_STATE_APPROVED => ['request_status' => self::REQUEST_STATUS_APPROVED],
            self::REQUEST_STATE_DENIED => ['request_status' => self::REQUEST_STATUS_DENIED],
            self::REQUEST_STATE_SHIPPING_UPDATE => ['is_shipping_cost_updated' => 0],
            default => [],
        };
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
        $query = $this->relationQuery($relations)->withCount($this->withReviewCountForSort($withCount, $sortBy));

        if (! empty($filters[self::FILTER_ACTIVE])) {
            $query->active();
        }

        if (! empty($filters[self::FILTER_EXCLUDE_IDS])) {
            $query->whereNotIn('id', $filters[self::FILTER_EXCLUDE_IDS]);
        }

        if (! empty($filters[self::FILTER_FLASH_DEAL])) {
            $query->flashDeal($filters[self::FILTER_FLASH_DEAL]);
        }

        unset($filters[self::FILTER_ACTIVE], $filters[self::FILTER_EXCLUDE_IDS], $filters[self::FILTER_FLASH_DEAL]);

        $this->applyOwnershipFilters($query, $filters);
        $this->applySearch($query, $searchValue);
        $this->applyColumnFilters($query, $filters);

        in_array($sortBy, static::PRIORITY_SORTS, true)
            ? $this->applyPriority($query, $sortBy)
            : $this->applySort($query, $sortBy);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function getIndexableSlugs(): LazyCollection
    {
        return $this->query()
            ->withoutGlobalScope('translate')
            ->select(['id', 'slug', 'updated_at'])
            ->active()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->lazyById(self::INDEXABLE_CHUNK_SIZE);
    }

    public function find(mixed $id, array $relations = [], array $withCount = []): ?Model
    {
        return $this->relationQuery($relations)->withCount($withCount)->find($id);
    }

    public function findUnscoped(mixed $id, array $relations = []): ?Model
    {
        return $this->query()
            ->withoutGlobalScopes()
            ->with(['translations', 'reviews', ...$relations])
            ->find($id);
    }

    public function findBy(array $filters = [], array $relations = []): ?Model
    {
        $query = $this->relationQuery($relations);

        $this->applyFilters($query, $filters);

        return $query->first();
    }

    public function getRandomDiscountedProduct(array $relations = []): ?Model
    {
        return $this->relationQuery($relations)
            ->active()
            ->where('discount', '>', 0)
            ->inRandomOrder()
            ->first();
    }

    public function findActiveBy(array $filters = [], array $relations = [], array $withCount = []): ?Model
    {
        $query = $this->relationQuery($relations)->active()->withCount($withCount);

        $this->applyFilters($query, $filters);

        return $query->first();
    }

    public function isVisibleOnStorefront(mixed $id): bool
    {
        return $this->query()
            ->active()
            ->whereKey($id)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->exists();
    }

    public function canBeActivated(Model $product): bool
    {
        return $product->added_by !== self::ADDED_BY_SELLER
            || (int) $product->request_status === self::REQUEST_STATUS_APPROVED;
    }

    public function isDenied(Model $product): bool
    {
        return (int) $product->request_status === self::REQUEST_STATUS_DENIED;
    }

    public function isApprovedOnAdminEdit(Model $product): bool
    {
        return $product->added_by === self::ADDED_BY_SELLER
            && in_array((int) $product->request_status, [self::REQUEST_STATUS_PENDING, self::REQUEST_STATUS_DENIED], true);
    }

    private function applyPriority(Builder $query, string $configName): void
    {
        $priority = getWebConfig(name: $configName);

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            $fallbackColumn = self::PRIORITY_FALLBACK_SORTS[$configName] ?? null;

            match ($fallbackColumn) {
                null => $query->orderByDesc('id'),
                'reviews_avg_rating' => $this->orderByHighestRating($query),
                default => $query->orderByDesc($this->sortColumn($fallbackColumn)),
            };

            return;
        }

        $this->applyTemporaryCloseHide($query, $priority['temporary_close_sorting'] ?? null);

        if (($priority['out_of_stock_product'] ?? null) === 'hide') {
            $this->applyInStockOnly($query);
        }

        if (($priority['temporary_close_sorting'] ?? null) === 'desc') {
            $query->orderByRaw($this->shopTemporaryCloseExpression());
        }

        if (($priority['out_of_stock_product'] ?? null) === 'desc') {
            $query->orderByRaw($this->inStockSortExpression());
        }

        match ($priority['sort_by'] ?? null) {
            'latest_created' => $query->orderByDesc('id'),
            'first_created' => $query->orderBy('id'),
            'most_order' => $query->orderByDesc($this->sortColumn('order_details_count')),
            'reviews_count' => $query->orderByDesc($this->sortColumn('reviews_count')),
            'rating' => $this->orderByHighestRating($query),
            'a_to_z' => $query->orderBy('name'),
            'z_to_a' => $query->orderByDesc('name'),
            default => $query->orderByDesc('id'),
        };
    }

    protected function orderByHighestRating(Builder $query): Builder
    {
        return $query
            ->orderByDesc($this->sortColumn('reviews_avg_rating'))
            ->orderByDesc($this->sortColumn('reviews_count'))
            ->orderByDesc('products.id');
    }

    private function listSortColumn(string $alias): string|Expression
    {
        return config('product.sort_source', 'computed') === 'denormalized' ? $this->sortColumn($alias) : $alias;
    }

    protected function sortColumn(string $alias): string|Expression
    {
        if (config('product.sort_source', 'computed') === 'denormalized') {
            return [
                'reviews_avg_rating' => 'rating_avg',
                'reviews_count' => 'rating_count',
                'order_details_count' => 'order_count',
            ][$alias] ?? $alias;
        }

        return match ($alias) {
            'order_details_count' => DB::raw('(select count(*) from order_details where order_details.product_id = products.id)'),
            'reviews_count' => DB::raw('(select count(*) from reviews where reviews.product_id = products.id and reviews.status = 1)'),
            'reviews_avg_rating' => DB::raw('(select avg(reviews.rating) from reviews where reviews.product_id = products.id and reviews.status = 1)'),
            default => $alias,
        };
    }

    protected function shopTemporaryCloseExpression(): string
    {
        $inHouseClosed = Cache::get(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS) ? 1 : 0;

        return "CASE WHEN products.added_by = 'admin' THEN {$inHouseClosed}
            ELSE COALESCE((SELECT shops.temporary_close FROM shops WHERE shops.seller_id = products.user_id LIMIT 1), 0) END";
    }

    public function getActiveOptions(): Collection
    {
        return $this->query()->active()->get(['id', 'name', 'slug']);
    }

    /** @return array<string, int> */
    public function getSellerProductCountsByBusinessModule(int $sellerId): array
    {
        return $this->model->newQuery()
            ->where(['added_by' => self::ADDED_BY_SELLER, 'user_id' => $sellerId])
            ->selectRaw('business_module, COUNT(*) as total')
            ->groupBy('business_module')
            ->pluck('total', 'business_module')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        if (! empty($filters[self::FILTER_ACTIVE])) {
            $query->active();
        }

        if (! empty($filters[self::FILTER_CREATED_BETWEEN])) {
            $query->whereBetween('created_at', $filters[self::FILTER_CREATED_BETWEEN]);
        }

        if (isset($filters[self::FILTER_DISCOUNTED_OR_IDS])) {
            $ids = (array) $filters[self::FILTER_DISCOUNTED_OR_IDS];

            $query->where(fn (Builder $builder) => $builder
                ->where('discount', '!=', 0)
                ->when($ids !== [], fn (Builder $inner) => $inner->orWhereIn('id', $ids)));
        }

        if (! empty($filters[self::FILTER_EXCLUDE_IDS])) {
            $query->whereNotIn('id', $filters[self::FILTER_EXCLUDE_IDS]);
        }

        if (! empty($filters[self::FILTER_FLASH_DEAL])) {
            $query->flashDeal($filters[self::FILTER_FLASH_DEAL]);
        }

        if (! empty($filters[self::FILTER_FEATURED_DEAL])) {
            $query->featuredDeal($filters[self::FILTER_FEATURED_DEAL]);
        }

        unset(
            $filters[self::FILTER_ACTIVE],
            $filters[self::FILTER_CREATED_BETWEEN],
            $filters[self::FILTER_DISCOUNTED_OR_IDS],
            $filters[self::FILTER_EXCLUDE_IDS],
            $filters[self::FILTER_FLASH_DEAL],
            $filters[self::FILTER_FEATURED_DEAL],
        );

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function getActiveProductIds(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        return $this->query()->active()->whereIn('id', $productIds)->pluck('id')->all();
    }

    public function getIds(array $filters = [], ?string $searchValue = null): array
    {
        $query = $this->query();

        if (! empty($filters[self::FILTER_ACTIVE])) {
            $query->active();
        }

        $this->applyOwnershipFilters($query, $filters);
        $this->applySearch($query, $searchValue);
        $this->applyColumnFilters($query, $filters);

        return $query->pluck('id')->toArray();
    }

    /**
     * @param  array<int, int>  $sellerIds
     * @return array<int, int>
     */
    public function getCountsBySeller(array $sellerIds): array
    {
        if ($sellerIds === []) {
            return [];
        }

        return $this->query()
            ->where('added_by', self::ADDED_BY_SELLER)
            ->whereIn('user_id', $sellerIds)
            ->selectRaw('user_id, COUNT(*) AS seller_count')
            ->groupBy('user_id')
            ->pluck('seller_count', 'user_id')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function getSellerProductIdsMatchingAllWords(int $sellerId, string $searchValue): array
    {
        return $this->query()
            ->where('added_by', self::ADDED_BY_SELLER)
            ->where('user_id', $sellerId)
            ->where(function (Builder $matched) use ($searchValue): void {
                foreach (explode(' ', $searchValue) as $word) {
                    $matched->where('name', 'like', "%{$word}%");
                }
            })
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function getSellerRestockSearchIds(int $sellerId, string $searchValue): array
    {
        $translatedIds = $this->translatedIds($searchValue);

        return $this->query()
            ->where('added_by', self::ADDED_BY_SELLER)
            ->where('user_id', $sellerId)
            ->where(fn (Builder $matched) => $matched
                ->where('name', 'like', "%{$searchValue}%")
                ->orWhere('code', 'like', "%{$searchValue}%")
                ->orWhereIn('id', $translatedIds))
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function getActiveIds(array $filters = []): array
    {
        $query = $this->query()->active();

        $this->applyOwnershipFilters($query, $filters);
        $this->applyColumnFilters($query, $filters);

        return $query->pluck('id')->all();
    }

    public function getInHouseIds(): array
    {
        return $this->query()
            ->withoutGlobalScope('translate')
            ->where('added_by', self::ADDED_BY_ADMIN)
            ->pluck('id')
            ->toArray();
    }

    /**
     * @return array<int, int>
     */
    public function getActiveInHouseIds(): array
    {
        return $this->query()
            ->active()
            ->where('added_by', self::ADDED_BY_ADMIN)
            ->pluck('id')
            ->all();
    }

    public function getActiveIdsBySellers(array $sellerIds, bool $includeInHouse = false): array
    {
        if ($sellerIds === [] && ! $includeInHouse) {
            return [];
        }

        $idsByOwner = [];

        if ($includeInHouse) {
            $idsByOwner[self::OWNER_IN_HOUSE] = $this->activeIdQuery()
                ->where('added_by', self::ADDED_BY_ADMIN)
                ->pluck('id')
                ->all();
        }

        if ($sellerIds !== []) {
            $rows = $this->activeIdQuery()
                ->where('added_by', self::ADDED_BY_SELLER)
                ->whereIn('user_id', $sellerIds)
                ->get(['id', 'user_id']);

            foreach ($rows as $row) {
                $idsByOwner[(int) $row->user_id][] = (int) $row->id;
            }
        }

        return $idsByOwner;
    }

    private function activeIdQuery(): Builder
    {
        return $this->query()->withoutGlobalScope('translate')->active();
    }

    public function getStockLimitList(
        int $stockLimit,
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()->with($relations)->withCount($withCount);

        $this->applyOwnershipFilters($query, $filters);
        $this->applySearch($query, $searchValue);

        $stockLimit <= 0
            ? $query->where('current_stock', 0)
            : $query->where('current_stock', '<=', $stockLimit);

        $this->applyColumnFilters($query, $filters);

        in_array($sortBy, self::STOCK_SORTS, true)
            ? $this->applyStockSort($query, $sortBy)
            : $this->applySort($query, $sortBy);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    /** @return array{active: int, inactive: int} */
    public function getStatusCounts(?string $searchValue = null, array $filters = []): array
    {
        $query = $this->query();

        $this->applyOwnershipFilters($query, $filters);
        $this->applySearch($query, $searchValue);
        $this->applyColumnFilters($query, array_diff_key($filters, ['status' => null]));

        $counts = $query->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'active' => (int) ($counts[1] ?? 0),
            'inactive' => (int) ($counts[0] ?? 0),
        ];
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        cacheRemoveByType(type: 'products');

        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
    }

    public function create(
        array $data,
        array $files = [],
        string $addedBy = self::ADDED_BY_ADMIN,
        ?int $userId = null,
        int|string $shopId = 0,
        array $colorNames = [],
        ?array $images = null,
    ): ?Model {
        $images ??= $this->processImages($data, $files);
        $variations = $this->buildVariations($data, $colorNames);

        $attributes = $this->buildCommonAttributes($data, $images, $variations, $addedBy, $userId, $shopId, $files);

        return DB::transaction(function () use ($attributes): Model {
            $product = $this->query()->create($attributes);

            cacheRemoveByType(type: 'products');

            return $product;
        });
    }

    public function update(
        mixed $id,
        array $data,
        array $files = [],
        string $updateBy = self::ADDED_BY_ADMIN,
        array $colorNames = [],
        ?array $images = null,
    ): ?Model {
        $product = $this->findUnscoped($id);

        if ($product === null) {
            return null;
        }

        $images ??= $this->processUpdateImages($data, $files, $product);
        $variations = $this->buildVariations($data, $colorNames);

        $attributes = $this->buildCommonUpdateAttributes($data, $images, $variations, $product, $updateBy, $files);

        return DB::transaction(function () use ($product, $attributes): Model {
            $product->update($attributes);

            cacheRemoveByType(type: 'products');

            return $product->refresh();
        });
    }

    public function updateQuantity(mixed $id, int $currentStock): bool
    {
        $product = $this->query()->find($id);

        if ($product === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($product, $currentStock): bool {
            $product->update(['current_stock' => $currentStock]);

            cacheRemoveByType(type: 'products');

            return true;
        });
    }

    public function findForCart(mixed $id): ?Model
    {
        return $this->query()
            ->with(['digitalVariation', 'retailDetail', 'seller.shop', 'clearanceSale' => fn ($query) => $query->active()])
            ->where('id', $id)
            ->first();
    }

    public function getActiveCartProducts(array $ids): Collection
    {
        return $this->query()
            ->active()
            ->whereIn('id', $ids)
            ->with(['digitalVariation', 'retailDetail', 'seller.shop'])
            ->get()
            ->keyBy('id');
    }

    /** @return array{is_stock_decreased: int, delivery_status: string}|null */
    public function updateStockForOrderDetail(object|array $orderDetail, string $status): ?array
    {
        $isRestoring = in_array($status, self::STOCK_RESTORING_ORDER_STATUSES, true);

        if ($orderDetail['is_stock_decreased'] != ($isRestoring ? 1 : 0)) {
            return null;
        }

        $product = $this->model->newQuery()->with(['retailDetail'])->find($orderDetail['product_id']);

        if ($product === null) {
            return null;
        }

        $quantityChange = $isRestoring ? $orderDetail['qty'] : -$orderDetail['qty'];
        $variationData = [];

        foreach (json_decode($product->retailDetail?->variation ?? '[]', true) as $variation) {
            if ($orderDetail['variant'] == $variation['type']) {
                $variation['qty'] += $quantityChange;
            }
            $variationData[] = $variation;
        }

        DB::transaction(function () use ($product, $variationData, $quantityChange): void {
            $this->model->newQuery()->where('id', $product['id'])->update([
                'current_stock' => $product['current_stock'] + $quantityChange,
            ]);
            $product->retailDetail()->updateOrCreate(
                ['product_id' => $product['id']],
                ['variation' => json_encode($variationData)],
            );
        });

        return ['is_stock_decreased' => $isRestoring ? 0 : 1, 'delivery_status' => $status];
    }

    /** @return array<string, mixed>|null */
    public function getOrderSnapshotAttributes(mixed $id): ?array
    {
        return $this->model->newQuery()
            ->where(['id' => $id])
            ->with(['digitalVariation', 'clearanceSale' => fn ($query) => $query->active()])
            ->first()
            ?->toArray();
    }

    public function deductStockForOrderItem(array $product, mixed $variant, int|float $quantity): bool
    {
        if (($product['product_type'] ?? null) !== self::PRODUCT_TYPE_PHYSICAL || $quantity <= 0) {
            return true;
        }

        return DB::transaction(fn (): bool => $this->deductVariationStock(productId: $product['id'], variant: $variant, quantity: $quantity)
            && $this->deductCurrentStock(productId: $product['id'], quantity: $quantity));
    }

    private function deductCurrentStock(mixed $productId, int|float $quantity): bool
    {
        return $this->model->newQuery()
            ->where(['id' => $productId])
            ->where('current_stock', '>=', $quantity)
            ->decrement('current_stock', $quantity) === 1;
    }

    private function deductVariationStock(mixed $productId, mixed $variant, int|float $quantity): bool
    {
        if (blank($variant)) {
            return true;
        }

        $retailDetail = $this->model->newFromBuilder(['id' => $productId])->retailDetail()->lockForUpdate()->first();
        $variations = json_decode((string) ($retailDetail['variation'] ?? ''), true);

        if (! is_array($variations) || $variations === []) {
            return true;
        }

        $isVariantFound = false;

        foreach ($variations as $index => $variation) {
            if (($variation['type'] ?? null) != $variant) {
                continue;
            }

            if ((float) ($variation['qty'] ?? 0) < $quantity) {
                return false;
            }

            $variations[$index]['qty'] = $variation['qty'] - $quantity;
            $isVariantFound = true;
        }

        return ! $isVariantFound || $this->model->newFromBuilder(['id' => $productId])->retailDetail()->updateOrCreate(
            ['product_id' => $productId],
            ['variation' => json_encode($variations)],
        ) !== null;
    }

    public function delete(mixed $id): bool
    {
        $product = $this->findUnscoped($id);

        if ($product === null) {
            return false;
        }

        $this->deleteStoredFiles($product);

        return (bool) DB::transaction(function () use ($product): bool {
            $this->deleteTranslations([$product->id]);
            $deleted = (bool) $product->delete();

            cacheRemoveByType(type: 'products');

            return $deleted;
        });
    }

    public function deleteMany(array $filters): bool
    {
        $query = $this->query();

        $this->applyColumnFilters($query, $filters);

        $productIds = $query->pluck('id')->toArray();

        if ($productIds === []) {
            return false;
        }

        return (bool) DB::transaction(function () use ($productIds): bool {
            $deleted = (bool) $this->query()->whereIn('id', $productIds)->delete();

            cacheRemoveByType(type: 'products');

            return $deleted;
        });
    }

    public function updateShift(string $type, mixed $fromId, mixed $toId): bool
    {
        if (! $toId) {
            return false;
        }

        return match ($type) {
            self::SHIFT_BRAND => (bool) $this->query()
                ->where('brand_id', $fromId)
                ->update(['brand_id' => $toId]),
            self::SHIFT_CATEGORY => (bool) $this->query()
                ->where('category_id', $fromId)
                ->update([
                    'category_ids' => json_encode([['id' => $toId, 'position' => 1]]),
                    'category_id' => $toId,
                    'sub_category_id' => null,
                    'sub_sub_category_id' => null,
                ]),
            default => false,
        };
    }

    public function updateCategoryPathShift(string $type, mixed $fromId, array $categoryPath): bool
    {
        $fromColumn = match ($type) {
            self::SHIFT_SUB_CATEGORY => 'sub_category_id',
            self::SHIFT_SUB_SUB_CATEGORY => 'sub_sub_category_id',
            default => null,
        };

        $categoryPath = array_values(array_filter($categoryPath));

        if ($fromColumn === null || $categoryPath === []) {
            return false;
        }

        return (bool) $this->query()
            ->where($fromColumn, $fromId)
            ->update([
                'category_ids' => json_encode(array_map(
                    fn (mixed $categoryId, int $index): array => ['id' => (string) $categoryId, 'position' => $index + 1],
                    $categoryPath,
                    array_keys($categoryPath),
                )),
                'category_id' => $categoryPath[0],
                'sub_category_id' => $categoryPath[1] ?? null,
                'sub_sub_category_id' => $categoryPath[2] ?? null,
            ]);
    }

    public function updateParentCategory(mixed $subCategoryId, mixed $parentCategoryId): void
    {
        $this->query()
            ->where('sub_category_id', $subCategoryId)
            ->update([
                'category_id' => $parentCategoryId,
                'category_ids' => DB::raw("JSON_SET(CAST(category_ids AS JSON), '$[0].id', '{$parentCategoryId}')"),
            ]);
    }

    public function getUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while ($this->isCodeTaken($code));

        return $code;
    }

    public function getDefaultContentLanguage(): string
    {
        $languages = getWebConfig(name: 'pnc_language') ?? [];

        return in_array(self::BASE_LANGUAGE, $languages, true)
            ? self::BASE_LANGUAGE
            : ($languages[0] ?? self::BASE_LANGUAGE);
    }

    public function syncTags(Model $product, ?string $tags): void
    {
        $names = array_filter(array_map('trim', explode(',', (string) $tags)), static fn (string $name): bool => $name !== '');

        $tagIds = [];

        foreach ($names as $name) {
            $tag = $product->tags()->getRelated()->newQuery()->firstOrCreate(['tag' => $name]);
            $tagIds[] = $tag->id;
        }

        $product->tags()->sync($tagIds);
    }

    /** @return array<int, array<int, string>> */
    public function getCombinations(array $options): array
    {
        return array_reduce(
            $options,
            static fn (array $carry, array $values): array => array_merge(...array_map(
                static fn (array $row): array => array_map(
                    static fn ($value): array => [...$row, $value],
                    $values,
                ),
                $carry,
            )),
            [[]],
        );
    }

    public function getOptions(array $data): array
    {
        $options = [];

        if ($this->hasColors($data)) {
            $options[] = $data['colors'];
        }

        foreach ($data['choice_no'] ?? [] as $no) {
            $options[] = array_values(array_filter(
                explode(',', implode('|', $data['choice_options_'.$no] ?? [])),
                static fn (string $value): bool => $value !== '',
            ));
        }

        return $options;
    }

    public function getChoiceOptions(array $data): array
    {
        $choiceOptions = [];

        foreach ($data['choice_no'] ?? [] as $index => $no) {
            $choiceOptions[] = [
                'name' => 'choice_'.$no,
                'title' => $data['choice'][$index] ?? null,
                'options' => explode(',', implode('|', $data['choice_options_'.$no] ?? [])),
            ];
        }

        return $choiceOptions;
    }

    public function getCategoriesArray(array $data): array
    {
        $levels = [
            1 => $data['category_id'] ?? null,
            2 => $data['sub_category_id'] ?? null,
            3 => $data['sub_sub_category_id'] ?? null,
        ];

        $categories = [];

        foreach ($levels as $position => $id) {
            if ($id !== null && $id !== '') {
                $categories[] = ['id' => (string) $id, 'position' => $position];
            }
        }

        return $categories;
    }

    public function getSlug(array $data): string
    {
        return Str::slug($this->baseLocaleValue($data, 'name'), '-').'-'.Str::random(6);
    }

    public function buildVariations(array $data, array $colorNames = []): array
    {
        $combinations = $this->getCombinations($this->getOptions($data));

        if (empty($combinations[0])) {
            return [];
        }

        $hasColors = $this->hasColors($data);
        $variations = [];

        foreach ($combinations as $combination) {
            $type = '';

            foreach ($combination as $index => $value) {
                if ($index > 0) {
                    $type .= '-'.str_replace(' ', '', $value);

                    continue;
                }

                $type .= $hasColors ? ($colorNames[$value] ?? $value) : str_replace(' ', '', $value);
            }

            $key = str_replace('.', '_', $type);

            $variations[] = [
                'type' => $type,
                'price' => currencyConverter(abs($data['price_'.$key] ?? 0)),
                'sku' => $data['sku_'.$key] ?? null,
                'qty' => abs($data['qty_'.$key] ?? 0),
            ];
        }

        return $variations;
    }

    public function getTotalQuantity(array $variations): int
    {
        return (int) array_sum(array_column($variations, 'qty'));
    }

    protected function applyOwnershipFilters(Builder $query, array $filters): void
    {
        $addedBy = $filters['added_by'] ?? null;

        if (in_array($addedBy, [self::ADDED_BY_IN_HOUSE, self::ADDED_BY_ADMIN], true)) {
            $query->where('added_by', self::ADDED_BY_ADMIN);
        } elseif ($addedBy !== null) {
            $query->where('added_by', self::ADDED_BY_SELLER);
        }

        if ($this->isSelectedFilter($filters, 'request_status')) {
            $query->where('request_status', $filters['request_status']);
        }

        if ($this->isSelectedFilter($filters, 'seller_id')) {
            $query->where('user_id', $filters['seller_id']);
        }
    }

    private function isSelectedFilter(array $filters, string $key): bool
    {
        $value = $filters[$key] ?? null;

        return $value !== null && $value !== '' && $value !== self::REQUEST_STATE_ALL;
    }

    protected function applySearch(Builder $query, ?string $searchValue): void
    {
        if (! $searchValue) {
            return;
        }

        $likeValue = $this->escapeLikeWildcards($searchValue);
        $translatedIds = $this->translatedIds($likeValue);

        $query->where(function (Builder $inner) use ($searchValue, $likeValue, $translatedIds): void {
            $this->applyNameSearch($inner, $searchValue);

            $inner->orWhere('code', 'like', "%{$likeValue}%")
                ->when(is_numeric($searchValue), fn (Builder $builder) => $builder->orWhere('id', $searchValue))
                ->orWhereIn('id', $translatedIds);
        });
    }

    protected function applyNameSearch(Builder $query, string $searchValue, array $terms = []): void
    {
        if (config('search.driver', 'like') === 'fulltext' && trim($searchValue) !== '') {
            $query->whereFullText('name', $searchValue);

            return;
        }

        foreach ($terms === [] ? [$searchValue] : $terms as $term) {
            $query->orWhere('name', 'like', '%'.$this->escapeLikeWildcards($term).'%');
        }
    }

    protected function escapeLikeWildcards(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    protected function applyTemporaryCloseHide(Builder $query, ?string $temporaryCloseSorting): void
    {
        if ($temporaryCloseSorting !== 'hide') {
            return;
        }

        $query->where(fn (Builder $outer) => $outer
            ->where('added_by', self::ADDED_BY_SELLER)
            ->whereHas('seller.shop', fn (Builder $shop) => $shop->where('temporary_close', 0))
            ->orWhere(fn (Builder $inner) => Cache::get(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS)
                ? $inner
                : $inner->where('added_by', self::ADDED_BY_ADMIN)));
    }

    protected function applyInStockOnly(Builder $query): void
    {
        $query->where(fn (Builder $outer) => $outer
            ->where('product_type', self::PRODUCT_TYPE_DIGITAL)
            ->orWhere(fn (Builder $inner) => $inner
                ->where('product_type', self::PRODUCT_TYPE_PHYSICAL)
                ->where('current_stock', '>', 0)));
    }

    protected function inStockSortExpression(): string
    {
        return "CASE WHEN products.product_type = 'digital' OR products.current_stock > 0 THEN 0 ELSE 1 END";
    }

    protected function mergeInStockFirst(Collection $products): Collection
    {
        $inStock = $products->filter(
            fn (Model $product) => $product->product_type === self::PRODUCT_TYPE_DIGITAL || $product->current_stock > 0
        );

        $outOfStock = $products->filter(
            fn (Model $product) => $product->current_stock <= 0 && $product->product_type !== self::PRODUCT_TYPE_DIGITAL
        );

        return $inStock->merge($outOfStock);
    }

    protected function normalizeSearchKeyword(string $keyword): string
    {
        return str_ireplace(self::SEARCH_NOISE_CHARACTERS, ' ', preg_replace('/\s\s+/', ' ', $keyword));
    }

    protected function applyColumnFilters(Builder $query, array $filters): void
    {
        $columns = [
            'shop_id' => 'shop_id',
            'brand_id' => 'brand_id',
            'category_id' => 'category_id',
            'sub_category_id' => 'sub_category_id',
            'sub_sub_category_id' => 'sub_sub_category_id',
            'product_type' => 'product_type',
            'status' => 'status',
            'featured_status' => 'featured_status',
            'code' => 'code',
            'is_shipping_cost_updated' => 'is_shipping_cost_updated',
            'added_by_user' => 'added_by',
            'user_id' => 'user_id',
        ];

        foreach ($columns as $key => $column) {
            if (! isset($filters[$key]) || $filters[$key] === 'all' || $filters[$key] === '') {
                continue;
            }

            is_array($filters[$key])
                ? $query->whereIn($column, $filters[$key])
                : $query->where($column, $filters[$key]);
        }

        if (isset($filters['id'])) {
            $query->whereIn('id', (array) $filters['id']);
        }

        if (! empty($filters['has_reviews'])) {
            $query->whereHas('reviews');
        }

        if (! empty($filters['has_orders'])) {
            $query->whereHas('orderDetails');
        }

        if (! empty($filters['publishing_house_id'])) {
            $query->whereHas(
                'digitalProductPublishingHouse',
                fn (Builder $builder) => $builder->where('publishing_house_id', $filters['publishing_house_id']),
            );
        }

        if (! empty($filters['author_id'])) {
            $query->whereHas(
                'digitalProductAuthors',
                fn (Builder $builder) => $builder->where('author_id', $filters['author_id']),
            );
        }

        $this->applyCategoryTreeFilter($query, $filters);
    }

    private function applyCategoryTreeFilter(Builder $query, array $filters): void
    {
        $categoryIds = $this->selectedIds($filters, 'filter_category_ids');
        $subCategoryIds = $this->selectedIds($filters, 'filter_sub_category_ids');
        $subSubCategoryIds = $this->selectedIds($filters, 'filter_sub_sub_category_ids');
        $directCategoryIds = $this->selectedIds($filters, 'filter_direct_category_ids');
        $directSubCategoryIds = $this->selectedIds($filters, 'filter_direct_sub_category_ids');

        if ($categoryIds === [] && $subCategoryIds === [] && $subSubCategoryIds === [] && $directCategoryIds === [] && $directSubCategoryIds === []) {
            return;
        }

        $query->where(fn (Builder $inner) => $inner
            ->when($categoryIds !== [], fn (Builder $builder) => $builder->orWhereIn('category_id', $categoryIds))
            ->when($subCategoryIds !== [], fn (Builder $builder) => $builder->orWhereIn('sub_category_id', $subCategoryIds))
            ->when($subSubCategoryIds !== [], fn (Builder $builder) => $builder->orWhereIn('sub_sub_category_id', $subSubCategoryIds))
            ->when($directCategoryIds !== [], fn (Builder $builder) => $builder->orWhere(fn (Builder $direct) => $direct
                ->whereIn('category_id', $directCategoryIds)
                ->where(fn (Builder $unassigned) => $this->whereCategoryColumnIsEmpty($unassigned, 'sub_category_id'))))
            ->when($directSubCategoryIds !== [], fn (Builder $builder) => $builder->orWhere(fn (Builder $direct) => $direct
                ->whereIn('sub_category_id', $directSubCategoryIds)
                ->where(fn (Builder $unassigned) => $this->whereCategoryColumnIsEmpty($unassigned, 'sub_sub_category_id')))));
    }

    private function whereCategoryColumnIsEmpty(Builder $query, string $column): Builder
    {
        return $query->whereNull($column)->orWhereIn($column, ['', '0']);
    }

    private function selectedIds(array $filters, string $key): array
    {
        return is_array($filters[$key] ?? null) ? array_values(array_filter($filters[$key])) : [];
    }

    private function applyStockSort(Builder $query, ?string $sortBy): void
    {
        match ($sortBy) {
            self::SORT_STOCK_ASC => $query->orderBy('current_stock'),
            self::SORT_STOCK_DESC => $query->orderByDesc('current_stock'),
            self::SORT_ORDERS_ASC => $query->orderBy('order_details_count'),
            self::SORT_ORDERS_DESC => $query->orderByDesc('order_details_count'),
            self::SORT_ID_ASC => $query->orderBy('id'),
            default => $query->orderByDesc('id'),
        };
    }

    protected function applySort(Builder $query, ?string $sortBy): void
    {
        match ($sortBy) {
            self::SORT_OLDEST => $query->orderBy('id'),
            self::SORT_BEST_SELLING => $query->withCount([
                'orderDetails as total_qty_sold' => fn (Builder $builder) => $this->constrainToDeliveredModuleSales($builder)
                    ->select(DB::raw('SUM(qty)')),
                'orderDetails as totalSoldAmount' => fn (Builder $builder) => $this->constrainToDeliveredModuleSales($builder)
                    ->select(DB::raw('SUM(price) - SUM(discount)')),
            ])->orderByDesc('total_qty_sold')->orderByDesc('id'),
            self::SORT_MOST_REVIEWED => $this->orderByCustomerReviewCount($query),
            self::SORT_PRICE_ASC => $query->orderBy('unit_price'),
            self::SORT_NAME_ASC => $query->orderBy('name'),
            self::SORT_RANDOM => $query->inRandomOrder(),
            self::SORT_MOST_FAVORITE => $query
                ->withCount(['reviews' => fn ($builder) => $builder->whereNull('delivery_man_id')])
                ->withAvg('rating as ratings_average', 'rating')
                ->orderByDesc($this->listSortColumn('reviews_count'))
                ->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };
    }

    private function constrainToDeliveredModuleSales(Builder $orderDetails): Builder
    {
        return $orderDetails
            ->where('delivery_status', self::DELIVERY_STATUS_DELIVERED)
            ->whereHas('order', fn (Builder $order) => $order->businessModule($this->businessModule()));
    }

    private function orderByCustomerReviewCount(Builder $query): Builder
    {
        if (config('product.sort_source', 'computed') === 'denormalized') {
            return $query->orderByDesc($this->sortColumn('reviews_count'))->orderByDesc('id');
        }

        return $query
            ->withCount(['reviews as '.self::CUSTOMER_REVIEW_COUNT_ALIAS => fn (Builder $reviews) => $this->constrainToActiveCustomerReviews($reviews)])
            ->orderByDesc(self::CUSTOMER_REVIEW_COUNT_ALIAS)
            ->orderByDesc('id');
    }

    protected function constrainToActiveCustomerReviews(Builder $reviews): Builder
    {
        return $reviews
            ->where('reviews.status', self::REVIEW_STATUS_ACTIVE)
            ->whereNull('reviews.delivery_man_id');
    }

    protected function hasColors(array $data): bool
    {
        return filter_var($data['colors_active'] ?? false, FILTER_VALIDATE_BOOLEAN) && ! empty($data['colors']);
    }

    protected function baseLocaleValue(array $data, string $field): mixed
    {
        $index = array_search(self::BASE_LANGUAGE, $data['lang'] ?? [], true);

        return $index === false ? ($data[$field][0] ?? null) : ($data[$field][$index] ?? null);
    }

    protected function getColorsJson(array $data): string
    {
        return json_encode(
            $this->hasColors($data) && ($data['product_type'] ?? null) === self::PRODUCT_TYPE_PHYSICAL ? $data['colors'] : [],
        );
    }

    protected function storedGallery(Model $product): array
    {
        return collect(json_decode($product->images, true) ?: [])
            ->map(static fn ($image): array => is_array($image)
                ? ['image_name' => $image['image_name'], 'storage' => $image['storage'] ?? 'public']
                : ['image_name' => $image, 'storage' => 'public'])
            ->unique('image_name')
            ->values()
            ->all();
    }

    protected function rejectImage(array $images, string $imageName): array
    {
        return array_values(array_filter(
            $images,
            static fn (array $image): bool => $image['image_name'] !== $imageName,
        ));
    }

    protected function resolveStock(array $data, array $variations): int
    {
        if (($data['product_type'] ?? null) !== self::PRODUCT_TYPE_PHYSICAL) {
            return self::DIGITAL_PRODUCT_STOCK;
        }

        return abs($variations !== [] ? $this->getTotalQuantity($variations) : (int) ($data['current_stock'] ?? 0));
    }

    protected function isPhysical(array $data): bool
    {
        return ($data['product_type'] ?? null) === self::PRODUCT_TYPE_PHYSICAL;
    }

    protected function buildCommonAttributes(
        array $data,
        array $images,
        array $variations,
        string $addedBy,
        ?int $userId,
        int|string $shopId,
        array $files,
    ): array {
        $isPhysical = $this->isPhysical($data);
        $thumbnail = $files['image'] ?? null;

        return [
            'business_module' => $this->businessModule(),
            'added_by' => $addedBy,
            'user_id' => $userId,
            'shop_id' => $shopId,
            'name' => $this->baseLocaleValue($data, 'name'),
            'code' => $data['code'],
            'slug' => $this->getSlug($data),
            'category_ids' => json_encode($this->getCategoriesArray($data)),
            'category_id' => $data['category_id'],
            'sub_category_id' => $data['sub_category_id'] ?? null,
            'sub_sub_category_id' => $data['sub_sub_category_id'] ?? null,
            'brand_id' => $isPhysical ? ($data['brand_id'] ?? null) : null,
            'unit' => $isPhysical ? ($data['unit'] ?? null) : null,
            'product_type' => $data['product_type'],
            'details' => $this->baseLocaleValue($data, 'description'),
            'colors' => $this->getColorsJson($data),
            'choice_options' => json_encode($isPhysical ? $this->getChoiceOptions($data) : []),
            'unit_price' => currencyConverter(amount: $data['unit_price'] ?? 0),
            ...$this->buildTaxAttributes($data),
            ...$this->buildDiscountAttributes($data),
            'attributes' => json_encode($isPhysical ? ($data['choice_attributes'] ?? []) : []),
            'current_stock' => $this->resolveStock($data, $variations),
            'minimum_order_qty' => $data['minimum_order_qty'] ?? 1,
            'video_provider' => 'youtube',
            'video_url' => $data['video_url'] ?? null,
            'status' => $addedBy === self::ADDED_BY_ADMIN ? 1 : 0,
            'request_status' => $this->resolveRequestStatus($addedBy),
            'shipping_cost' => $isPhysical ? currencyConverter(amount: $data['shipping_cost'] ?? 0) : 0,
            'multiply_qty' => $isPhysical && ($data['multiply_qty'] ?? null) === 'on' ? 1 : 0,
            'images' => json_encode($images['image_names']),
            'thumbnail' => $thumbnail instanceof UploadedFile
                ? $this->upload(dir: self::THUMBNAIL_DIRECTORY, format: 'webp', image: $thumbnail)
                : ($data['existing_thumbnail'] ?? null),
            'thumbnail_storage_type' => $thumbnail instanceof UploadedFile ? $this->storageDisk() : null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_image' => isset($files['meta_image'])
                ? $this->upload(dir: self::META_DIRECTORY, format: 'webp', image: $files['meta_image'])
                : ($data['existing_meta_image'] ?? null),
        ];
    }

    protected function buildDiscountAttributes(array $data): array
    {
        $discountType = $data['discount_type'] ?? self::DISCOUNT_TYPE_FLAT;
        $discount = $data['discount'] ?? 0;

        return [
            'discount' => $discountType === self::DISCOUNT_TYPE_FLAT ? currencyConverter(amount: $discount) : $discount,
            'discount_type' => $discountType,
        ];
    }

    protected function buildTaxAttributes(array $data, ?Model $product = null): array
    {
        $taxType = $this->postedOrStoredTax($data, $product, 'tax_type', self::TAX_TYPE_PERCENT);
        $tax = $this->postedOrStoredTax($data, $product, 'tax', 0);

        return [
            'tax' => $taxType === self::TAX_TYPE_FLAT ? currencyConverter(amount: $tax) : $tax,
            'tax_type' => $taxType,
            'tax_model' => $this->postedOrStoredTax($data, $product, 'tax_model', self::TAX_MODEL_EXCLUDE),
        ];
    }

    private function postedOrStoredTax(array $data, ?Model $product, string $column, mixed $default): mixed
    {
        foreach ([$data[$column] ?? null, $product?->{$column}] as $value) {
            if (! blank($value)) {
                return $value;
            }
        }

        return $default;
    }

    protected function resolveRequestStatus(string $addedBy): int
    {
        if ($addedBy === self::ADDED_BY_ADMIN) {
            return 1;
        }

        return getWebConfig(name: 'new_product_approval') == 1 ? 0 : 1;
    }

    protected function buildCommonUpdateAttributes(
        array $data,
        array $images,
        array $variations,
        Model $product,
        string $updateBy,
        array $files,
    ): array {
        $isPhysical = $this->isPhysical($data);

        $attributes = [
            'name' => $this->baseLocaleValue($data, 'name'),
            'code' => $data['code'],
            'product_type' => $data['product_type'],
            'category_ids' => json_encode($this->getCategoriesArray($data)),
            'category_id' => $data['category_id'],
            'sub_category_id' => $data['sub_category_id'] ?? null,
            'sub_sub_category_id' => $data['sub_sub_category_id'] ?? null,
            'brand_id' => $isPhysical ? ($data['brand_id'] ?? null) : null,
            'unit' => $isPhysical ? ($data['unit'] ?? null) : null,
            'details' => $this->baseLocaleValue($data, 'description'),
            'colors' => $this->getColorsJson($data),
            'choice_options' => json_encode($isPhysical ? $this->getChoiceOptions($data) : []),
            'unit_price' => currencyConverter(amount: $data['unit_price'] ?? 0),
            ...$this->buildTaxAttributes($data, $product),
            ...$this->buildDiscountAttributes($data),
            'attributes' => json_encode($isPhysical ? ($data['choice_attributes'] ?? []) : []),
            'current_stock' => $this->resolveStock($data, $variations),
            'minimum_order_qty' => $data['minimum_order_qty'] ?? 1,
            'video_provider' => 'youtube',
            'video_url' => $data['video_url'] ?? null,
            'multiply_qty' => $isPhysical && ($data['multiply_qty'] ?? null) === 'on' ? 1 : 0,
            'images' => json_encode($images['image_names']),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_image' => isset($files['meta_image'])
                ? $this->updateFile(dir: self::META_DIRECTORY, oldImage: $product->meta_image, format: 'png', image: $files['meta_image'])
                : $product->meta_image,
        ];

        if (isset($files['image'])) {
            $attributes['thumbnail'] = $this->updateFile(dir: self::THUMBNAIL_DIRECTORY, oldImage: $product->thumbnail, format: 'webp', image: $files['image'], fileType: 'image');
            $attributes['thumbnail_storage_type'] = $this->storageDisk();
        }

        return $attributes + $this->buildShippingAttributes($data, $product, $updateBy) + $this->buildApprovalAttributes($product, $updateBy);
    }

    protected function buildShippingAttributes(array $data, Model $product, string $updateBy): array
    {
        $posted = currencyConverter(amount: $data['shipping_cost'] ?? 0);

        $needsApproval = $updateBy === self::ADDED_BY_SELLER
            && getWebConfig(name: 'product_wise_shipping_cost_approval') == 1
            && $product->shipping_cost != $posted;

        if ($needsApproval) {
            return [
                'temp_shipping_cost' => $posted,
                'is_shipping_cost_updated' => 0,
                'shipping_cost' => $product->shipping_cost,
            ];
        }

        return ['shipping_cost' => $this->isPhysical($data) ? $posted : 0];
    }

    protected function buildApprovalAttributes(Model $product, string $updateBy): array
    {
        if ($updateBy === self::ADDED_BY_SELLER && $product->request_status == 2) {
            return ['request_status' => 0];
        }

        if ($updateBy === self::ADDED_BY_ADMIN && $this->isApprovedOnAdminEdit($product)) {
            return ['request_status' => self::REQUEST_STATUS_APPROVED];
        }

        return [];
    }

    private function deleteStoredFiles(Model $product): void
    {
        foreach (json_decode($product->images, true) ?: [] as $image) {
            $this->deleteFile(filePath: self::IMAGE_DIRECTORY.(is_array($image) ? $image['image_name'] : $image));
        }

        $this->deleteFile(filePath: self::THUMBNAIL_DIRECTORY.$product->thumbnail);
    }

    /** @return array<int, array{type: string, price: mixed, sku: string, qty: mixed}> */
    public function getSkuCombinations(array $data, ?Model $product = null, array $colorNames = []): array
    {
        $combinations = $this->generatePhysicalVariationCombination(
            $data,
            $this->getCombinations($this->getOptions($data)),
            $product,
            $colorNames,
        );

        $regenerated = collect(json_decode($data['generated_combinations'] ?? '[]', true) ?: [])
            ->filter(static fn ($item): bool => isset($item['option']))
            ->keyBy(static fn (array $item): string => strtolower($item['option']));

        return array_map(static function (array $combination) use ($regenerated): array {
            $posted = $regenerated->get(strtolower($combination['type']));

            if (! $posted) {
                return $combination;
            }

            return [
                'type' => $combination['type'],
                'price' => isset($posted['price']) ? round($posted['price']) : $combination['price'],
                'sku' => $posted['sku'] ?? $combination['sku'],
                'qty' => $posted['stock'] ?? $combination['qty'],
            ];
        }, $combinations);
    }

    public function generatePhysicalVariationCombination(
        array $data,
        array $combinations,
        ?Model $product = null,
        array $colorNames = [],
    ): array {
        $stored = collect($this->storedVariations($product))->keyBy('type');
        $skuPrefix = $this->skuPrefix($this->baseLocaleValue($data, 'name'));
        $hasColors = $this->hasColors($data);

        $generated = [];

        foreach (array_filter($combinations) as $combination) {
            $type = '';

            foreach ($combination as $index => $value) {
                if ($index > 0) {
                    $type .= '-'.str_replace(' ', '', $value);

                    continue;
                }

                $type .= $hasColors ? ($colorNames[$value] ?? $value) : str_replace(' ', '', $value);
            }

            if ($stored->has($type)) {
                $variation = $stored->get($type);
                $variation['sku'] = str_replace(' ', '', $variation['sku']);
                $generated[] = $variation;

                continue;
            }

            $generated[] = [
                'type' => $type,
                'price' => currencyConverter(amount: $data['unit_price'] ?? 0),
                'sku' => str_replace(' ', '', $skuPrefix.'-'.$type),
                'qty' => 1,
            ];
        }

        return $generated;
    }

    public function getSeoData(array $data, array $files, ?Model $product = null, ?string $action = null): array
    {
        return [
            'product_id' => $product?->id,
            'title' => $data['meta_title'] ?? $product?->meta_title,
            'description' => $data['meta_description'] ?? $product?->meta_description,
            'index' => ($data['meta_index'] ?? null) === 'index' ? '' : 'noindex',
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
            'image' => $this->resolveMetaImage($files, $product, $action),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function syncSeoData(Model $product, array $data, array $files, string $action): void
    {
        $product->seoMeta()->updateOrCreate(
            ['product_id' => $product->id],
            $this->getSeoData(data: $data, files: $files, product: $product, action: $action),
        );
    }

    private function resolveMetaImage(array $files, ?Model $product, ?string $action): ?string
    {
        if (isset($files['meta_image'])) {
            return $product
                ? $this->updateFile(dir: self::META_DIRECTORY, oldImage: $product->meta_image, format: 'png', image: $files['meta_image'])
                : $this->upload(dir: self::META_DIRECTORY, format: 'webp', image: $files['meta_image']);
        }

        if (isset($files['image']) && $action === 'add') {
            return $this->upload(dir: self::META_DIRECTORY, format: 'webp', image: $files['image']);
        }

        return $product?->seoInfo?->image ?? $product?->meta_image;
    }

    protected function storedVariations(?Model $product): array
    {
        return [];
    }

    protected function skuPrefix(?string $productName): string
    {
        return implode('', array_map(
            static fn (string $word): string => substr($word, 0, 1),
            explode(' ', (string) $productName),
        ));
    }

    public function getBarcodeSheet(int $quantity, ?string $layout = null): array
    {
        $resolvedLayout = array_key_exists((string) $layout, self::BARCODE_LABELS_PER_PAGE)
            ? (string) $layout
            : self::BARCODE_LAYOUT_DEFAULT;

        $labelCount = min(max($quantity, 1), self::BARCODE_MAX_QUANTITY);

        return [
            'layout' => $resolvedLayout,
            'pages' => array_chunk(range(1, $labelCount), self::BARCODE_LABELS_PER_PAGE[$resolvedLayout]),
        ];
    }

    public function createMany(array $products, array $codes): Collection
    {
        return DB::transaction(function () use ($products, $codes): Collection {
            $this->model->newQuery()->insert($products);

            $imported = $this->query()->whereIn('code', $codes)->get();

            cacheRemoveByType(type: 'products');

            return $imported;
        });
    }

    public function updateRestockRequestListAndNotify(object|array $product, object|array $updatedProduct): void
    {
        $productVariation = json_decode($product->retailDetail?->variation ?? '[]', true) ?? [];
        $productUpdateVariation = json_decode($updatedProduct->retailDetail?->variation ?? '[]', true) ?? [];

        if (count($productUpdateVariation) > 0) {
            foreach ($productUpdateVariation as $productUpdateVariant) {
                $restockRequest = RestockProduct::with(['product'])->where(['product_id' => $product['id'], 'variant' => $productUpdateVariant['type']])->first();
                foreach ($productVariation as $variation) {
                    if ($restockRequest && $variation['qty'] == 0 && $productUpdateVariant['qty'] > 0) {
                        RestockProduct::where(['id' => $restockRequest['id']])->delete();
                        RestockProductCustomer::where(['restock_product_id' => $restockRequest['id']])->delete();
                        $this->sendRestockProductNotification(restockRequest: $restockRequest, type: 'restocked');
                    }
                }
            }
        }

        $isVariationChange = false;
        if (count($productUpdateVariation) > 0 || count($productVariation) > 0) {
            $originalTypes = array_column($productVariation, 'type') ?? [];
            $updatedTypes = array_column($productUpdateVariation, 'type') ?? [];

            ksort($originalTypes);
            ksort($updatedTypes);

            if (empty($originalTypes) || empty($updatedTypes)) {
                $isVariationChange = true;
            } else {
                $isVariationChange = array_diff($originalTypes, $updatedTypes) || array_diff($updatedTypes, $originalTypes);
            }
        }

        if (($product['current_stock'] <= 0 && $updatedProduct['current_stock'] > 0 && count($productUpdateVariation) <= 0) || $isVariationChange) {
            $restockRequestList = RestockProduct::with(['product'])->where(['product_id' => $product['id']])->get();
            foreach ($restockRequestList as $restockRequest) {
                RestockProduct::where(['id' => $restockRequest['id']])->delete();
                RestockProductCustomer::where(['restock_product_id' => $restockRequest['id']])->delete();
                $this->sendRestockProductNotification(restockRequest: $restockRequest, type: 'update');
            }
        }
    }

    public function sendRestockProductNotification(object|array $restockRequest, ?string $type = null): void
    {
        $product = $restockRequest?->product;

        event(new RestockProductNotificationEvent(data: [
            'topic' => getRestockProductFCMTopic(restockRequest: $restockRequest),
            'title' => $product?->name,
            'product_id' => $product?->id,
            'slug' => $product?->slug,
            'description' => $type === 'restocked'
                ? translate('This product has restocked')
                : translate('Your requested restock product has been updated'),
            'image' => getStorageImages(path: $product?->thumbnail_full_url ?? '', type: 'product'),
            'route' => route('product', $product?->slug),
            'type' => 'product_restock_update',
            'status' => $type === 'restocked' ? 'product_restocked' : 'product_update',
        ]));
    }

    public function activate(mixed $id, bool $status): bool
    {
        $product = $this->query()->find($id);

        if ($product === null) {
            return false;
        }

        if ($status && $product->added_by === self::ADDED_BY_SELLER && in_array($product->request_status, [0, 2])) {
            return false;
        }

        cacheRemoveByType(type: 'products');

        return (bool) $product->update(['status' => $status]);
    }

    public function deny(mixed $id, ?string $deniedNote = null): ?Model
    {
        $product = $this->findUnscoped($id);

        if ($product === null) {
            return null;
        }

        return DB::transaction(function () use ($product, $deniedNote): Model {
            $product->update([
                'request_status' => self::REQUEST_STATUS_DENIED,
                'status' => 0,
                'denied_note' => $deniedNote,
            ]);

            cacheRemoveByType(type: 'products');

            return $product;
        });
    }

    /** @return array{all: int, new: int, update: int, approved: int, denied: int} */
    public function getRequestStatusCounts(array $filters = []): array
    {
        $query = $this->query();

        $this->applyOwnershipFilters($query, array_diff_key($filters, ['request_status' => null]));

        $counts = $query
            ->selectRaw('
                COUNT(*) AS all_count,
                SUM(CASE WHEN request_status = ? THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN is_shipping_cost_updated = 0 THEN 1 ELSE 0 END) AS shipping_update_count,
                SUM(CASE WHEN request_status = ? THEN 1 ELSE 0 END) AS approved_count,
                SUM(CASE WHEN request_status = ? THEN 1 ELSE 0 END) AS denied_count
            ', [self::REQUEST_STATUS_PENDING, self::REQUEST_STATUS_APPROVED, self::REQUEST_STATUS_DENIED])
            ->first();

        return [
            self::REQUEST_STATE_ALL => (int) $counts->all_count,
            self::REQUEST_STATE_NEW => (int) $counts->pending_count,
            self::REQUEST_STATE_SHIPPING_UPDATE => (int) $counts->shipping_update_count,
            self::REQUEST_STATE_APPROVED => (int) $counts->approved_count,
            self::REQUEST_STATE_DENIED => (int) $counts->denied_count,
        ];
    }

    public function updateRequestStatus(mixed $id, int $requestStatus): ?Model
    {
        $product = $this->find($id);

        if ($product === null) {
            return null;
        }

        $product->update(['request_status' => $requestStatus]);

        cacheRemoveByType(type: 'products');

        return $product;
    }

    public function toggleRequestStatus(mixed $id): ?Model
    {
        $product = $this->findUnscoped($id);

        if ($product === null) {
            return null;
        }

        $product->update([
            'request_status' => $product->request_status == self::REQUEST_STATUS_PENDING
                ? self::REQUEST_STATUS_APPROVED
                : self::REQUEST_STATUS_PENDING,
        ]);

        cacheRemoveByType(type: 'products');

        return $product;
    }

    public function resolveShippingCostUpdate(mixed $id, bool $approved): bool
    {
        $product = $this->query()->find($id);

        if ($product === null) {
            return false;
        }

        $attributes = ['is_shipping_cost_updated' => $approved];

        if ($approved) {
            $attributes['shipping_cost'] = $product->temp_shipping_cost;
        }

        cacheRemoveByType(type: 'products');

        return (bool) $product->update($attributes);
    }

    public function buildStockVariations(array $data): array
    {
        $variations = [];

        foreach ($data['type'] ?? [] as $type) {
            $key = str_replace('.', '_', $type);

            $variations[] = [
                'type' => $type,
                'price' => currencyConverter(amount: abs($data['price_'.$key] ?? 0)),
                'sku' => $data['sku_'.$key] ?? null,
                'qty' => abs($data['qty_'.$key] ?? 0),
            ];
        }

        return $variations;
    }

    public function getActiveList(
        array $filters = [],
        array $excludeIds = [],
        array $relations = [],
        array $withCount = [],
        array $withSum = [],
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()->active()->with($relations)->withCount($this->withReviewCountForSort($withCount, $sortBy));

        foreach ($withSum as $sum) {
            isset($sum['whereColumn'])
                ? $query->withSum(
                    [$sum['relation'].' as '.Str::snake($sum['relation']).'_sum_'.$sum['column'] => fn ($builder) => $builder->where($sum['whereColumn'], $sum['whereValue'])],
                    $sum['column'],
                )
                : $query->withSum($sum['relation'], $sum['column']);
        }

        $this->applyOwnershipFilters($query, $filters);
        $this->applyColumnFilters($query, $filters);

        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        $this->applySort($query, $sortBy);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function findSellerProductBy(int $sellerId, array $filters, array $relations = [], array $withCount = []): ?Model
    {
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->where('added_by', self::ADDED_BY_SELLER)
            ->where('user_id', $sellerId);

        $this->applyFilters($query, $filters);

        return $query->first();
    }

    public function getSellerLowStockProducts(int $sellerId, int|float $stockLimit): Collection
    {
        return $this->query()
            ->where([
                'added_by' => self::ADDED_BY_SELLER,
                'product_type' => self::PRODUCT_TYPE_PHYSICAL,
                'request_status' => self::REQUEST_STATUS_APPROVED,
                'user_id' => $sellerId,
            ])
            ->where('current_stock', '<=', $stockLimit)
            ->get();
    }

    public function getSellerTopSellingList(?int $sellerId, int $dataLimit = DEFAULT_DATA_LIMIT, int $page = 1, string $addedBy = self::ADDED_BY_SELLER, array $relations = []): LengthAwarePaginator
    {
        $deliveredDetails = fn ($detail) => $detail->where('delivery_status', self::DELIVERY_STATUS_DELIVERED);

        $query = $this->query()
            ->with(['rating', 'orderDetails', 'refundRequest', ...$relations])
            ->whereHas('orderDetails', fn (Builder $detail) => $deliveredDetails($detail)
                ->whereDoesntHave('refundStatus', fn (Builder $refund) => $refund->where('status', self::REFUND_STATUS_APPROVED)))
            ->withCount([
                'orderDetails as total_qty_sold' => fn ($detail) => $deliveredDetails($detail->select(DB::raw('SUM(qty)'))),
                'orderDetails as totalSoldAmount' => fn ($detail) => $deliveredDetails($detail->select(DB::raw('SUM(price * qty) - SUM(discount)'))),
                'reviews',
            ]);

        $this->applyOwnershipFilters($query, $this->getAuthorFilters(addedBy: $addedBy, sellerId: $sellerId));

        $products = $query->get()
            ->sortByDesc('total_qty_sold')
            ->values();

        return $this->paginateCollection($products, $dataLimit, $page);
    }

    public function getSellerMostPopularList(?int $sellerId, int $dataLimit = DEFAULT_DATA_LIMIT, int $page = 1, string $addedBy = self::ADDED_BY_SELLER, array $relations = []): LengthAwarePaginator
    {
        $query = $this->query()
            ->with([
                'rating',
                'tags',
                'clearanceSale' => fn ($relation) => $relation->active(),
                'reviews' => fn ($review) => $review->whereHas('product', fn (Builder $product) => $product->active()),
                ...$relations,
            ])
            ->withCount(['reviews' => fn ($review) => $review->whereNull('delivery_man_id')])
            ->withAvg('rating as ratings_average', 'rating')
            ->having('reviews_count', '>', 0)
            ->orderByDesc('reviews_count');

        $this->applyOwnershipFilters($query, $this->getAuthorFilters(addedBy: $addedBy, sellerId: $sellerId));

        return $this->paginateCollection($query->get(), $dataLimit, $page);
    }

    private function getAuthorFilters(string $addedBy, ?int $sellerId): array
    {
        return [
            'added_by' => $addedBy,
            'request_status' => $addedBy === self::ADDED_BY_IN_HOUSE ? null : self::REQUEST_STATUS_APPROVED,
            'seller_id' => $sellerId,
        ];
    }

    public function getPosDiscountAmount(object|array $product, int|float $price): mixed
    {
        return $product['discount_type'] == self::DISCOUNT_TYPE_PERCENT
            ? ($price / 100) * $product['discount']
            : $product['discount'];
    }

    public function hasDigitalProduct(array $productIds): bool
    {
        return $productIds !== [] && $this->query()
            ->whereIn('id', $productIds)
            ->where('product_type', self::PRODUCT_TYPE_DIGITAL)
            ->exists();
    }

    /** @return array<int, int> */
    public function getBrandIds(array $productIds): array
    {
        return $this->query()
            ->whereIn('id', $productIds)
            ->pluck('brand_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function storeImage(UploadedFile $image, string $type): string
    {
        $directory = $type === self::IMAGE_TYPE_PRODUCT ? self::IMAGE_DIRECTORY : self::IMAGE_DIRECTORY.$type.'/';

        return $this->upload(dir: $directory, format: 'webp', image: $image);
    }

    public function applyOwnerFlags(iterable $products, array $wishlistProductIds = [], array $compareProductIds = []): void
    {
        foreach ($products as $product) {
            $product->setAttribute('wish_list_count', in_array($product->id, $wishlistProductIds) ? 1 : 0);
            $product->setAttribute('is_in_compare_list', in_array($product->id, $compareProductIds) ? 1 : 0);
        }
    }

    protected function paginateCollection(Collection $products, int|string $dataLimit, int $page): Collection|LengthAwarePaginator
    {
        if ($dataLimit === 'all') {
            return $products->values();
        }

        return new CollectionPaginator(
            items: $products->forPage($page, (int) $dataLimit)->values(),
            total: $products->count(),
            perPage: (int) $dataLimit,
            currentPage: $page,
            options: ['path' => Paginator::resolveCurrentPath()],
        );
    }

    private function withReviewCountForSort(array $withCount, ?string $sortBy): array
    {
        if ($sortBy !== self::SORT_MOST_REVIEWED || in_array('reviews', $withCount, true) || array_key_exists('reviews', $withCount)) {
            return $withCount;
        }

        return [...$withCount, 'reviews'];
    }

    protected function assistantUnitPrice(Model $product): float
    {
        return (float) ($product->unit_price ?? 0);
    }

    private function assistantListingQuery(): Builder
    {
        return $this->query()
            ->active()
            ->with(['rating', 'tags'])
            ->withCount(['orderDetails', 'reviews'])
            ->withAvg('reviews', 'rating');
    }

    private function assistantCategoryClause(Builder $query, int $categoryId): Builder
    {
        return $query->where(function (Builder $inner) use ($categoryId) {
            $inner->where('category_id', $categoryId)
                ->orWhere('sub_category_id', $categoryId)
                ->orWhere('sub_sub_category_id', $categoryId);
        });
    }

    /** @return array<int, Model> */
    public function getAssistantKeywordMatches(
        string $keyword,
        ?int $categoryId,
        int $limit,
        array $authorProductIds = [],
        array $publishingHouseProductIds = [],
    ): array {
        return $this->assistantListingQuery()
            ->where(function (Builder $q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhereHas('tags', fn (Builder $t) => $t->where('tag', 'like', "%{$keyword}%"));
            })
            ->when($categoryId !== null, fn (Builder $q) => $this->assistantCategoryClause($q, $categoryId))
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->all();
    }

    /** @return array<int, Model> */
    public function getAssistantLocaleMatches(string $keyword, ?int $categoryId, string $locale, int $limit): array
    {
        $productIds = Translation::where('translationable_type', $this->model::class)
            ->where('locale', $locale)
            ->where('key', 'name')
            ->where('value', 'like', "%{$keyword}%")
            ->pluck('translationable_id')
            ->unique()
            ->values()
            ->all();

        if ($productIds === []) {
            $productIds = $this->assistantLocaleTokenMatchIds($keyword, $locale, $limit);
        }

        if ($productIds === []) {
            return [];
        }

        $products = $this->query()
            ->active()
            ->with(['rating'])
            ->whereIn('id', $productIds)
            ->when($categoryId !== null, fn (Builder $q) => $this->assistantCategoryClause($q, $categoryId))
            ->get()
            ->all();

        $rank = array_flip(array_values($productIds));
        usort($products, fn ($a, $b) => ($rank[$a->id] ?? PHP_INT_MAX) <=> ($rank[$b->id] ?? PHP_INT_MAX));

        return array_slice($products, 0, $limit);
    }

    /** @return array<int, int> */
    private function assistantLocaleTokenMatchIds(string $keyword, string $locale, int $limit): array
    {
        $tokens = $this->assistantSignificantTokens($keyword);
        if ($tokens === []) {
            return [];
        }

        $scores = [];
        foreach ($tokens as $token) {
            $ids = Translation::where('translationable_type', $this->model::class)
                ->where('locale', $locale)
                ->where('key', 'name')
                ->where('value', 'like', "%{$token}%")
                ->limit($limit * 4)
                ->pluck('translationable_id');

            foreach ($ids as $id) {
                $scores[$id] = ($scores[$id] ?? 0) + 1;
            }
        }

        arsort($scores);

        return array_keys($scores);
    }

    /** @return array<int, Model> */
    public function getAssistantFilteredList(string $keyword, ?int $categoryId, ?int $brandId, ?int $sellerId, int $limit): array
    {
        return $this->assistantListingQuery()
            ->when($keyword !== '', fn (Builder $q) => $q->where(function (Builder $inner) use ($keyword) {
                $inner->where('name', 'like', "%{$keyword}%")
                    ->orWhereHas('tags', fn (Builder $t) => $t->where('tag', 'like', "%{$keyword}%"));
            }))
            ->when($categoryId !== null, fn (Builder $q) => $this->assistantCategoryClause($q, $categoryId))
            ->when($brandId !== null, fn (Builder $q) => $q->where('brand_id', $brandId))
            ->when($sellerId !== null, fn (Builder $q) => $q->where('added_by', self::ADDED_BY_SELLER)->where('user_id', $sellerId))
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->all();
    }

    /** @return array<int, Model> */
    public function getAssistantPopularList(int $limit): array
    {
        return $this->assistantListingQuery()
            ->orderByDesc('order_details_count')
            ->limit($limit)
            ->get()
            ->all();
    }

    /** @return array<int, Model> */
    public function getAssistantPriceSortedList(string $direction, int $limit): array
    {
        return $this->assistantListingQuery()
            ->orderBy('unit_price', $direction === 'price_high' ? 'desc' : 'asc')
            ->limit($limit)
            ->get()
            ->all();
    }

    /** @return array<int, Model> */
    public function getAssistantCategoryList(int $categoryId, int $limit): array
    {
        $query = $this->assistantListingQuery();
        $this->assistantCategoryClause($query, $categoryId);

        return $query->orderByDesc('id')->limit($limit)->get()->all();
    }

    /** @return array<int, Model> */
    public function getAssistantBrandList(int $brandId, int $limit): array
    {
        return $this->query()
            ->active()
            ->with(['rating'])
            ->where('brand_id', $brandId)
            ->limit($limit)
            ->get()
            ->all();
    }

    /** @return array<int, Model> */
    public function getAssistantSuggestions(
        string $query,
        ?int $categoryId,
        ?int $brandId,
        ?int $sellerId,
        int $limit,
        ?float $maxPriceUsd,
        ?bool $hasVariation,
        ?string $sort,
        array $authorProductIds = [],
        array $publishingHouseProductIds = [],
    ): array {
        $query = $this->normalizeAssistantQuery($query);
        $priceSort = in_array($sort, ['price_high', 'price_low'], true) ? $sort : null;
        $locale = app()->getLocale();

        if ($query === '' && $categoryId === null && $brandId === null && $sellerId === null) {
            $products = $priceSort !== null
                ? $this->getAssistantPriceSortedList(direction: $priceSort, limit: min($limit * 3, 30))
                : $this->getAssistantPopularList(limit: min($limit * 3, 30));

            return $this->finalizeAssistantList($products, $maxPriceUsd, $hasVariation, $priceSort, rerankByPopularity: $priceSort === null, limit: $limit);
        }

        if ($brandId !== null || $sellerId !== null) {
            $products = $this->getAssistantFilteredList(
                keyword: $query,
                categoryId: $categoryId,
                brandId: $brandId,
                sellerId: $sellerId,
                limit: min($limit * 3, 30),
            );

            return $this->finalizeAssistantList($products, $maxPriceUsd, $hasVariation, $priceSort, rerankByPopularity: $priceSort === null, limit: $limit);
        }

        $rerank = $sort === 'popularity';
        $fetchLimit = ($maxPriceUsd !== null || $hasVariation !== null || $rerank || $priceSort !== null) ? min($limit * 3, 30) : $limit;

        $products = [];
        $baseLocale = config('app.fallback_locale', 'en');
        if ($query !== '' && $locale !== $baseLocale) {
            $products = $this->getAssistantLocaleMatches(keyword: $query, categoryId: $categoryId, locale: $locale, limit: $fetchLimit);

            if ($products === [] && $categoryId) {
                $products = $this->getAssistantLocaleMatches(keyword: $query, categoryId: null, locale: $locale, limit: $fetchLimit);
            }
        }

        if ($products === [] && $query !== '') {
            $products = $this->getAssistantKeywordMatches(
                keyword: $query,
                categoryId: $categoryId,
                limit: $fetchLimit,
                authorProductIds: $authorProductIds,
                publishingHouseProductIds: $publishingHouseProductIds,
            );
        }

        if ($products === [] && $query !== '' && $categoryId) {
            $products = $this->getAssistantKeywordMatches(
                keyword: $query,
                categoryId: null,
                limit: $fetchLimit,
                authorProductIds: $authorProductIds,
                publishingHouseProductIds: $publishingHouseProductIds,
            );
        }

        if ($products === [] && $query !== '') {
            $products = $this->assistantTranslatedMatches(keyword: $query, categoryId: $categoryId, limit: $fetchLimit);
        }

        if ($products === [] && $query !== '' && $categoryId) {
            $products = $this->assistantTranslatedMatches(keyword: $query, categoryId: null, limit: $fetchLimit);
        }

        if ($products === [] && $query !== '') {
            $products = $this->assistantTokenizedSearch(keyword: $query, categoryId: $categoryId, limit: $fetchLimit);
        }

        if ($products === [] && $categoryId) {
            $products = $this->getAssistantCategoryList(categoryId: $categoryId, limit: $fetchLimit);
        }

        return $this->finalizeAssistantList($products, $maxPriceUsd, $hasVariation, $priceSort, rerankByPopularity: $rerank, limit: $limit);
    }

    /** @return array<int, Model> */
    private function assistantTranslatedMatches(string $keyword, ?int $categoryId, int $limit): array
    {
        $productIds = $this->translatedIds($keyword);
        if ($productIds === []) {
            return [];
        }

        return $this->query()
            ->active()
            ->with(['rating'])
            ->whereIn('id', $productIds)
            ->when($categoryId !== null, fn (Builder $q) => $this->assistantCategoryClause($q, $categoryId))
            ->limit($limit)
            ->get()
            ->all();
    }

    /** @return array<int, Model> */
    private function assistantTokenizedSearch(string $keyword, ?int $categoryId, int $limit): array
    {
        $tokens = $this->assistantSignificantTokens($keyword);
        if ($tokens === []) {
            return [];
        }

        $scored = [];
        foreach ($tokens as $token) {
            foreach ($this->getAssistantKeywordMatches(keyword: $token, categoryId: $categoryId, limit: $limit * 2) as $product) {
                $id = $product->id;
                if (! isset($scored[$id])) {
                    $scored[$id] = ['product' => $product, 'score' => 0];
                }
                $scored[$id]['score']++;
            }
        }

        if ($scored === [] && $categoryId) {
            return $this->assistantTokenizedSearch(keyword: $keyword, categoryId: null, limit: $limit);
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn ($entry) => $entry['product'], array_slice($scored, 0, $limit));
    }

    /** @return array<int, string> */
    private function assistantSignificantTokens(string $keyword): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', strtolower($keyword), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $kept = array_filter(
            $tokens,
            fn ($token) => mb_strlen($token) >= 2 && ! in_array($token, self::ASSISTANT_STOP_WORDS, true),
        );

        return array_values(array_unique($kept));
    }

    private function normalizeAssistantQuery(string $query): string
    {
        $query = trim($query);
        if ($query === '') {
            return '';
        }

        $tokens = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $kept = array_filter(
            $tokens,
            fn ($token) => ! in_array(strtolower($token), self::ASSISTANT_GENERIC_QUERY_TERMS, true),
        );

        return trim(implode(' ', $kept));
    }

    /** @param array<int, Model> $products @return array<int, Model> */
    protected function finalizeAssistantList(array $products, ?float $maxPriceUsd, ?bool $hasVariation, ?string $priceSort, bool $rerankByPopularity, int $limit): array
    {
        $products = $this->applyAssistantPostFilters($products, $maxPriceUsd, $hasVariation);

        if ($priceSort !== null) {
            $products = $this->sortAssistantByPrice($products, $priceSort);
        } elseif ($rerankByPopularity) {
            $products = $this->sortAssistantByPopularity($products);
        }

        return array_slice($products, 0, $limit);
    }

    /** @param array<int, Model> $products @return array<int, Model> */
    private function applyAssistantPostFilters(array $products, ?float $maxPriceUsd, ?bool $hasVariation): array
    {
        if ($maxPriceUsd !== null) {
            $products = array_values(array_filter($products, function (Model $product) use ($maxPriceUsd) {
                $unitPrice = $this->assistantUnitPrice($product);
                $effective = max(0.0, $unitPrice - Helpers::getProductDiscount(product: $product, price: $unitPrice));

                return $effective <= $maxPriceUsd;
            }));
        }

        if ($hasVariation !== null) {
            $this->loadDigitalVariationsIfNeeded($products);
            $products = array_values(array_filter($products, fn (Model $product) => $this->assistantProductHasVariation($product) === $hasVariation));
        }

        return $products;
    }

    /** @param array<int, Model> $products */
    private function loadDigitalVariationsIfNeeded(array $products): void
    {
        $needsDigital = array_values(array_filter(
            $products,
            fn (Model $product) => ($product->product_type ?? self::PRODUCT_TYPE_PHYSICAL) === self::PRODUCT_TYPE_DIGITAL
                && ! $product->relationLoaded('digitalVariation'),
        ));

        if ($needsDigital !== []) {
            (new Collection($needsDigital))->load('digitalVariation');
        }
    }

    private function assistantProductHasVariation(Model $product): bool
    {
        if (($product->product_type ?? self::PRODUCT_TYPE_PHYSICAL) === self::PRODUCT_TYPE_DIGITAL) {
            return $product->digitalVariation->isNotEmpty();
        }

        $choiceOptions = json_decode((string) ($product->choice_options ?? '[]'), true) ?: [];
        $variation = json_decode((string) ($product->retailDetail?->variation ?? '[]'), true) ?: [];

        return $choiceOptions !== [] || $variation !== [];
    }

    /** @param array<int, Model> $products @return array<int, Model> */
    private function sortAssistantByPrice(array $products, string $direction): array
    {
        usort($products, function (Model $a, Model $b) use ($direction) {
            $priceA = $this->assistantUnitPrice($a);
            $priceB = $this->assistantUnitPrice($b);

            return $direction === 'price_high' ? $priceB <=> $priceA : $priceA <=> $priceB;
        });

        return $products;
    }

    /** @param array<int, Model> $products @return array<int, Model> */
    private function sortAssistantByPopularity(array $products): array
    {
        $scored = [];
        foreach ($products as $index => $product) {
            $scored[] = ['product' => $product, 'index' => $index, 'score' => $this->assistantPopularityScore($product)];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score'] ?: $a['index'] <=> $b['index']);

        return array_map(fn ($entry) => $entry['product'], $scored);
    }

    private function assistantPopularityScore(Model $product): float
    {
        $ratings = collect($product->rating ?? []);
        $avgRating = $product->reviews_avg_rating ?? ($ratings->isNotEmpty() ? (float) $ratings->avg('rating') : 0.0);
        $reviews = $product->reviews_count ?? $ratings->count();
        $orders = $product->order_details_count ?? 0;

        return ((float) $avgRating * 1000) + min((int) $reviews, 999) + (min((int) $orders, 999) / 1000);
    }

    /** @return array<int, Model> */
    public function getAssistantSimilarList(int $productId, int $limit): array
    {
        $product = $this->query()->active()->with(['rating', 'digitalVariation'])->where('id', $productId)->first();

        if ($product === null) {
            return [];
        }

        $collected = [];
        $seen = [$productId => true];

        $push = function (array $candidates) use (&$collected, &$seen): void {
            foreach ($candidates as $candidate) {
                $id = $candidate->id ?? null;
                if ($id === null || isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $collected[] = $candidate;
            }
        };

        foreach ([$product->category_id, $product->sub_category_id, $product->sub_sub_category_id] as $categoryId) {
            if (count($collected) >= $limit) {
                break;
            }
            if ($categoryId) {
                $push($this->getAssistantCategoryList(categoryId: (int) $categoryId, limit: $limit * 3));
            }
        }

        if (count($collected) < $limit && ! empty($product->brand_id)) {
            $push($this->getAssistantBrandList(brandId: (int) $product->brand_id, limit: $limit * 2));
        }

        if (count($collected) < $limit) {
            $push($this->assistantTokenizedSearch(keyword: (string) $product->name, categoryId: null, limit: $limit * 2));
        }

        return array_slice($collected, 0, $limit);
    }
}
