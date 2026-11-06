<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Shared\Dashboard\OrderStatusRequest;
use App\Services\DashboardChartService;
use App\Services\DashboardService;
use App\Services\New\AdminWalletService;
use App\Services\New\BrandService;
use App\Services\New\ChattingService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryManService;
use App\Services\New\OrderService;
use App\Services\New\OrderTransactionService;
use App\Services\New\ProductRetailService;
use App\Services\New\RestockProductService;
use App\Services\New\VendorService;
use App\Utils\Helpers;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardController extends BaseController
{
    private const STATISTICS_TYPE_SESSION_KEY = 'statistics_type';

    private const DASHBOARD_MODULE = 'retail.dashboard';

    private const MASTER_ADMIN_ROLE_ID = 1;

    private const STATISTICS_CACHE_MINUTES = 10;

    private const RESTOCK_REQUEST_DISPLAY_LIMIT = 100;

    public function __construct(
        private readonly AdminWalletService $adminWalletService,
        private readonly BrandService $brandService,
        private readonly ChattingService $chattingService,
        private readonly CustomerService $customerService,
        private readonly DeliveryManService $deliveryManService,
        private readonly OrderService $orderService,
        private readonly ProductRetailService $productService,
        private readonly RestockProductService $restockProductService,
        private readonly VendorService $vendorService,
        private readonly DashboardService $dashboardService,
        private readonly DashboardChartService $chartService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        if (empty(session(self::STATISTICS_TYPE_SESSION_KEY))) {
            session()->put(self::STATISTICS_TYPE_SESSION_KEY, OrderService::DATE_TYPE_THIS_YEAR);
        }

        $statisticsType = session(self::STATISTICS_TYPE_SESSION_KEY);

        if (! $this->canViewDashboard()) {
            return view('admin-views.system.dashboard', [
                'canViewDashboard' => false,
                'statisticsType' => $statisticsType,
            ]);
        }

        $charts = Cache::remember(DashboardChartService::adminYearChartsKey(), CACHE_FOR_1_HOUR, function () {
            return $this->chartService->getAdminYearCharts();
        });
        $adminWallet = $this->adminWalletService->findBy(params: ['admin_id' => AdminWalletService::IN_HOUSE_ADMIN_ID]);

        $data = $this->getOrderStatusData(statisticsType: $statisticsType) + [
            'brand' => $this->brandService->count(),
            'topSellProduct' => $this->productService->getSellerTopSellingList(
                sellerId: null,
                dataLimit: DASHBOARD_TOP_SELL_DATA_LIMIT,
                addedBy: ProductRetailService::ADDED_BY_IN_HOUSE,
            ),
            'mostRatedProducts' => $this->productService->getSellerMostPopularList(
                sellerId: null,
                dataLimit: DASHBOARD_DATA_LIMIT,
                addedBy: ProductRetailService::ADDED_BY_IN_HOUSE,
            ),
            'topVendorByEarning' => $this->getVendorsHavingShop(vendors: $this->vendorService->getList(
                dataLimit: DASHBOARD_DATA_LIMIT,
                relations: ['shop'],
                filters: [VendorService::FILTER_HAS_EARNING => true],
                sortBy: VendorService::SORT_TOTAL_EARNING,
            )),
            'vendorTopSellProduct' => $this->productService->getSellerTopSellingList(
                sellerId: null,
                dataLimit: DASHBOARD_TOP_SELL_DATA_LIMIT,
            ),
            'vendorMostRatedProducts' => $this->productService->getSellerMostPopularList(
                sellerId: null,
                dataLimit: DASHBOARD_DATA_LIMIT,
            ),
            'top_customer' => $this->customerService->getList(
                dataLimit: DASHBOARD_DATA_LIMIT,
                withCount: ['orders'],
                filters: ['is_active' => 1, CustomerService::FILTER_EXCLUDE_WALKING_CUSTOMER => true],
                sortBy: CustomerService::SORT_ORDER_AMOUNT,
            ),
            'topVendorListByWishlist' => $this->getVendorsHavingShop(vendors: $this->vendorService->getList(
                dataLimit: DASHBOARD_DATA_LIMIT,
                relations: ['shop'],
                withCount: ['product as wishlist_count' => fn (Builder $product) => $product->whereHas('wishList')],
                filters: [VendorService::FILTER_HAS_WISHLISTED_PRODUCTS => true],
                sortBy: VendorService::SORT_WISHLIST_COUNT,
            )),
            'topRatedDeliveryMan' => $this->deliveryManService->getList(
                dataLimit: DASHBOARD_DATA_LIMIT,
                withCount: ['deliveredOrders'],
                filters: ['seller_id' => DeliveryManService::SELLER_ADMIN, DeliveryManService::FILTER_HAS_DELIVERED_ORDERS => true],
                sortBy: DeliveryManService::SORT_TOP_RATED,
            ),
            'inhouse_earning' => $adminWallet['inhouse_earning'] ?? 0,
            'commission_earned' => $adminWallet['commission_earned'] ?? 0,
            'delivery_charge_earned' => $adminWallet['delivery_charge_earned'] ?? 0,
            'pending_amount' => $adminWallet['pending_amount'] ?? 0,
            'total_tax_collected' => $adminWallet['total_tax_collected'] ?? 0,
            'getTotalCustomerCount' => $this->customerService->count(filters: [CustomerService::FILTER_EXCLUDE_WALKING_CUSTOMER => true]),
            'getTotalVendorCount' => $this->vendorService->count(),
            'getTotalDeliveryManCount' => $this->deliveryManService->count(filters: ['seller_id' => DeliveryManService::SELLER_ADMIN]),
        ];

        return view('admin-views.system.dashboard', [
            'data' => $data,
            'inHouseEarning' => $charts['inHouseEarning'],
            'vendorEarning' => $charts['vendorEarning'],
            'commissionEarn' => $charts['commissionEarn'],
            'inHouseOrderEarningArray' => $charts['inHouseOrderEarningArray'],
            'vendorOrderEarningArray' => $charts['vendorOrderEarningArray'],
            'label' => $this->dashboardService->getDateTypeData(dateType: OrderTransactionService::STATISTICS_YEAR)['keyRange'],
            'dateType' => OrderTransactionService::STATISTICS_YEAR,
            'canViewDashboard' => true,
            'statisticsType' => $statisticsType,
        ]);
    }

    public function getOrderStatus(OrderStatusRequest $request): JsonResponse
    {
        session()->put(self::STATISTICS_TYPE_SESSION_KEY, $request['statistics_type']);
        $data = $this->getOrderStatusData(statisticsType: $request['statistics_type']);

        return response()->json(['view' => view('admin-views.partials._dashboard-order-status', compact('data'))->render()], 200);
    }

    public function getOrderStatistics(Request $request): JsonResponse
    {
        $dateType = $request['type'];
        $dateTypeArray = $this->dashboardService->getDateTypeData(dateType: $dateType);
        $from = $dateTypeArray['from'];
        $to = $dateTypeArray['to'];
        $type = $dateTypeArray['type'];
        $range = $dateTypeArray['range'];
        $label = $dateTypeArray['keyRange'] ?? [];

        [$inHouseOrderEarningArray, $vendorOrderEarningArray] = Cache::remember(
            'dash_admin_orderstat_'.($dateType ?: OrderTransactionService::STATISTICS_YEAR),
            now()->addMinutes(self::STATISTICS_CACHE_MINUTES),
            function () use ($from, $to, $range, $type) {
                return [
                    array_values($this->chartService->getOrderStatisticsData(from: $from, to: $to, range: $range, type: $type, sellerIs: OrderService::SELLER_IS_ADMIN)),
                    array_values($this->chartService->getOrderStatisticsData(from: $from, to: $to, range: $range, type: $type, sellerIs: OrderService::SELLER_IS_SELLER)),
                ];
            }
        );

        return response()->json([
            'view' => view('admin-views.system.partials.order-statistics', compact('inHouseOrderEarningArray', 'vendorOrderEarningArray', 'label', 'dateType'))->render(),
        ]);
    }

    public function getEarningStatistics(Request $request): JsonResponse
    {
        $dateType = $request['type'];
        $dateTypeArray = $this->dashboardService->getDateTypeData(dateType: $dateType);
        $from = $dateTypeArray['from'];
        $to = $dateTypeArray['to'];
        $type = $dateTypeArray['type'];
        $range = $dateTypeArray['range'];
        $label = $dateTypeArray['keyRange'] ?? [];

        [$inHouseEarning, $vendorEarning, $commissionEarn] = Cache::remember(
            'dash_admin_earnstat_'.($dateType ?: OrderTransactionService::STATISTICS_YEAR),
            now()->addMinutes(self::STATISTICS_CACHE_MINUTES),
            function () use ($from, $to, $range, $type) {
                return [
                    array_values($this->chartService->getEarning(from: $from, to: $to, range: $range, type: $type, sellerIs: OrderService::SELLER_IS_ADMIN)),
                    array_values($this->chartService->getEarning(from: $from, to: $to, range: $range, type: $type, sellerIs: OrderService::SELLER_IS_SELLER)),
                    array_values($this->chartService->getAdminCommission(from: $from, to: $to, range: $range, type: $type)),
                ];
            }
        );

        return response()->json([
            'view' => view('admin-views.system.partials.earning-statistics', compact('inHouseEarning', 'vendorEarning', 'commissionEarn', 'label', 'dateType'))->render(),
        ]);
    }

    public function getRealTimeActivities(): JsonResponse
    {
        $newOrderCount = $this->orderService->count(filters: ['checked' => 0]);
        $restockSummary = $this->restockProductService->getAuthorRestockSummary(addedBy: RestockProductService::ADDED_BY_IN_HOUSE, sellerId: null);

        $chattingFilters = [
            'admin_id' => ChattingService::ADMIN_THREAD_ID,
            'seen_by_admin' => 0,
            'notification_receiver' => ChattingService::TYPE_ADMIN,
            'seen_notification' => 0,
        ];
        $unseenMessageCount = $this->chattingService->count(filters: $chattingFilters);
        $this->chattingService->updateWhere(params: $chattingFilters, data: ['seen_notification' => 1]);

        return response()->json([
            'success' => 1,
            'new_order_count' => $newOrderCount,
            'restockProductCount' => $restockSummary['product_count'],
            'restockProduct' => $this->getRestockNotification(restockSummary: $restockSummary),
            'newMessagesExist' => $unseenMessageCount,
            'message' => $unseenMessageCount > 1 ? $unseenMessageCount.' '.translate('New Message') : translate('New Message'),
        ]);
    }

    private function canViewDashboard(): bool
    {
        $admin = auth('admin')->user();

        return $admin === null
            || $admin->admin_role_id == self::MASTER_ADMIN_ROLE_ID
            || Helpers::module_permission_check(self::DASHBOARD_MODULE);
    }

    private function getOrderStatusData(string $statisticsType): array
    {
        return Cache::remember("dash_admin_orderstatus_{$statisticsType}", now()->addMinutes(self::STATISTICS_CACHE_MINUTES), function () use ($statisticsType) {
            $createdBetween = $this->getStatisticsDateRange(statisticsType: $statisticsType);
            $statusCounts = $this->orderService->getStatusCounts(filters: [OrderService::FILTER_DATE_TYPE => $statisticsType]);

            return [
                'order' => array_sum($statusCounts),
                'failed' => $statusCounts[OrderService::STATUS_FAILED] ?? 0,
                'pending' => $statusCounts[OrderService::STATUS_PENDING] ?? 0,
                'returned' => $statusCounts[OrderService::STATUS_RETURNED] ?? 0,
                'canceled' => $statusCounts[OrderService::STATUS_CANCELED] ?? 0,
                'confirmed' => $statusCounts[OrderService::STATUS_CONFIRMED] ?? 0,
                'delivered' => $statusCounts[OrderService::STATUS_DELIVERED] ?? 0,
                'processing' => $statusCounts[OrderService::STATUS_PROCESSING] ?? 0,
                'out_for_delivery' => $statusCounts[OrderService::STATUS_OUT_FOR_DELIVERY] ?? 0,
                'store' => $this->vendorService->count(filters: [VendorService::FILTER_JOINED_BETWEEN => $createdBetween]),
                'product' => $this->productService->count(filters: [ProductRetailService::FILTER_CREATED_BETWEEN => $createdBetween]),
                'customer' => $this->customerService->count(filters: [
                    CustomerService::FILTER_EXCLUDE_WALKING_CUSTOMER => true,
                    CustomerService::FILTER_JOINED_BETWEEN => $createdBetween,
                ]),
            ];
        });
    }

    private function getStatisticsDateRange(string $statisticsType): array
    {
        return match ($statisticsType) {
            OrderService::DATE_TYPE_TODAY => [now()->startOfDay(), now()->endOfDay()],
            OrderService::DATE_TYPE_THIS_WEEK => [now()->startOfWeek(), now()->endOfWeek()],
            OrderService::DATE_TYPE_THIS_MONTH => [now()->startOfMonth(), now()->endOfMonth()],
            OrderService::DATE_TYPE_THIS_YEAR => [now()->startOfYear(), now()->endOfYear()],
            default => [],
        };
    }

    private function getVendorsHavingShop(LengthAwarePaginator $vendors): Collection
    {
        return collect($vendors->items())->filter(fn ($vendor) => $vendor->shop !== null)->values();
    }

    private function getRestockNotification(array $restockSummary): array
    {
        $productCount = $restockSummary['product_count'];
        $route = route('admin.retail.products.request-restock-list');

        if ($productCount === 1) {
            $customerCount = $restockSummary['customer_count'];
            $product = $restockSummary['latest']?->product;

            return [
                'title' => $product?->name ?? '',
                'body' => $customerCount < self::RESTOCK_REQUEST_DISPLAY_LIMIT
                    ? translate('This product has').' '.$customerCount.' '.translate('Restock Request')
                    : translate('This product has').' 99+ '.translate('Restock Request'),
                'image' => getStorageImages(path: $product?->thumbnail_full_url ?? '', type: 'product'),
                'route' => $route,
            ];
        }

        if ($productCount > 1) {
            return [
                'title' => translate('Restock Request'),
                'body' => $productCount < self::RESTOCK_REQUEST_DISPLAY_LIMIT
                    ? ($productCount.' '.translate('Products have restock request'))
                    : ('99 +'.' '.translate('More products have restock request')),
                'image' => dynamicAsset(path: 'public/assets/back-end/img/icons/restock-request-icon.svg'),
                'route' => $route,
            ];
        }

        return [];
    }
}
