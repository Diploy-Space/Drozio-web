<?php

namespace App\Http\Controllers\Admin\Product;

use App\Exports\CategoryListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Category\AddRequest;
use App\Http\Requests\Admin\Category\DeleteRequest;
use App\Http\Requests\Admin\Category\UpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\CategoryService;
use App\Services\New\ProductRetailService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\TaxModule\app\Traits\VatTaxManagement;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CategoryController extends BaseController
{
    use VatTaxManagement;

    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ProductRetailService $productService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => ['pagination_limit', 'pnc_language']])
            ->pluck('value', 'type');

        $taxData = $this->getTaxSystemType();
        $categoryWiseTax = $taxData['categoryWiseTax'];

        $categories = $this->categoryService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $settings->get('pagination_limit'),
            relations: ['translations', 'seo', ...$this->getTaxRelations(categoryWiseTax: $categoryWiseTax)],
            withCount: ['product'],
            withChildCounts: true,
            filters: ['position' => 0],
            sortBy: CategoryService::SORT_NEWEST_ID,
        )->appends($request->only(['searchValue']));

        $languages = json_decode((string) $settings->get('pnc_language'), true) ?? null;

        $categoryOptions = $this->categoryService->getListForParentSelect(position: CategoryService::POSITION_CATEGORY)
            ->map(fn ($category): array => ['id' => $category['id'], 'name' => $category['defaultname']])
            ->values();

        return view('admin-views.category.view', [
            'categories' => $categories,
            'categoryOptions' => $categoryOptions,
            'categoriesWithTrans' => $categories,
            'languages' => $languages,
            'defaultLanguage' => $languages[0],
            'taxVats' => $taxData['taxVats'],
            'categoryWiseTax' => $categoryWiseTax,
            'categoryPosition' => CategoryService::POSITION_CATEGORY,
        ]);
    }

    public function getUpdateView(Request $request): View|RedirectResponse
    {
        $category = $this->categoryService->find(id: $request['id']);

        if ($category === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.retail.category.view');
        }

        $languages = json_decode((string) $this->businessSettingService->findBy(params: ['type' => 'pnc_language'])?->value, true) ?? null;

        return view('admin-views.category.category-edit', [
            'category' => $category,
            'languages' => $languages,
            'defaultLanguage' => $languages[0],
        ]);
    }

    public function add(AddRequest $request): RedirectResponse|JsonResponse
    {
        $this->categoryService->create(
            data: $request->all(),
            position: CategoryService::POSITION_CATEGORY,
            image: $request->file('image'),
            metaImage: $request->file('meta_image'),
        );

        updateSetupGuideCacheKey(key: 'category_setup', panel: 'admin');

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => translate(':item added successfully', ['item' => translate('Category')]),
                'redirect_url' => route('admin.retail.category.view'),
            ]);
        }

        ToastMagic::success(translate(':item added successfully', ['item' => translate('Category')]));

        return back();
    }

    public function update(UpdateRequest $request): RedirectResponse
    {
        $category = $this->categoryService->update(
            id: $request['id'],
            data: $request->all(),
            image: $request->file('image'),
            metaImage: $request->file('meta_image'),
        );

        if ($category === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        updateSetupGuideCacheKey(key: 'category_setup', panel: 'admin');

        ToastMagic::success($this->getUpdatedMessage(position: $category->position));

        return back();
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $statusType = $request['status_type'] ?? CategoryService::STATUS_TYPE_HOME;

        abort_unless(in_array($statusType, CategoryService::STATUS_COLUMNS, true), 400);

        abort_unless($this->categoryService->updateStatus(
            id: $request['id'],
            status: $request->boolean($statusType),
            column: $statusType,
        ), 404);

        updateSetupGuideCacheKey(key: 'category_setup', panel: 'admin');

        return response()->json([
            'success' => 1,
            'message' => $statusType === CategoryService::STATUS_TYPE_HOME
                ? translate('Status updated successfully')
                : translate(':item updated successfully', ['item' => translate('Auction home status')]),
        ], 200);
    }

    public function delete(DeleteRequest $request): RedirectResponse
    {
        $category = $this->categoryService->find(id: $request['id'], relations: []);

        $isDeleted = $category !== null && DB::transaction(function () use ($category, $request): bool {
            $this->productService->updateShift(
                type: ProductRetailService::SHIFT_CATEGORY,
                fromId: $category->id,
                toId: $request['category_id'],
            );

            return $this->categoryService->delete(id: $category->id);
        });

        $isDeleted
            ? ToastMagic::success(translate('Deleted successfully'))
            : ToastMagic::error(translate('Not found'));

        return redirect()->back();
    }

    public function getExportList(Request $request): BinaryFileResponse
    {
        $taxData = $this->getTaxSystemType();
        $categoryWiseTax = $taxData['categoryWiseTax'];
        $searchValue = $request['searchValue'];

        $categories = $this->categoryService->getList(
            position: CategoryService::POSITION_CATEGORY,
            searchValue: $searchValue,
            dataLimit: 'all',
            relations: ['translations', 'seo', ...$this->getTaxRelations(categoryWiseTax: $categoryWiseTax)],
            withCount: ['product'],
            sortBy: CategoryService::SORT_NEWEST_ID,
            withChildCounts: true,
        );

        $counts = $this->categoryService->getStatusCounts(
            position: CategoryService::POSITION_CATEGORY,
            searchValue: $searchValue,
        );

        return Excel::download(new CategoryListExport([
            'categories' => $categories,
            'title' => 'category',
            'listHeading' => translate('Category list'),
            'analyticsHeading' => translate('Category analytics'),
            'nameHeading' => translate('Category name'),
            'totalLabel' => translate('Total category'),
            'search' => $searchValue,
            'active' => $counts['active'],
            'inactive' => $counts['inactive'],
            'category_wise_tax' => $categoryWiseTax,
        ]), 'category-list.xlsx');
    }

    /** @return array<string, \Closure> */
    private function getTaxRelations(bool $categoryWiseTax): array
    {
        if (! $categoryWiseTax) {
            return [];
        }

        return [
            'taxVats' => fn ($query) => $query->with(['tax'])->whereHas('tax', fn ($query) => $query->where('is_active', 1)),
        ];
    }

    private function getUpdatedMessage(int $position): string
    {
        return match ($position) {
            CategoryService::POSITION_SUB_CATEGORY => translate('Sub category updated successfully'),
            CategoryService::POSITION_SUB_SUB_CATEGORY => translate('Sub sub category updated successfully'),
            default => translate(':item updated successfully', ['item' => translate('Category')]),
        };
    }
}
