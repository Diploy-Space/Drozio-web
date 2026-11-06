<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Shop;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class ShopService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }

    public const IN_HOUSE_ID = 0;

    public const AUTHOR_TYPE_ADMIN = 'admin';

    public const AUTHOR_TYPE_VENDOR = 'vendor';

    public const AUTHOR_TYPE_SELLER = 'seller';

    public const DEFAULT_IMAGE = 'def.png';

    public const SETUP_GUIDE_APP_STEPS = ['shop_setup', 'add_new_product', 'order_setup', 'withdraw_setup', 'payment_information'];

    public const FILTER_VISIBLE = 'visible';

    public const FILTER_OWNERS = 'owners';

    public const FILTER_SELLER_APPROVED = 'seller_approved';

    public const FILTER_SELLER_VERIFIED = 'seller_verified';

    public const FILTER_HAS_ACTIVE_PRODUCTS_IN_MODULE = 'has_active_products_in_module';

    public const FILTER_PRODUCT_CATEGORY_TREE = 'product_category_tree';

    public const FILTER_INCLUDE_IN_HOUSE = 'include_in_house';

    public const FILTER_WITH_IMAGE = 'with_image';

    private const TOP_VENDOR_PRIORITY_SETTING = 'top_vendor_list_priority';

    private const MINIMUM_RATING_POINTS = ['4', '3.5', '3', '2'];

    private const POSITIVE_RATING = 4;

    private const PRIORITY_HIDE = 'hide';

    private const PRIORITY_DESC = 'desc';

    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const SORT_NAME_ASC = 'a-z';

    public const SORT_NAME_DESC = 'z-a';

    public const SORT_RANDOM = 'random';

    public const SORT_GIVEN = 'given';

    private const ABILITY_TYPE_IN_HOUSE = 'inhouse';

    private const ABILITY_TYPE_VENDOR = 'vendor';

    public const SORT_AUTHOR_TYPE_ASC = 'author-type';

    public const VENDOR_ORDER_NAME_ASC = 'asc';

    public const VENDOR_ORDER_NAME_DESC = 'desc';

    public const VENDOR_ORDER_PRODUCTS_DESC = 'highest-products';

    public const VENDOR_ORDER_PRODUCTS_ASC = 'lowest-products';

    public const VENDOR_ORDER_RATING_DESC = 'rating-high-to-low';

    public const VENDOR_ORDER_RATING_ASC = 'rating-low-to-high';

    private const SELLER_STATUS_APPROVED = 'approved';

    private const MINIMUM_ORDER_AMOUNT_SETTING = 'minimum_order_amount';

    private const IN_HOUSE_VACATION_SETTING = 'vacation_add';

    private const TEMPORARY_CLOSE_SETTING = 'temporary_close';

    private const DOCUMENT_DIRECTORY = 'shop/documents/';

    /** @var array<string, string> */
    private const IMAGE_DIRECTORIES = [
        'image' => 'shop/',
        'banner' => 'shop/banner/',
        'bottom_banner' => 'shop/banner/',
        'offer_banner' => 'shop/banner/',
    ];

    /** @var array<int, string> */
    public const VACATION_DURATION_TYPES = [
        self::VACATION_ONE_DAY,
        self::VACATION_UNTIL_CHANGED,
        self::VACATION_CUSTOM,
    ];

    public const VACATION_ONE_DAY = 'one_day';

    public const VACATION_UNTIL_CHANGED = 'until_change';

    public const VACATION_CUSTOM = 'custom';

    private const VACATION_DISPLAY_FORMAT = 'd M Y h:i A';

    /** @var array<string, string> */
    private const STOREFRONT_SHOP_ROUTES = [
        BusinessModule::RETAIL => 'vendor-shop',
        BusinessModule::WHOLESALE => 'wholesale.shop',
        BusinessModule::AUCTION => 'auction.shop',
    ];

    public const TEMPORARY_CLOSE_COLUMNS = [
        BusinessModule::RETAIL => 'temporary_close',
        BusinessModule::WHOLESALE => 'wholesale_temporary_close',
        BusinessModule::AUCTION => 'auction_temporary_close',
    ];

    private const INDEXABLE_CHUNK_SIZE = 1000;

    public function __construct(Shop $shop)
    {
        parent::__construct($shop);
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
        array $sortIds = [],
    ): Collection|LengthAwarePaginator {
        $query = $this->listQuery(
            searchValue: $searchValue,
            relations: $relations,
            withCount: $withCount,
            filters: $filters,
            sortBy: $sortBy,
            sortIds: $sortIds,
        );

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $this->columnFilters($filters)));
    }

    public function getCursorList(
        ?string $searchValue = null,
        int $dataLimit = DEFAULT_DATA_LIMIT,
        ?string $cursor = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): CursorPaginator {
        return $this->listQuery(
            searchValue: $searchValue,
            relations: $relations,
            withCount: $withCount,
            filters: $filters,
            sortBy: $sortBy,
        )
            ->cursorPaginate(perPage: $dataLimit, cursor: $cursor)
            ->appends($this->listQueryString($searchValue, $sortBy, $this->columnFilters($filters)));
    }

    private function listQuery(
        ?string $searchValue,
        array $relations,
        array $withCount,
        array $filters,
        ?string $sortBy,
        array $sortIds = [],
    ): Builder {
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where('name', 'like', "%{$searchValue}%"));

        if (! empty($filters[self::FILTER_VISIBLE])) {
            $this->applyVisibility($query);
        }

        if (! empty($filters[self::FILTER_OWNERS])) {
            $this->applyOwners($query, $filters[self::FILTER_OWNERS]);
        }

        if (! empty($filters[self::FILTER_WITH_IMAGE])) {
            $query->whereNotNull('image')->whereNotIn('image', ['', self::DEFAULT_IMAGE]);
        }

        if (! empty($filters[self::FILTER_INCLUDE_IN_HOUSE])) {
            $query->where(fn (Builder $outer) => $outer
                ->where(fn (Builder $vendor) => $this->applyVendorFilters(
                    $vendor->where('author_type', '!=', self::AUTHOR_TYPE_ADMIN),
                    $filters,
                ))
                ->orWhere(fn (Builder $inHouse) => $this->applyInHouseFilters($inHouse, $filters)));
        } else {
            $this->applyVendorFilters($query, $filters);
        }

        match ($sortBy) {
            self::SORT_GIVEN => $this->applyGivenOrder($query, $sortIds),
            self::SORT_OLDEST => $query->orderBy('id'),
            self::SORT_NAME_ASC => $query->orderBy('name'),
            self::SORT_NAME_DESC => $query->orderByDesc('name'),
            self::SORT_RANDOM => $query->inRandomOrder(),
            self::SORT_AUTHOR_TYPE_ASC => $query->orderBy('author_type')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };

        $this->applyFilters($query, $this->columnFilters($filters));

        return $query;
    }

    private function columnFilters(array $filters): array
    {
        unset(
            $filters[self::FILTER_VISIBLE],
            $filters[self::FILTER_OWNERS],
            $filters[self::FILTER_SELLER_APPROVED],
            $filters[self::FILTER_SELLER_VERIFIED],
            $filters[self::FILTER_HAS_ACTIVE_PRODUCTS_IN_MODULE],
            $filters[self::FILTER_PRODUCT_CATEGORY_TREE],
            $filters[self::FILTER_INCLUDE_IN_HOUSE],
            $filters[self::FILTER_WITH_IMAGE],
        );

        return $filters;
    }

    public function getVendorListing(?string $shopName = null): Collection
    {
        return $this->query()
            ->active()
            ->applyNameFilter($shopName)
            ->withCount(['products' => fn (Builder $builder) => $builder->active()->businessModule(BusinessModule::RETAIL)])
            ->with([
                'products' => fn ($relation) => $relation->active()->businessModule(BusinessModule::RETAIL)->with('reviews'),
                'seller' => fn ($relation) => $relation->withCount([
                    'orders' => fn (Builder $builder) => $builder->businessModule(BusinessModule::RETAIL),
                ]),
            ])
            ->get()
            ->each(fn (Model $shop) => $this->attachVendorStats($shop));
    }

    public function prependInHouseShop(Collection $vendors, ?Model $inHouseShop): Collection
    {
        if ($inHouseShop === null) {
            return $vendors;
        }

        return $vendors
            ->reject(fn (Model $shop): bool => $shop->seller_id === $inHouseShop->seller_id
                && $shop->author_type === $inHouseShop->author_type)
            ->prepend($inHouseShop);
    }

    public function applyVendorOrdering(Collection $vendors, ?string $orderBy): Collection
    {
        return match ($orderBy) {
            self::VENDOR_ORDER_NAME_ASC => $vendors->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            self::VENDOR_ORDER_NAME_DESC => $vendors->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE),
            self::VENDOR_ORDER_PRODUCTS_DESC => $vendors->sortByDesc('products_count'),
            self::VENDOR_ORDER_PRODUCTS_ASC => $vendors->sortBy('products_count'),
            self::VENDOR_ORDER_RATING_DESC => $vendors->sortByDesc('average_rating'),
            self::VENDOR_ORDER_RATING_ASC => $vendors->sortBy('average_rating'),
            default => $vendors,
        };
    }

    private function attachVendorStats(Model $shop): void
    {
        $reviews = $shop->products->flatMap->reviews;
        $reviewCount = $reviews->count();

        $shop->orders_count = $shop->seller->orders_count ?? 0;
        $shop->average_rating = $reviews->avg('rating') ?? 0;
        $shop->review_count = $reviewCount;
        $shop->total_rating = $reviews->sum('rating');
        $shop->positive_review = $reviewCount === 0
            ? 0
            : ($reviews->where('rating', '>=', 4)->count() * 100) / $reviewCount;
        $shop->is_vacation_mode_now = $this->isOnVacationNow(shop: $shop);
    }

    /** @return array<string, mixed> */
    public function getShopInfo(Model $shop, mixed $averageRating, int $totalReview, int $totalOrder): array
    {
        $isInHouse = $shop['author_type'] === self::AUTHOR_TYPE_ADMIN;
        $abilityType = $isInHouse ? self::ABILITY_TYPE_IN_HOUSE : self::ABILITY_TYPE_VENDOR;

        return [
            'id' => $shop['id'],
            'is_in_house' => $isInHouse,
            'name' => $shop['name'],
            'slug' => $shop['slug'],
            'author_type' => $shop['author_type'],
            'seller_id' => $isInHouse ? 0 : $shop['seller_id'],
            'average_rating' => $averageRating,
            'total_review' => $totalReview,
            'total_order' => $totalOrder,
            'current_date' => date('Y-m-d'),
            'vacation_start_date' => date('Y-m-d', strtotime($shop->vacation_start_date)),
            'vacation_end_date' => date('Y-m-d', strtotime($shop->vacation_end_date)),
            'temporary_close' => checkVendorAbility(type: $abilityType, status: 'temporary_close', vendor: $shop),
            'vacation_status' => checkVendorAbility(type: $abilityType, status: 'vacation_status', vendor: $shop),
            'banner_full_url' => $shop->banner_full_url,
            'bottom_banner' => $shop->bottom_banner,
            'bottom_banner_full_url' => $shop->bottom_banner_full_url,
            'image_full_url' => $shop->image_full_url,
            'is_verified' => $isInHouse ? isInHouseShopVerified() : (bool) $shop->seller?->hasVerifiedBadge(),
            'minimum_order_amount' => $isInHouse
                ? getWebConfig(name: self::MINIMUM_ORDER_AMOUNT_SETTING)
                : $shop->seller->minimum_order_amount,
        ];
    }

    /** @return array{temporary_close: mixed, vacation_status: mixed, vacation_start_date: ?string, vacation_end_date: ?string} */
    public function getVacationSnapshot(Model $shop): array
    {
        return [
            'temporary_close' => $shop->temporary_close,
            'vacation_status' => $shop->vacation_status,
            'vacation_start_date' => $shop->vacation_start_date ? date('Y-m-d', strtotime($shop->vacation_start_date)) : null,
            'vacation_end_date' => $shop->vacation_end_date ? date('Y-m-d', strtotime($shop->vacation_end_date)) : null,
        ];
    }

    /** @return array{temporary_close: mixed, vacation_status: mixed, vacation_start_date: mixed, vacation_end_date: mixed} */
    public function getInHouseVacationSnapshot(): array
    {
        $vacation = getWebConfig(name: self::IN_HOUSE_VACATION_SETTING);

        return [
            'temporary_close' => getWebConfig(name: self::TEMPORARY_CLOSE_SETTING)['status'],
            'vacation_status' => $vacation['status'],
            'vacation_start_date' => $vacation['vacation_start_date'],
            'vacation_end_date' => $vacation['vacation_end_date'],
        ];
    }

    public function isClosedOn(array $snapshot, string $date): bool
    {
        return (bool) $snapshot['temporary_close'] || (
            $snapshot['vacation_status']
            && $date >= $snapshot['vacation_start_date']
            && $date <= $snapshot['vacation_end_date']
        );
    }

    public function attachRatings(Collection $shops): void
    {
        $shops->each(function (Model $shop): void {
            $shopReviews = $shop->products->flatMap->reviews;
            $reviewCount = $shopReviews->count();

            $shop->review_count = $reviewCount;
            $shop->total_rating = $shopReviews->sum('rating');
            $shop->average_rating = $shopReviews->avg('rating');
            $shop->positive_review = $reviewCount === 0
                ? 0
                : ($shopReviews->where('rating', '>=', self::POSITIVE_RATING)->count() * 100) / $reviewCount;
        });
    }

    /** @param  array<int, array{rating_count: int, total_rating: int, average_rating: ?float, positive_percentage: float|int}>  $ratingsBySeller */
    public function attachSellerRatings(Collection $shops, array $ratingsBySeller): void
    {
        $shops->each(function (Model $shop) use ($ratingsBySeller): void {
            $rating = $ratingsBySeller[(int) $shop->seller_id] ?? null;

            $shop->review_count = $rating['rating_count'] ?? 0;
            $shop->total_rating = $rating['total_rating'] ?? 0;
            $shop->average_rating = $rating['average_rating'] ?? null;
            $shop->positive_review = $rating['positive_percentage'] ?? 0;
        });
    }

    public function applyTopVendorPriority(Collection $shops, string $businessModule = BusinessModule::RETAIL): Collection
    {
        $sellers = Collection::make($shops->pluck('seller')->filter()->all());

        if ($sellers->isNotEmpty()) {
            $sellers->loadCount(['orders' => fn (Builder $builder) => $builder->businessModule($businessModule)]);
        }

        $shops->each(function (Model $shop) use ($businessModule): void {
            $shop->orders_count = $shop->seller?->orders_count ?? 0;
            $shop->coupon_list = $shop->seller?->coupon;
            $shop->is_vacation_mode_now = $this->isOnVacationNow(shop: $shop, businessModule: $businessModule);
        });

        $priority = getWebConfig(name: self::TOP_VENDOR_PRIORITY_SETTING);

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            return $shops;
        }

        $sorted = $shops
            ->when(
                in_array($priority['minimum_rating_point'] ?? null, self::MINIMUM_RATING_POINTS, true),
                fn (Collection $list) => $list->filter(
                    fn (Model $shop) => (float) $shop->average_rating >= (float) $priority['minimum_rating_point'],
                ),
            )
            ->when(
                ($priority['vacation_mode_sorting'] ?? null) === self::PRIORITY_HIDE,
                fn (Collection $list) => $list->filter(fn (Model $shop) => $shop->is_vacation_mode_now != 1),
            )
            ->when(
                ($priority['temporary_close_sorting'] ?? null) === self::PRIORITY_HIDE,
                fn (Collection $list) => $list->filter(fn (Model $shop) => $shop->temporary_close != 1),
            );

        $sorted = match ($priority['sort_by'] ?? null) {
            'order' => $sorted->sortByDesc('orders_count'),
            'reviews_count' => $sorted->sortByDesc('review_count'),
            'rating' => $sorted->sortByDesc('average_rating'),
            'rating_and_review' => $sorted->sortByDesc('review_count')->sortByDesc('average_rating'),
            default => $sorted,
        };

        if (($priority['vacation_mode_sorting'] ?? null) === self::PRIORITY_DESC) {
            $sorted = $sorted->sortBy('is_vacation_mode_now');
        }

        if (($priority['temporary_close_sorting'] ?? null) === self::PRIORITY_DESC) {
            $sorted = $sorted->sortBy('temporary_close');
        }

        return $sorted;
    }

    public function getIndexableSlugs(): LazyCollection
    {
        return $this->query()
            ->select(['id', 'slug', 'updated_at'])
            ->whereHas('seller', fn (Builder $seller) => $seller->where('status', self::SELLER_STATUS_APPROVED))
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->lazyById(self::INDEXABLE_CHUNK_SIZE);
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function getSellerIdByShopName(string $name): ?int
    {
        $sellerId = $this->query()->where('name', 'like', "%{$name}%")->value('seller_id');

        return $sellerId ? (int) $sellerId : null;
    }

    public function getApprovedVendorsWithProductCounts(int $limit, ?string $nameLike, string $businessModule): Collection
    {
        return $this->query()
            ->whereHas('seller', fn (Builder $q) => $q->where('status', self::SELLER_STATUS_APPROVED))
            ->when($nameLike, fn (Builder $q) => $q->where('name', 'like', "%{$nameLike}%"))
            ->withCount(['products' => fn (Builder $q) => $q->businessModule($businessModule)])
            ->orderByDesc('products_count')
            ->limit($limit)
            ->get();
    }

    /** @return array<string, int> */
    public function getSetupGuideAppSteps(Model $shop): array
    {
        $storedSteps = is_array($shop->setup_guide_app) ? $shop->setup_guide_app : [];

        return array_map(
            static fn (string $step): int => (int) ($storedSteps[$step] ?? 0),
            array_combine(self::SETUP_GUIDE_APP_STEPS, self::SETUP_GUIDE_APP_STEPS),
        );
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $shop = $this->findBy(params: $params, relations: []);

        if ($shop === null) {
            return null;
        }

        $shop->update($data);

        return $shop->refresh();
    }

    public function isOnVacationNow(Model $shop, string $businessModule = BusinessModule::RETAIL): bool
    {
        return (bool) checkVendorAbility(
            type: $this->abilityType(shop: $shop),
            status: 'vacation_status',
            vendor: $shop,
            businessModule: $businessModule,
        );
    }

    public function isTemporarilyClosed(Model $shop, string $businessModule = BusinessModule::RETAIL): bool
    {
        return (bool) checkVendorAbility(
            type: $this->abilityType(shop: $shop),
            status: 'temporary_close',
            vendor: $shop,
            businessModule: $businessModule,
        );
    }

    /** @return array{temporary_close: bool, vacation_status: bool, is_vacation_mode_now: bool, vacation_duration_type: ?string, vacation_start_date: mixed, vacation_end_date: mixed} */
    public function getAvailability(Model $shop, string $businessModule = BusinessModule::RETAIL): array
    {
        return [
            'temporary_close' => $this->isTemporarilyClosed(shop: $shop, businessModule: $businessModule),
            'vacation_status' => (bool) $shop->vacation_status,
            'is_vacation_mode_now' => $this->isOnVacationNow(shop: $shop, businessModule: $businessModule),
            'vacation_duration_type' => $shop->vacation_duration_type,
            'vacation_start_date' => $shop->vacation_start_date,
            'vacation_end_date' => $shop->vacation_end_date,
        ];
    }

    public function isClosedForAuthor(?string $authorType, ?Model $shop, string $businessModule = BusinessModule::RETAIL): bool
    {
        $abilityType = $authorType === self::AUTHOR_TYPE_SELLER ? self::ABILITY_TYPE_VENDOR : self::ABILITY_TYPE_IN_HOUSE;
        $vendor = $abilityType === self::ABILITY_TYPE_VENDOR ? $shop : null;

        return (bool) checkVendorAbility(type: $abilityType, status: 'temporary_close', vendor: $vendor, businessModule: $businessModule)
            || (bool) checkVendorAbility(type: $abilityType, status: 'vacation_status', vendor: $vendor, businessModule: $businessModule);
    }

    public function create(array $data, array $images = [], ?UploadedFile $tinCertificate = null): ?Model
    {
        $storage = $this->storageDisk();
        $certificate = $images['tin_certificate'] ?? null;

        return $this->query()->create([
            'seller_id' => $data['seller_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name'], '-').'-'.Str::random(6),
            'address' => $data['address'] ?? '',
            'contact' => $data['contact'] ?? null,
            'image' => $data['image'] ?? self::DEFAULT_IMAGE,
            'tax_identification_number' => $data['tax_identification_number'] ?? null,
            'tin_expire_date' => empty($data['tin_expire_date']) ? null : Carbon::parse($data['tin_expire_date']),
            'tin_certificate' => $certificate instanceof UploadedFile
                ? $this->fileUpload(dir: self::DOCUMENT_DIRECTORY, format: $certificate->getClientOriginalExtension(), file: $certificate)
                : null,
            'tin_certificate_storage_type' => $certificate instanceof UploadedFile ? $storage : null,
            ...$this->uploadedImageData(shop: null, images: $images),
            ...$this->uploadedDocumentData(tinCertificate: $tinCertificate),
        ]);
    }

    /** @param  array<string, UploadedFile|null>  $images */
    public function update(mixed $id, array $data, array $images = [], ?UploadedFile $tinCertificate = null): ?Model
    {
        $shop = $this->query()->find($id);

        if ($shop === null) {
            return null;
        }

        $columns = array_filter([
            'name' => $data['name'] ?? null,
            'address' => $data['address'] ?? null,
            'contact' => $data['company_phone'] ?? null,
        ], static fn ($value) => $value !== null);

        if (array_key_exists('tax_identification_number', $data)) {
            $columns['tax_identification_number'] = $data['tax_identification_number'];
        }

        if (array_key_exists('tin_expire_date', $data)) {
            $columns['tin_expire_date'] = empty($data['tin_expire_date']) ? null : Carbon::parse($data['tin_expire_date']);
        }

        $shop->update([
            ...$columns,
            ...$this->uploadedImageData(shop: $shop, images: $images),
            ...$this->uploadedDocumentData(tinCertificate: $tinCertificate),
            ...$this->removedDocumentData(shop: $shop, isRemoved: $tinCertificate === null && ! empty($data['remove_certificate'])),
        ]);

        return $shop->refresh();
    }

    public function delete(mixed $id): bool
    {
        $shop = $this->query()->find($id);

        if ($shop === null) {
            return false;
        }

        return (bool) $shop->delete();
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $isVisible = ! empty($filters[self::FILTER_VISIBLE]);
        unset($filters[self::FILTER_VISIBLE]);

        $query = $this->query();

        if ($isVisible) {
            $this->applyVisibility($query);
        }

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function getProductShopId(int $shopId): int
    {
        return $this->exists(params: ['id' => $shopId, 'author_type' => self::AUTHOR_TYPE_ADMIN])
            ? self::IN_HOUSE_ID
            : $shopId;
    }

    public function getVendorOptions(): Collection
    {
        $shops = $this->query()
            ->active()
            ->where('author_type', self::AUTHOR_TYPE_VENDOR)
            ->get(['id', 'name', 'slug']);

        return $shops->prepend(getInHouseShopConfig());
    }

    /**
     * @param  array<string, string>  $moduleLabels
     * @param  array<string, bool>  $availability
     * @return array<int, array{key: string, label: string, description_on: string, description_off: string, is_open: bool}>
     */
    public function getAvailabilityModules(array $moduleLabels, array $availability): array
    {
        $rows = [];
        foreach ($moduleLabels as $moduleKey => $label) {
            if (! array_key_exists($moduleKey, $availability)) {
                continue;
            }

            $label = translate($label);
            $rows[] = [
                'key' => $moduleKey,
                'label' => $label,
                'description_on' => $this->getAvailabilityDescription(moduleKey: $moduleKey, label: $label, isOpen: true),
                'description_off' => $this->getAvailabilityDescription(moduleKey: $moduleKey, label: $label, isOpen: false),
                'is_open' => $availability[$moduleKey],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $moduleLabels
     * @param  string[]|null  $selectedModuleKeys
     * @return array<int, array{key: string, label: string, is_selected: bool}>
     */
    public function getVacationModules(array $moduleLabels, ?array $selectedModuleKeys): array
    {
        $rows = [];
        foreach ($moduleLabels as $moduleKey => $label) {
            $rows[] = [
                'key' => $moduleKey,
                'label' => translate($label),
                'is_selected' => $selectedModuleKeys === null || in_array($moduleKey, $selectedModuleKeys, true),
            ];
        }

        return $rows;
    }

    /** @return array{vacation_start_date: ?string, vacation_end_date: ?string} */
    public function getVacationPeriod(?string $durationType, bool $isTurningOn, ?string $startDate, ?string $endDate): array
    {
        if ($durationType === self::VACATION_UNTIL_CHANGED) {
            return ['vacation_start_date' => null, 'vacation_end_date' => null];
        }

        if ($durationType === self::VACATION_ONE_DAY && $isTurningOn) {
            $startsAt = now();

            return [
                'vacation_start_date' => $startsAt->toDateTimeString(),
                'vacation_end_date' => $startsAt->copy()->addDay()->toDateTimeString(),
            ];
        }

        return ['vacation_start_date' => $startDate, 'vacation_end_date' => $endDate];
    }

    /**
     * @param  array<int, array{key: string, label: string, is_selected: bool}>  $vacationModules
     * @return array{businesses: string, covers_all_businesses: bool, start_date: ?string, end_date: ?string, is_open_ended: bool, is_upcoming: bool}|null
     */
    public function getVacationSchedule(
        bool $isOnVacation,
        ?string $durationType,
        ?string $startDate,
        ?string $endDate,
        array $vacationModules,
    ): ?array {
        $selectedModules = array_filter($vacationModules, fn (array $module) => $module['is_selected']);
        $businesses = $this->joinBusinessLabels(array_column($selectedModules, 'label'));

        if (! $isOnVacation || $businesses === null) {
            return null;
        }

        $coversAllBusinesses = count($selectedModules) === count($vacationModules);

        if ($durationType === self::VACATION_UNTIL_CHANGED) {
            return [
                'businesses' => $businesses,
                'covers_all_businesses' => $coversAllBusinesses,
                'start_date' => null,
                'end_date' => null,
                'is_open_ended' => true,
                'is_upcoming' => false,
            ];
        }

        if (empty($startDate) || empty($endDate) || now()->greaterThan(Carbon::parse($endDate))) {
            return null;
        }

        return [
            'businesses' => $businesses,
            'covers_all_businesses' => $coversAllBusinesses,
            'start_date' => Carbon::parse($startDate)->format(self::VACATION_DISPLAY_FORMAT),
            'end_date' => Carbon::parse($endDate)->format(self::VACATION_DISPLAY_FORMAT),
            'is_open_ended' => false,
            'is_upcoming' => now()->lessThan(Carbon::parse($startDate)),
        ];
    }

    /** @return array<int, array{label: string, url: string}> */
    public function getStorefrontLinks(?string $slug): array
    {
        if ($slug === null || $slug === '') {
            return [];
        }

        $links = [];

        foreach (self::STOREFRONT_SHOP_ROUTES as $moduleKey => $routeName) {
            if (! businessModuleManager()->isAvailable($moduleKey) || ! Route::has($routeName)) {
                continue;
            }

            $links[] = [
                'label' => translate(businessModuleManager()->get($moduleKey)?->label ?? ucfirst($moduleKey)),
                'url' => route($routeName, ['slug' => $slug]),
            ];
        }

        return $links;
    }

    /** @param  array<int, array{key: string, label: string, is_open: bool}>  $availabilityModules */
    public function getClosedBusinessesLabel(array $availabilityModules): ?string
    {
        return $this->joinBusinessLabels(array_column(
            array_filter($availabilityModules, fn (array $module) => ! $module['is_open']),
            'label',
        ));
    }

    /** @param  string[]  $labels */
    private function joinBusinessLabels(array $labels): ?string
    {
        if ($labels === []) {
            return null;
        }

        $lastLabel = array_pop($labels);

        return $labels ? implode(', ', $labels).' & '.$lastLabel : $lastLabel;
    }

    private function getAvailabilityDescription(string $moduleKey, string $label, bool $isOpen): string
    {
        return match ([$moduleKey, $isOpen]) {
            [BusinessModule::RETAIL, true] => translate('Disable your retail store to temporarily stop new retail orders.').' '.translate('Existing orders will remain unaffected.'),
            [BusinessModule::RETAIL, false] => translate('Enable your retail store to let customers browse products and place individual orders through the app and website.'),
            [BusinessModule::WHOLESALE, true] => translate('Disable wholesale to temporarily stop new bulk orders.').' '.translate('Existing wholesale orders will remain unaffected.'),
            [BusinessModule::WHOLESALE, false] => translate('Enable wholesale to accept bulk purchase orders and make your wholesale products visible to business buyers.'),
            [BusinessModule::AUCTION, true] => translate('Disable auctions to hide your auction listings and prevent new bids.'),
            [BusinessModule::AUCTION, false] => translate('Enable auctions to let customers discover, bid on, and participate in your live auction listings.'),
            default => $isOpen
                ? translate('Disable :business to temporarily stop new orders.', ['business' => $label])
                : translate('Enable :business to start accepting new orders.', ['business' => $label]),
        };
    }

    /**
     * @param  array<string, UploadedFile|null>  $images
     * @return array<string, string>
     */
    private function uploadedImageData(?Model $shop, array $images): array
    {
        $storage = $this->storageDisk();

        $data = [];
        foreach (self::IMAGE_DIRECTORIES as $column => $directory) {
            if (! ($images[$column] ?? null) instanceof UploadedFile) {
                continue;
            }

            $data[$column] = $shop === null
                ? $this->upload(dir: $directory, format: 'webp', image: $images[$column])
                : $this->updateFile(dir: $directory, oldImage: $shop[$column], format: 'webp', image: $images[$column]);
            $data[$column.'_storage_type'] = $storage;
        }

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);

        return ($slug === '' ? Str::random(12) : $slug).'-'.Str::random(6);
    }

    /** @return array<string, null> */
    private function removedDocumentData(Model $shop, bool $isRemoved): array
    {
        if (! $isRemoved || empty($shop->tin_certificate)) {
            return [];
        }

        $this->deleteFile(filePath: self::DOCUMENT_DIRECTORY.$shop->tin_certificate);

        return [
            'tin_certificate' => null,
            'tin_certificate_storage_type' => null,
        ];
    }

    /** @return array<string, string> */
    private function uploadedDocumentData(?UploadedFile $tinCertificate): array
    {
        if ($tinCertificate === null) {
            return [];
        }

        return [
            'tin_certificate' => $this->fileUpload(
                dir: self::DOCUMENT_DIRECTORY,
                format: $tinCertificate->getClientOriginalExtension(),
                file: $tinCertificate,
            ),
            'tin_certificate_storage_type' => $this->storageDisk(),
        ];
    }

    private function applyVendorFilters(Builder $query, array $filters): void
    {
        if (! empty($filters[self::FILTER_SELLER_APPROVED])) {
            $query->whereHas('seller', fn (Builder $seller) => $seller->where('status', self::SELLER_STATUS_APPROVED));
        }

        if (! empty($filters[self::FILTER_SELLER_VERIFIED])) {
            $query->whereHas('seller', fn (Builder $seller) => $seller->verified());
        }

        $this->applyProductFilters($query, $filters, relation: 'products');
    }

    private function applyInHouseFilters(Builder $query, array $filters): void
    {
        $query->where('author_type', self::AUTHOR_TYPE_ADMIN);

        $this->applyProductFilters($query, $filters, relation: 'inHouseProducts');
    }

    private function applyProductFilters(Builder $query, array $filters, string $relation): void
    {
        if (empty($filters[self::FILTER_HAS_ACTIVE_PRODUCTS_IN_MODULE]) && empty($filters[self::FILTER_PRODUCT_CATEGORY_TREE])) {
            return;
        }

        $businessModule = $filters[self::FILTER_HAS_ACTIVE_PRODUCTS_IN_MODULE] ?? null;
        $categoryTree = $filters[self::FILTER_PRODUCT_CATEGORY_TREE] ?? [];

        $query->whereHas($relation, function (Builder $product) use ($businessModule, $categoryTree): void {
            $product->active();

            if ($businessModule !== null) {
                $product->businessModule($businessModule);
            }

            $this->applyProductCategoryTree($product, $categoryTree);
        });
    }

    /** @param array{category_ids?: array<int, int>, sub_category_ids?: array<int, int>, sub_sub_category_ids?: array<int, int>} $categoryTree */
    private function applyProductCategoryTree(Builder $query, array $categoryTree): void
    {
        $categoryIds = $categoryTree['category_ids'] ?? [];
        $subCategoryIds = $categoryTree['sub_category_ids'] ?? [];
        $subSubCategoryIds = $categoryTree['sub_sub_category_ids'] ?? [];

        if ($categoryIds === [] && $subCategoryIds === [] && $subSubCategoryIds === []) {
            return;
        }

        $query->where(fn (Builder $inner) => $inner
            ->when($categoryIds !== [], fn (Builder $builder) => $builder->orWhereIn('category_id', $categoryIds))
            ->when($subCategoryIds !== [], fn (Builder $builder) => $builder->orWhereIn('sub_category_id', $subCategoryIds))
            ->when($subSubCategoryIds !== [], fn (Builder $builder) => $builder->orWhereIn('sub_sub_category_id', $subSubCategoryIds)));
    }

    private function applyVisibility(Builder $query): void
    {
        $query->where(fn (Builder $outer) => $outer
            ->where('author_type', self::AUTHOR_TYPE_ADMIN)
            ->orWhereHas('seller', fn (Builder $seller) => $seller->where('status', self::SELLER_STATUS_APPROVED)));
    }

    private function abilityType(Model $shop): string
    {
        return $shop->author_type === self::AUTHOR_TYPE_ADMIN
            ? self::ABILITY_TYPE_IN_HOUSE
            : self::ABILITY_TYPE_VENDOR;
    }

    /** @param  array<int, int>  $sortIds */
    private function applyGivenOrder(Builder $query, array $sortIds): void
    {
        if ($sortIds === []) {
            $query->orderByDesc('id');

            return;
        }

        $query->orderByRaw('FIELD(shops.id, '.implode(',', array_map('intval', $sortIds)).')');
    }

    /** @param  array{in_house: bool, seller_ids: array<int, int>}  $owners */
    private function applyOwners(Builder $query, array $owners): void
    {
        $query->where(fn (Builder $outer) => $outer
            ->when($owners['in_house'] ?? false, fn (Builder $inner) => $inner->orWhere('author_type', self::AUTHOR_TYPE_ADMIN))
            ->when($owners['seller_ids'] ?? [], fn (Builder $inner, array $sellerIds) => $inner->orWhere(
                fn (Builder $vendor) => $vendor->where('author_type', self::AUTHOR_TYPE_VENDOR)->whereIn('seller_id', $sellerIds),
            )));
    }
}
