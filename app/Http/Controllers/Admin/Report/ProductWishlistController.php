<?php

namespace App\Http\Controllers\Admin\Report;

use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\WishlistProductReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Report\ProductWishlist\FilterRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\ProductWishlistReportService;
use App\Services\New\VendorService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductWishlistController extends Controller
{
    public function __construct(
        private readonly ProductWishlistReportService $productWishlistReportService,
        private readonly VendorService $vendorService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $sellerId = $request->sellerId();

        return view('admin-views.report.product-in-wishlist', [
            'sellerId' => $sellerId ?? ProductWishlistReportService::SELLER_ALL,
            'sort' => $request->sort(),
            'search' => $request['search'],
            'vendors' => $this->vendorService->getList(
                dataLimit: 'all',
                relations: ['shop'],
                filters: ['status' => VendorService::STATUS_APPROVED],
                sortBy: VendorService::SORT_OLDEST,
            ),
            'products' => $this->productWishlistReportService->getProductWishlistList(
                sellerId: $sellerId,
                searchValue: $request['search'],
                sortBy: $request->sort(),
                dataLimit: $this->businessSettingService->getValueByType(type: 'pagination_limit', default: DEFAULT_DATA_LIMIT),
                page: $request['page'],
            )->withQueryString(),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $sellerId = $request->sellerId();

        return Excel::download(new WishlistProductReportExport([
            'search' => $request['search'],
            'sort' => $request->sort(),
            'storeName' => $this->storeName(sellerId: $sellerId),
            'products' => $this->productWishlistReportService->getProductWishlistList(
                sellerId: $sellerId,
                searchValue: $request['search'],
                sortBy: $request->sort(),
                dataLimit: 'all',
            ),
        ]), Report::PRODUCT_WISHLISTED_REPORT);
    }

    private function storeName(int|string|null $sellerId): ?string
    {
        return match (true) {
            $sellerId === null => null,
            $sellerId === ProductWishlistReportService::SELLER_IN_HOUSE => translate('In-house'),
            default => $this->vendorService->find(id: $sellerId, relations: ['shop'])?->shop?->name,
        };
    }
}
