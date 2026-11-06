<?php

namespace App\Http\Controllers\Admin\Product;

use App\Exports\CategoryListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\SubCategory\AddRequest;
use App\Http\Requests\Admin\SubCategory\UpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\CategoryService;
use App\Services\New\ProductRetailService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubCategoryController extends BaseController
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly ProductRetailService $productService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => ['pagination_limit', 'pnc_language']])
            ->pluck('value', 'type');

        $categories = $this->categoryService->getList(
            position: CategoryService::POSITION_SUB_CATEGORY,
            searchValue: $request['searchValue'],
            dataLimit: $settings->get('pagination_limit'),
            relations: ['translations', 'seo', 'parent'],
            withCount: ['subCategoryProduct as product_count', 'childes as sub_sub_category_count'],
        )->appends($request->only(['searchValue']));

        $languages = json_decode((string) $settings->get('pnc_language'), true) ?? null;

        return view('admin-views.category.sub-category-view', [
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
            position: CategoryService::POSITION_SUB_CATEGORY,
            image: $request->file('image'),
            metaImage: $request->file('meta_image'),
        );

        $response === null
            ? ToastMagic::error(translate('Not found'))
            : ToastMagic::success(translate('Sub category added successfully'));

        return back();
    }

    public function update(UpdateRequest $request, string|int $id): JsonResponse
    {
        $parentId = $request['parent_id'] ? (int) $request['parent_id'] : null;

        $response = DB::transaction(function () use ($request, $id, $parentId): ?Model {
            $isMovingToNewParent = $this->categoryService->isSubCategoryMovingToNewParent(id: $id, parentId: $parentId);

            $category = $this->categoryService->update(
                id: $id,
                data: $request->all(),
                image: $request->file('image'),
                metaImage: $request->file('meta_image'),
            );

            if ($category !== null && $isMovingToNewParent) {
                $this->productService->updateParentCategory(
                    subCategoryId: $id,
                    parentCategoryId: $parentId,
                );
            }

            return $category;
        });

        $response === null
            ? ToastMagic::error(translate('Not found'))
            : ToastMagic::success(translate('Sub category updated successfully'));

        return response()->json();
    }

    public function delete(Request $request): JsonResponse
    {
        $this->categoryService->delete(id: $request['id']);

        return response()->json(['message' => translate('Deleted successfully')]);
    }

    public function getExportList(Request $request): BinaryFileResponse
    {
        $searchValue = $request['searchValue'];

        $subCategories = $this->categoryService->getList(
            position: CategoryService::POSITION_SUB_CATEGORY,
            searchValue: $searchValue,
            dataLimit: 'all',
            relations: ['translations', 'seo', 'parent'],
            withCount: ['subCategoryProduct as product_count', 'childes as sub_sub_category_count'],
        );

        $counts = $this->categoryService->getStatusCounts(
            position: CategoryService::POSITION_SUB_CATEGORY,
            searchValue: $searchValue,
        );

        return Excel::download(new CategoryListExport([
            'categories' => $subCategories,
            'title' => 'sub_category',
            'listHeading' => translate('Sub category list'),
            'analyticsHeading' => translate('Sub category analytics'),
            'nameHeading' => translate('Sub category name'),
            'totalLabel' => translate('Total sub category'),
            'search' => $searchValue,
            'active' => $counts['active'],
            'inactive' => $counts['inactive'],
        ]), 'sub-category-list.xlsx');
    }
}
