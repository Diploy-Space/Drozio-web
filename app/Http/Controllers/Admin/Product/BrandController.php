<?php

namespace App\Http\Controllers\Admin\Product;

use App\Exports\BrandListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Brand\AddRequest;
use App\Http\Requests\Admin\Brand\UpdateRequest;
use App\Services\New\BrandService;
use App\Services\New\BusinessSettingService;
use App\Services\New\ProductRetailService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandController extends BaseController
{
    public function __construct(
        private readonly BrandService $brandService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly ProductRetailService $productService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => ['pagination_limit', 'pnc_language']])
            ->pluck('value', 'type');

        $brands = $this->brandService->getList(
            searchValue: $request->get('searchValue'),
            dataLimit: $settings->get('pagination_limit'),
            relations: ['translations', 'storage', 'seo'],
            withCount: ['brandAllProducts', 'brandProductOrderDetails'],
        );

        $language = getWebConfig(name: 'pnc_language') ?? [];

        return view('admin-views.brand.list', [
            'brands' => $brands,
            'language' => $language,
            'defaultLanguage' => $language[0],
        ]);
    }

    public function getAddView(): View
    {
        $language = getWebConfig(name: 'pnc_language') ?? [];

        return view('admin-views.brand.add-new', [
            'language' => $language,
            'defaultLanguage' => $language[0],
        ]);
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        $brand = $this->brandService->find(id: $id);

        if ($brand === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.retail.brand.list');
        }

        $language = getWebConfig(name: 'pnc_language') ?? [];

        return view('admin-views.brand.edit', [
            'brand' => $brand,
            'language' => $language,
            'defaultLanguage' => $language[0],
        ]);
    }

    public function add(AddRequest $request): RedirectResponse
    {
        $this->brandService->create(
            data: $request->all(),
            image: $request->file('image'),
            metaImage: $request->file('meta_image')
        );

        updateSetupGuideCacheKey(key: 'brand_setup', panel: 'admin');
        ToastMagic::success(translate('Brand added successfully'));

        return redirect()->route('admin.retail.brand.list');
    }

    public function update(UpdateRequest $request, string|int $id): RedirectResponse
    {
        $brand = $this->brandService->update(
            id: $id,
            data: $request->all(),
            image: $request->file('image'),
            metaImage: $request->file('meta_image')
        );

        if ($brand === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.retail.brand.list');
        }

        updateSetupGuideCacheKey(key: 'brand_setup', panel: 'admin');
        ToastMagic::success(translate('Brand updated successfully'));

        return redirect()->route('admin.retail.brand.list');
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->brandService->updateStatus(id: $request['id'], status: (bool) $request->get('status', 0));

        return response()->json(['success' => 1, 'message' => translate(':item updated successfully', ['item' => translate('Status')])], 200);
    }

    public function delete(Request $request): RedirectResponse
    {
        $brand = $this->brandService->find(id: $request['id'], relations: []);

        $isDeleted = $brand !== null && DB::transaction(function () use ($brand, $request): bool {
            $this->productService->updateShift(
                type: ProductRetailService::SHIFT_BRAND,
                fromId: $brand->id,
                toId: $request['brand_id'],
            );

            return $this->brandService->delete(id: $brand->id);
        });

        $isDeleted
            ? ToastMagic::success(translate('Brand deleted successfully'))
            : ToastMagic::error(translate('Not found'));

        return redirect()->route('admin.retail.brand.list');
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $searchValue = $request->get('searchValue');
        $brands = $this->brandService->getList(
            searchValue: $searchValue,
            dataLimit: 'all',
            relations: ['storage', 'seo'],
            withCount: ['brandAllProducts', 'brandProductOrderDetails'],
        );
        $counts = $this->brandService->getStatusCounts();

        return Excel::download(new BrandListExport([
            'brands' => $brands,
            'search' => $searchValue,
            'active' => $counts['active'],
            'inactive' => $counts['inactive'],
        ]), 'Brand-list.xlsx');
    }

    public function loadMoreBrands(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $filterBrands = $this->brandService->getList(dataLimit: 8, page: $page, relations: ['storage']);

        $visibleLimit = $filterBrands->perPage();
        $totalBrands = $filterBrands->total();

        return response()->json([
            'html' => view('admin-views.partials.product-filters._filter-brands', [
                'filterBrands' => $filterBrands,
                'oldBrands' => $request['filter_brand_old_ids'] ? json_decode($request['filter_brand_old_ids']) : [],
            ])->render(),
            'visibleLimit' => $visibleLimit,
            'hiddenCount' => max(0, $totalBrands - ($page * $visibleLimit)),
            'totalBrands' => $totalBrands,
        ]);
    }
}
