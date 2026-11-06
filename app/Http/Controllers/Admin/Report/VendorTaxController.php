<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Report\VendorTax\DetailsRequest;
use App\Http\Requests\Admin\Report\VendorTax\FilterRequest;
use App\Services\New\ShopService;
use App\Services\New\TaxReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Facades\Excel;
use Modules\TaxModule\app\Exports\VendorTaxExport;
use Modules\TaxModule\app\Exports\VendorWiseTaxExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VendorTaxController extends Controller
{
    private const PER_PAGE = 10;

    private const LIST_EXPORT_FILE_NAME = 'VendorWiseTaxExport';

    private const DETAILS_EXPORT_FILE_SUFFIX = 's TaxExport';

    private const LIST_EXPORT_RELATIONS = ['shop', 'orderTaxes'];

    private const DETAILS_RELATIONS = ['order', 'orderTaxes'];

    private const SHOP_RELATIONS = ['seller'];

    private const EXPORT_AUTH_TYPE = 'admin';

    private const TAX_TYPE_ORDER_WISE = 'order_wise';

    private const TAX_TYPE_PRODUCT_WISE = 'product_wise';

    private const TAX_TYPE_CATEGORY_WISE = 'category_wise';

    public function __construct(
        private readonly TaxReportService $taxReportService,
        private readonly ShopService $shopService,
    ) {}

    public function index(FilterRequest $request): View
    {
        [$startDate, $endDate] = $request->dateRange();
        $summary = $this->taxReportService->getVendorTaxSummary(from: $startDate, to: $endDate, shopId: $request->shopId(), searchValue: $request['search']);

        $shopTaxList = $this->taxReportService->getVendorTaxShopList(
            from: $startDate,
            to: $endDate,
            shopId: $request->shopId(),
            searchValue: $request['search'],
            dataLimit: self::PER_PAGE,
            page: $request['page'],
        )->withPath($request->url())->withQueryString();

        return view('admin-views.report.vendor-tax', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'shops' => $this->getShopOptions(),
            'shopTaxList' => $shopTaxList->setCollection($this->attachShopsAndTaxes(rows: $shopTaxList->getCollection(), request: $request)),
            'totalTax' => $summary['total_tax'],
            'totalOrders' => $summary['total_orders'],
            'totalOrderAmount' => $summary['total_order_amount'],
            'isInHouseShopVerified' => isInHouseShopVerified(),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        [$startDate, $endDate] = $request->dateRange();
        $summary = $this->taxReportService->getVendorTaxSummary(from: $startDate, to: $endDate, shopId: $request->shopId(), searchValue: $request['search']);
        $orderTransactions = $this->taxReportService->getVendorTaxShopTransactions(
            from: $startDate,
            to: $endDate,
            shopId: $request->shopId(),
            searchValue: $request['search'],
            relations: self::LIST_EXPORT_RELATIONS,
        );

        return Excel::download(new VendorWiseTaxExport([
            'startDate' => $startDate,
            'endDate' => $endDate,
            'shops' => $this->getShopOptions(),
            'shopTaxList' => $orderTransactions->groupBy('shop_id'),
            'totalTax' => $summary['total_tax'],
            'totalOrders' => $summary['total_orders'],
            'totalOrderAmount' => $summary['total_order_amount'],
            'orderTransactions' => $orderTransactions,
            'auth_type' => self::EXPORT_AUTH_TYPE,
        ]), self::LIST_EXPORT_FILE_NAME.'.'.$request->exportExtension());
    }

    public function details(DetailsRequest $request): View
    {
        [$startDate, $endDate] = $request->dateRange();
        $shop = $this->findShop(request: $request);

        return view('admin-views.report.vendor-tax-details', [
            ...$this->getDetailsSummary(request: $request, shop: $shop),
            'taxTypeLabels' => $this->getTaxTypeLabels(),
            'defaultTaxType' => self::TAX_TYPE_ORDER_WISE,
            'isInHouseShopVerified' => isInHouseShopVerified(),
            'orderTransactions' => $this->taxReportService->getVendorTaxOrderList(
                shopId: $shop->id,
                from: $startDate,
                to: $endDate,
                searchValue: $request['search'],
                relations: self::DETAILS_RELATIONS,
                dataLimit: self::PER_PAGE,
                page: $request['page'],
            )->withQueryString(),
        ]);
    }

    public function exportDetails(DetailsRequest $request): BinaryFileResponse
    {
        [$startDate, $endDate] = $request->dateRange();
        $shop = $this->findShop(request: $request);

        return Excel::download(new VendorTaxExport([
            ...$this->getDetailsSummary(request: $request, shop: $shop),
            'orderTransactions' => $this->taxReportService->getVendorTaxOrderList(
                shopId: $shop->id,
                from: $startDate,
                to: $endDate,
                searchValue: $request['search'],
                relations: self::DETAILS_RELATIONS,
                dataLimit: 'all',
            ),
        ]), $shop['name'].self::DETAILS_EXPORT_FILE_SUFFIX.'.'.$request->exportExtension());
    }

    private function getDetailsSummary(DetailsRequest $request, Model $shop): array
    {
        [$startDate, $endDate] = $request->dateRange();
        $summary = $this->taxReportService->getVendorTaxOrderSummary(shopId: $shop->id, from: $startDate, to: $endDate, searchValue: $request['search']);

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'shop' => $shop,
            'totalTax' => $summary['total_tax'],
            'totalOrders' => $summary['total_orders'],
            'totalOrderAmount' => $summary['total_order_amount'],
            'search' => $request['search'],
        ];
    }

    private function findShop(DetailsRequest $request): Model
    {
        $shop = $this->shopService->find(id: $request->shopId(), relations: self::SHOP_RELATIONS);

        abort_if($shop === null, 404);

        return $shop;
    }

    private function getShopOptions(): Collection
    {
        return $this->shopService->getList(
            dataLimit: 'all',
            filters: [ShopService::FILTER_SELLER_APPROVED => true],
            sortBy: ShopService::SORT_OLDEST,
        )->prepend(getInHouseShopConfig());
    }

    private function attachShopsAndTaxes(SupportCollection $rows, FilterRequest $request): SupportCollection
    {
        [$startDate, $endDate] = $request->dateRange();
        $shopIds = $rows->pluck('shop_id')->filter()->values()->all();
        $shops = $this->shopService->getList(dataLimit: 'all', relations: self::SHOP_RELATIONS, filters: ['id' => $shopIds], sortBy: ShopService::SORT_OLDEST)->keyBy('id');
        $taxes = $this->taxReportService->getVendorTaxBreakdownByShop(from: $startDate, to: $endDate, shopIds: $shopIds, searchValue: $request['search']);

        return $rows->map(fn (array $row): array => $row + [
            'shop' => $shops->get($row['shop_id']),
            'taxes' => $taxes[$row['shop_id']] ?? [],
        ]);
    }

    /** @return array<string, string> */
    private function getTaxTypeLabels(): array
    {
        return [
            self::TAX_TYPE_ORDER_WISE => translate('Order wise'),
            self::TAX_TYPE_PRODUCT_WISE => translate('Product wise'),
            self::TAX_TYPE_CATEGORY_WISE => translate('Category wise'),
        ];
    }
}
