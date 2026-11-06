<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Customer\ShopResource;
use App\Services\New\BusinessSettingService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewService;
use App\Services\New\ShopService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ShopController extends BaseApiController
{
    private const MAX_RATING = 5;

    private const TYPE_ALL = 'all';

    private const TYPE_TOP = 'top';

    private const TYPE_NEW = 'new';

    private const TYPE_RANDOM = 'random';

    private const TYPES = [self::TYPE_ALL, self::TYPE_TOP, self::TYPE_NEW, self::TYPE_RANDOM];

    private const PRIORITY_SETTINGS = [
        self::TYPE_ALL => BusinessSettingService::VENDOR_LIST_PRIORITY,
        self::TYPE_TOP => BusinessSettingService::TOP_VENDOR_LIST_PRIORITY,
    ];

    /** @var array<int, string> */
    private const MINIMUM_RATING_POINTS = ['4', '3.5', '3', '2'];

    public function __construct(
        private readonly ShopService $shopService,
        private readonly ProductRetailService $productService,
        private readonly ReviewService $reviewService,
        private readonly OrderService $orderService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Shop list',
        tag: 'Shop',
        query: [
            'limit' => 'nullable|string',
            'offset' => 'nullable|integer',
            'search' => 'nullable|string',
            'type' => 'nullable|string|in:all,top,new,random',
        ],
        auth: false,
        guard: 'api',
    )]
    public function getList(Request $request): JsonResponse
    {
        $args = [
            'search' => $request->query('search'),
            'type' => $request->query('type'),
        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);
        $type = in_array($args['type'], self::TYPES, true) ? $args['type'] : self::TYPE_ALL;
        $args['type'] = $type;

        $plan = $this->listPlan($type);

        $shops = $this->shopService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: ['seller'],
            filters: $plan['filters'],
            sortBy: $plan['sort_by'],
            sortIds: $plan['sort_ids'],
        );

        $this->attachSummaries($shops);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ShopResource::collection($shops),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($shops),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Shop details by slug',
        tag: 'Shop',
        status: [404],
        auth: false,
        guard: 'api',
    )]
    public function find(string $slug): JsonResponse
    {
        $shop = $this->shopService->findBy(params: ['slug' => $slug], relations: ['seller']);

        if ($shop === null) {
            return $this->responseFormatter(config: config('response.shop_not_found'));
        }

        $this->attachSummaries(collect([$shop]));

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new ShopResource($shop)],
        );
    }

    /** @return array{filters: array<string, mixed>, sort_by: string, sort_ids: array<int, int>} */
    private function listPlan(string $type): array
    {
        $filters = [ShopService::FILTER_VISIBLE => true];

        if ($type === self::TYPE_TOP) {
            $filters[ShopService::FILTER_OWNERS] = $this->orderService->getOwnersWithOrders();
        }

        if ($type === self::TYPE_NEW) {
            return ['filters' => $filters, 'sort_by' => ShopService::SORT_LATEST, 'sort_ids' => []];
        }

        if ($type === self::TYPE_RANDOM) {
            return ['filters' => $filters, 'sort_by' => ShopService::SORT_RANDOM, 'sort_ids' => []];
        }

        $priority = $this->prioritySetup(settingType: self::PRIORITY_SETTINGS[$type]);

        if (($priority['custom_sorting_status'] ?? 0) != 1) {
            return ['filters' => $filters, 'sort_by' => ShopService::SORT_LATEST, 'sort_ids' => []];
        }

        return $this->priorityPlan($filters, $priority);
    }

    /** @return array<string, mixed> */
    private function prioritySetup(string $settingType): array
    {
        $value = $this->businessSettingService->findBy(params: ['type' => $settingType])?->value;

        return json_decode((string) $value, true) ?: [];
    }

    /**
     * The admin's priority setup for a shop rail hides rows and orders what is left by figures that
     * live in other entities, so the shops are resolved once, ranked here, and handed back to
     * ShopService as an explicit id order.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $priority
     * @return array{filters: array<string, mixed>, sort_by: string, sort_ids: array<int, int>}
     */
    private function priorityPlan(array $filters, array $priority): array
    {
        $shops = $this->shopService->getList(dataLimit: 'all', filters: $filters);
        $summaries = $this->summariesFor($shops);

        $minimumRating = $priority['minimum_rating_point'] ?? null;
        if (in_array($minimumRating, self::MINIMUM_RATING_POINTS, true)) {
            $shops = $shops->filter(fn (Model $shop) => $summaries[$shop->id]['avg_rating'] >= (float) $minimumRating);
        }

        if (($priority['vacation_mode_sorting'] ?? null) === 'hide') {
            $shops = $shops->filter(fn (Model $shop) => ! $this->shopService->isOnVacationNow(shop: $shop));
        }

        if (($priority['temporary_close_sorting'] ?? null) === 'hide') {
            $shops = $shops->filter(fn (Model $shop) => ! $this->shopService->isTemporarilyClosed(shop: $shop));
        }

        $shops = $this->applyPrioritySort($shops, $summaries, $priority);

        return [
            'filters' => $filters + ['id' => $shops->pluck('id')->all()],
            'sort_by' => ShopService::SORT_GIVEN,
            'sort_ids' => $shops->pluck('id')->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $summaries
     * @param  array<string, mixed>  $priority
     */
    private function applyPrioritySort(Collection $shops, array $summaries, array $priority): Collection
    {
        $by = fn (string $key) => fn (Model $shop) => $summaries[$shop->id][$key];

        $shops = match ($priority['sort_by'] ?? null) {
            'order', 'most_order' => $shops->sortByDesc($by('orders_count')),
            'reviews_count' => $shops->sortByDesc($by('total_review')),
            'rating' => $shops->sortByDesc($by('avg_rating')),
            'rating_and_review' => $shops->sortByDesc($by('total_review'))->sortByDesc($by('avg_rating')),
            'first_created' => $shops->sortBy('id'),
            'a_to_z' => $shops->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            'z_to_a' => $shops->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE),
            default => $shops->sortByDesc('id'),
        };

        if (($priority['vacation_mode_sorting'] ?? null) === 'desc') {
            $shops = $shops->sortBy(fn (Model $shop) => $this->shopService->isOnVacationNow(shop: $shop));
        }

        if (($priority['temporary_close_sorting'] ?? null) === 'desc') {
            $shops = $shops->sortBy(fn (Model $shop) => $this->shopService->isTemporarilyClosed(shop: $shop));
        }

        return $shops->values();
    }

    private function attachSummaries(mixed $shops): void
    {
        $summaries = $this->summariesFor($shops);

        foreach ($shops as $shop) {
            foreach ($summaries[$shop->id] as $key => $value) {
                $shop->{$key} = $value;
            }

            $shop->temporary_close_now = $this->shopService->isTemporarilyClosed(shop: $shop);
            $shop->is_vacation_mode_now = $this->shopService->isOnVacationNow(shop: $shop);
            $shop->minimum_order_amount = $this->minimumOrderAmount(shop: $shop);
        }
    }

    /**
     * Every figure the rows carry, in three grouped queries for the whole set rather than per row.
     *
     * @return array<int, array{avg_rating: float, rating_percentage: int, total_review: int, total_product: int, orders_count: int}>
     */
    private function summariesFor(mixed $shops): array
    {
        $sellerIds = [];
        $includeInHouse = false;
        foreach ($shops as $shop) {
            $shop->author_type === ShopService::AUTHOR_TYPE_ADMIN
                ? $includeInHouse = true
                : $sellerIds[] = (int) $shop->seller_id;
        }

        $productIds = $this->productService->getActiveIdsBySellers(sellerIds: $sellerIds, includeInHouse: $includeInHouse);
        $ratings = $this->reviewService->getRatingSummaryByGroup(productIdsByGroup: $productIds);
        $orders = $this->orderService->countBySellers(sellerIds: $sellerIds, includeInHouse: $includeInHouse);

        $summaries = [];
        foreach ($shops as $shop) {
            $owner = $shop->author_type === ShopService::AUTHOR_TYPE_ADMIN
                ? ProductRetailService::OWNER_IN_HOUSE
                : (int) $shop->seller_id;
            $rating = $ratings[$owner] ?? ['avg_rating' => 0.0, 'total_review' => 0];

            $summaries[$shop->id] = [
                'avg_rating' => $rating['avg_rating'],
                'rating_percentage' => (int) round(($rating['avg_rating'] * 100) / self::MAX_RATING),
                'total_review' => $rating['total_review'],
                'total_product' => count($productIds[$owner] ?? []),
                'orders_count' => $orders[$owner] ?? 0,
            ];
        }

        return $summaries;
    }

    private function minimumOrderAmount(Model $shop): float
    {
        $settings = $this->businessSettingService->getList(dataLimit: 'all', filters: [
            'type' => [
                BusinessSettingService::MINIMUM_ORDER_AMOUNT_STATUS,
                BusinessSettingService::MINIMUM_ORDER_AMOUNT_BY_SELLER,
                BusinessSettingService::MINIMUM_ORDER_AMOUNT,
            ],
        ])->pluck('value', 'type');

        if (! (int) ($settings[BusinessSettingService::MINIMUM_ORDER_AMOUNT_STATUS] ?? 0)) {
            return 0.0;
        }

        if ($shop->author_type === ShopService::AUTHOR_TYPE_ADMIN) {
            return (float) ($settings[BusinessSettingService::MINIMUM_ORDER_AMOUNT] ?? 0);
        }

        return (int) ($settings[BusinessSettingService::MINIMUM_ORDER_AMOUNT_BY_SELLER] ?? 0)
            ? (float) ($shop->seller?->minimum_order_amount ?? 0)
            : 0.0;
    }
}
