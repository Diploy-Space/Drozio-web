<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Category\ChildrenRequest;
use App\Http\Requests\Customer\Category\ListRequest;
use App\Services\New\CategoryService;
use App\Services\New\RobotsMetaContentService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    private const UNSUPPORTED_THEMES = ['theme_fashion', 'theme_aster'];

    private const LIST_VIEW = 'web-views.products.categories';

    private const CHILD_LIST_PARTIAL = 'web-views.partials._category-list-ajax';

    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly RobotsMetaContentService $robotsMetaContentService,
    ) {}

    public function index(ListRequest $request): View|RedirectResponse
    {
        if (in_array(theme_root_path(), self::UNSUPPORTED_THEMES, true)) {
            ToastMagic::warning(translate('Not found'));

            return back();
        }

        return view(self::LIST_VIEW, [
            'categories' => $this->categoryService->getList(
                dataLimit: 'all',
                filters: [CategoryService::FILTER_NAME_LIKE => $request['search']],
                sortBy: CategoryService::SORT_LIST_PRIORITY,
            ),
            'robotsMetaContentData' => $this->robotsMetaContentService->findByPageNameOrDefault(
                pageName: RobotsMetaContentService::PAGE_NAME_CATEGORIES,
            ),
        ]);
    }

    public function getChildren(ChildrenRequest $request): JsonResponse
    {
        abort_if(in_array(theme_root_path(), self::UNSUPPORTED_THEMES, true), 404);

        return response()->json([
            'view' => view(self::CHILD_LIST_PARTIAL, [
                'category' => $this->categoryService->getWithDescendants(id: $request['id']),
            ])->render(),
        ]);
    }
}
