<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Shared\Dashboard\OrderStatusRequest;
use App\Services\DashboardChartService;
use App\Services\DashboardService;
use App\Services\New\BrandService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryManService;
use App\Services\New\OrderService;
use App\Services\New\OrderTransactionService;
use App\Services\New\ProductRetailService;
use App\Services\New\RestockProductService;
use App\Services\New\VendorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends BaseController
{
    private const STATISTICS_TYPE_SESSION_KEY = 'vendor_statistics_type';

    private const BUILDER_ADDON = 'Builder';

    private const WEBSITE_BUILDER_STATUS_KEY = 'admin_website_builder_status';

    private const STATISTICS_CACHE_MINUTES = 10;

    private const RESTOCK_REQUEST_DISPLAY_LIMIT = 100;

    public function __construct(
        private readonly BrandService $brandService,
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
        $vendorId = auth('seller')->id();
        $charts = Cache::remember(DashboardChartService::vendorYearChartsKey($vendorId), CACHE_FOR_1_HOUR, function () use ($vendorId) {
            return $this->chartService->getVendorYearCharts($vendorId);
        });
        $statisticsType = $this->getSelectedStatisticsType();

        return view('vendor-views.dashboard.index', [
            'dashboardData' => [
                'orderStatus' => $this->getOrderStatusArray(vendorId: $vendorId, statisticsType: $statisticsType),
                'customers' => $this->customerService->count(filters: [CustomerService::FILTER_ORDERED_FROM_SELLER => $vendorId]),
                'products' => $this->productService->count(filters: ['user_id' => $vendorId, 'added_by' => ProductRetailService::ADDED_BY_SELLER]),
                'orders' => $this->orderService->count(filters: ['seller_id' => $vendorId, 'seller_is' => OrderService::SELLER_IS_SELLER]),
                'brands' => $this->brandService->count(),
                'topSell' => $this->productService->getSellerTopSellingList(sellerId: $vendorId, dataLimit: DASHBOARD_TOP_SELL_DATA_LIMIT),
                'topRatedProducts' => $this->productService->getSellerMostPopularList(sellerId: $vendorId, dataLimit: DASHBOARD_DATA_LIMIT),
                'topRatedDeliveryMan' => $this->deliveryManService->getList(
                    dataLimit: DASHBOARD_DATA_LIMIT,
                    relations: ['rating'],
                    withCount: ['deliveredOrders'],
                    filters: ['seller_id' => $vendorId, DeliveryManService::FILTER_DELIVERED_FOR_SELLER_ID => $vendorId],
                    sortBy: DeliveryManService::SORT_MOST_DELIVERED,
                ),
            ],
            'vendorEarning' => $charts['vendorEarning'],
            'commissionEarn' => $charts['commissionEarn'],
            'dateType' => OrderTransactionService::STATISTICS_YEAR,
            'label' => $this->dashboardService->getDateTypeData(dateType: OrderTransactionService::STATISTICS_YEAR)['keyRange'],
            'statisticsType' => $statisticsType,
            'vendorBuilderVisible' => $this->isWebsiteBuilderBannerVisible(vendorId: $vendorId),
        ]);
    }

    public function getOrderStatus(OrderStatusRequest $request, string $type): JsonResponse
    {
        session()->put(self::STATISTICS_TYPE_SESSION_KEY, $request['statistics_type']);
        $orderStatus = $this->getOrderStatusArray(vendorId: auth('seller')->id(), statisticsType: $request['statistics_type']);

        return response()->json([
            'view' => view('vendor-views.partials._dashboard-order-status', compact('orderStatus'))->render(),
        ]);
    }

    public function getEarningStatistics(Request $request): JsonResponse
    {
        $vendorId = auth('seller')->id();
        $dateType = $request['type'];
        $dateTypeArray = $this->dashboardService->getDateTypeData(dateType: $dateType);
        $from = $dateTypeArray['from'];
        $to = $dateTypeArray['to'];
        $type = $dateTypeArray['type'];
        $range = $dateTypeArray['range'];
        $label = $dateTypeArray['keyRange'] ?? [];

        [$vendorEarning, $commissionEarn] = Cache::remember(
            "dash_vendor_earnstat_{$vendorId}_".($dateType ?: OrderTransactionService::STATISTICS_YEAR),
            now()->addMinutes(self::STATISTICS_CACHE_MINUTES),
            function () use ($from, $to, $range, $type, $vendorId) {
                return [
                    array_values($this->chartService->getEarning(from: $from, to: $to, range: $range, type: $type, sellerIs: OrderService::SELLER_IS_SELLER, sellerId: $vendorId)),
                    array_values($this->chartService->getAdminCommission(from: $from, to: $to, range: $range, type: $type, sellerId: $vendorId)),
                ];
            }
        );

        return response()->json([
            'view' => view('vendor-views.dashboard.partials.earning-statistics', compact('vendorEarning', 'commissionEarn', 'label', 'dateType'))->render(),
        ]);
    }

    public function getRealTimeActivities(): JsonResponse
    {
        $vendorId = auth('seller')->id();
        $restockSummary = $this->restockProductService->getAuthorRestockSummary(addedBy: ProductRetailService::ADDED_BY_SELLER, sellerId: $vendorId);

        return response()->json([
            'success' => 1,
            'new_order_count' => $this->orderService->count(filters: ['seller_is' => OrderService::SELLER_IS_SELLER, 'seller_id' => $vendorId, 'checked' => 0]),
            'restockProductCount' => $restockSummary['product_count'],
            'restockProduct' => $this->getRestockNotification(restockSummary: $restockSummary),
        ]);
    }

    private function getSelectedStatisticsType(): string
    {
        $statisticsType = session(self::STATISTICS_TYPE_SESSION_KEY);

        return in_array($statisticsType, OrderStatusRequest::STATISTICS_TYPES, true)
            ? $statisticsType
            : OrderStatusRequest::STATISTICS_TYPE_OVERALL;
    }

    private function getOrderStatusArray(int $vendorId, string $statisticsType): array
    {
        return Cache::remember("dash_vendor_orderstatus_{$vendorId}_{$statisticsType}", now()->addMinutes(self::STATISTICS_CACHE_MINUTES), function () use ($vendorId, $statisticsType) {
            $counts = $this->orderService->getStatusCountsForSeller(sellerId: $vendorId, dateType: $statisticsType);

            $statusWiseOrders = [];
            foreach (getOrderStatusList() as $status) {
                $statusWiseOrders[$status] = $counts[$status] ?? 0;
            }

            return $statusWiseOrders;
        });
    }

    private function isWebsiteBuilderBannerVisible(int $vendorId): bool
    {
        return getCheckAddonPublishedStatus(moduleName: self::BUILDER_ADDON)
            && getWebConfig(name: self::WEBSITE_BUILDER_STATUS_KEY)
            && $this->vendorService->hasVisibleWebsite(vendorId: $vendorId);
    }

    private function getRestockNotification(array $restockSummary): array
    {
        $productCount = $restockSummary['product_count'];
        $route = route('vendor.retail.products.request-restock-list');

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
                'body' => ($productCount < self::RESTOCK_REQUEST_DISPLAY_LIMIT ? $productCount : '99 +').' '.translate('More products have restock request'),
                'image' => dynamicAsset(path: 'public/assets/back-end/img/icons/restock-request-icon.svg'),
                'route' => $route,
            ];
        }

        return [];
    }
}
