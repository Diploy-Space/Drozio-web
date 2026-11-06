<?php

namespace App\Http\Controllers\Admin\Product;

use App\Exports\CategoryListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Category\UpdateRequest;
use App\Http\Requests\Admin\SubCategory\AddRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\CategoryService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubSubCategoryController extends BaseController
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => ['pagination_limit', 'pnc_language']])
            ->pluck('value', 'type');

        $categories = $this->categoryService->getList(
            position: CategoryService::POSITION_SUB_SUB_CATEGORY,
            searchValue: $request['searchValue'],
            dataLimit: $settings->get('pagination_limit'),
            relations: ['translations', 'seo', 'parent.parent'],
            withCount: ['subSubCategoryProduct as product_count'],
        )->appends($request->only(['searchValue']));

        $languages = json_decode((string) $settings->get('pnc_language'), true) ?? null;

        return view('admin-views.category.sub-sub-category-view', [
            'categories' => $categories,
            'categoriesWithTrans' => $categories,
            'parentCategories' => $this->categoryService->getListForParentSelect(),
            'languages' => $languages,
            'defaultLanguage' => $languages[0],
        ]);
    }

    public function add(AddRequest $request): RedirectResponse
    {
        $response = $this->categoryService->create(
            data: $request->all(),
            position: CategoryService::POSITION_SUB_SUB_CATEGORY,
            metaImage: $request->file('meta_image'),
        );

        $response === null
            ? ToastMagic::error(translate('Not found'))
            : ToastMagic::success(translate('Sub sub category added successfully'));

        return back();
    }

    public function update(UpdateRequest $request, string|int $id): JsonResponse
    {
        $response = $this->categoryService->update(
            id: $id,
            data: $request->all(),
            metaImage: $request->file('meta_image'),
        );

        $response === null
            ? ToastMagic::error(translate('Not found'))
            : ToastMagic::success(translate('Sub sub category updated successfully'));

        return response()->json();
    }

    public function delete(Request $request): JsonResponse
    {
        $this->categoryService->delete(id: $request['id']);

        return response()->json(['message' => translate('Deleted successfully')]);
    }

    public function getSubCategory(Request $request): JsonResponse
    {
        $subCategories = $this->categoryService->getChildList(parentId: $request['id']);

        return response()->json([
            'html' => view('admin-views.category.offcanvas._sub-category-options', [
                'subCategories' => $subCategories,
            ])->render(),
        ]);
    }

    public function getExportList(Request $request): BinaryFileResponse
    {
        $searchValue = $request['searchValue'];

        $subSubCategories = $this->categoryService->getList(
            position: CategoryService::POSITION_SUB_SUB_CATEGORY,
            searchValue: $searchValue,
            dataLimit: 'all',
            sortBy: CategoryService::SORT_NEWEST_ID,
            relations: ['translations', 'seo', 'parent.parent'],
            withCount: ['subSubCategoryProduct as product_count'],
        );

        $counts = $this->categoryService->getStatusCounts(
            position: CategoryService::POSITION_SUB_SUB_CATEGORY,
            searchValue: $searchValue,
        );

        return Excel::download(new CategoryListExport([
            'categories' => $subSubCategories,
            'title' => 'sub_sub_category',
            'listHeading' => translate('Sub sub category list'),
            'analyticsHeading' => translate('Sub sub category analytics'),
            'nameHeading' => translate('Sub sub category name'),
            'totalLabel' => translate('Total sub sub category'),
            'search' => $searchValue,
            'active' => $counts['active'],
            'inactive' => $counts['inactive'],
        ]), 'sub-sub-category-list.xlsx');
    }
}
