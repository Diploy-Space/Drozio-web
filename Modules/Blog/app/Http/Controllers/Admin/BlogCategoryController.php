<?php

namespace Modules\Blog\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Blog\app\Http\Requests\Admin\Category\AddRequest;
use Modules\Blog\app\Http\Requests\Admin\Category\UpdateRequest;
use Modules\Blog\app\Services\New\BlogCategoryService;

class BlogCategoryController extends Controller
{
    public function __construct(
        private readonly BlogCategoryService $blogCategoryService,
    ) {}

    public function add(AddRequest $request): JsonResponse
    {
        $this->blogCategoryService->create(data: $request->all());

        return $this->listResponse(
            request: $request,
            message: translate(':item added successfully', ['item' => translate('Category')]),
        );
    }

    public function update(UpdateRequest $request): JsonResponse
    {
        $this->blogCategoryService->update(id: $request['id'], data: $request->all());

        return $this->listResponse(
            request: $request,
            message: translate(':item updated successfully', ['item' => translate('Category')]),
        );
    }

    public function getCategoryInfo(Request $request): JsonResponse
    {
        $category = $this->blogCategoryService->find(id: $request['id']);

        if ($category === null) {
            return response()->json(['message' => translate('Not found')], 404);
        }

        return response()->json([
            'data' => $category,
            'lang_data' => $this->blogCategoryService->getLanguageValues(category: $category),
        ]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->blogCategoryService->updateStatus(id: $request['category_id'], status: (bool) ($request['status'] ?? 0));

        return $this->listResponse(
            request: $request,
            message: translate(':item updated successfully', ['item' => translate('Status')]),
        );
    }

    public function deleteCategory(Request $request): JsonResponse
    {
        $this->blogCategoryService->delete(id: $request['category_id']);

        return $this->listResponse(request: $request, message: translate('Category deleted successfully'));
    }

    public function search(Request $request): JsonResponse
    {
        return $this->listResponse(request: $request);
    }

    public function getList(Request $request): JsonResponse
    {
        return response()->json([
            'select_tag' => view('blog::admin-views.blog.category.partials._options', [
                'options' => $this->blogCategoryService->getOptions(),
            ])->render(),
        ]);
    }

    private function listResponse(Request $request, ?string $message = null): JsonResponse
    {
        $categories = $this->blogCategoryService->getList(
            searchValue: $request['searchValue'],
            dataLimit: getWebConfig(name: 'pagination_limit'),
            page: $request['page'],
            relations: ['translations'],
            withCount: ['blogs'],
        )->appends($request->query());

        return response()->json(array_filter([
            'message' => $message,
            'html' => view('blog::admin-views.blog.category.partials.table-rows', ['categories' => $categories])->render(),
            'count' => $categories->total(),
        ], static fn ($value) => $value !== null));
    }
}
