<?php

namespace Modules\Blog\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Blog\app\Http\Requests\Admin\Blog\AddRequest;
use Modules\Blog\app\Http\Requests\Admin\Blog\UpdateRequest;
use Modules\Blog\app\Services\New\BlogCategoryService;
use Modules\Blog\app\Services\New\BlogService;

class BlogController extends Controller
{
    private const SETTING_PAGINATION_LIMIT = 'pagination_limit';

    private const SETTING_LANGUAGE = 'pnc_language';

    private const SETTING_INTRO_TITLE = 'blog_feature_title';

    private const SETTING_INTRO_SUB_TITLE = 'blog_feature_sub_title';

    private const SETTING_ACTIVE_STATUS = 'blog_feature_active_status';

    public function __construct(
        private readonly BlogService $blogService,
        private readonly BlogCategoryService $blogCategoryService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $settings = $this->settings([
            self::SETTING_PAGINATION_LIMIT,
            self::SETTING_LANGUAGE,
            self::SETTING_INTRO_TITLE,
            self::SETTING_INTRO_SUB_TITLE,
        ]);
        $languages = $this->languages($settings);

        return view('blog::admin-views.blog.index', [
            'blogs' => $this->blogService->getList(
                searchValue: $request['searchValue'],
                dataLimit: $this->paginationLimit($settings),
                relations: ['category'],
                filters: $this->listFilters($request),
            ),
            'categories' => $this->blogCategoryService->getList(dataLimit: 'all', relations: ['translations']),
            'titleData' => $this->decodedSetting($settings, self::SETTING_INTRO_TITLE),
            'subTitleData' => $this->decodedSetting($settings, self::SETTING_INTRO_SUB_TITLE),
            'languages' => $languages,
            'defaultLanguage' => $languages[0] ?? null,
            'blogCounts' => $this->blogCounts(),
        ]);
    }

    /** @return array{total: int, published: int, draft: int} */
    private function blogCounts(): array
    {
        return [
            'total' => $this->blogService->count(),
            'published' => $this->blogService->count(filters: ['is_published' => 1]),
            'draft' => $this->blogService->count(filters: ['is_draft' => 1]),
        ];
    }

    public function getConfigurationView(): View
    {
        $settings = $this->settings([
            self::SETTING_LANGUAGE,
            self::SETTING_INTRO_TITLE,
            self::SETTING_INTRO_SUB_TITLE,
        ]);
        $languages = $this->languages($settings);

        return view('blog::admin-views.blog.configuration', [
            'titleData' => $this->decodedSetting($settings, self::SETTING_INTRO_TITLE),
            'subTitleData' => $this->decodedSetting($settings, self::SETTING_INTRO_SUB_TITLE),
            'languages' => $languages,
            'defaultLanguage' => $languages[0] ?? null,
        ]);
    }

    public function updateIntro(Request $request): RedirectResponse
    {
        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::SETTING_INTRO_TITLE],
            data: ['value' => json_encode($request['title'] ?? [])],
        );
        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::SETTING_INTRO_SUB_TITLE],
            data: ['value' => json_encode($request['sub_title'] ?? [])],
        );

        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    public function getAddView(): View
    {
        $settings = $this->settings([self::SETTING_PAGINATION_LIMIT, self::SETTING_LANGUAGE]);
        $languages = $this->languages($settings);

        return view('blog::admin-views.blog.create', [
            'categories' => $this->categories($settings),
            'languages' => $languages,
            'defaultLanguage' => $languages[0] ?? null,
        ]);
    }

    public function addBlog(AddRequest $request): JsonResponse
    {
        $this->blogService->create(
            data: $request->all(),
            image: $request->file('image'),
            metaImage: $request->file('meta_image'),
        );

        return $this->savedResponse(isDraft: (bool) $request['is_draft']);
    }

    public function getUpdateView(Request $request): View|RedirectResponse
    {
        $blog = $this->blogService->find(id: $request['id']);

        if ($blog === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.settings.content.blog.view');
        }

        $settings = $this->settings([self::SETTING_PAGINATION_LIMIT, self::SETTING_LANGUAGE]);
        $languages = $this->languages($settings);

        return view('blog::admin-views.blog.edit', [
            'blog' => $blog,
            'categories' => $this->categories($settings),
            'languages' => $languages,
            'defaultLanguage' => $languages[0] ?? null,
        ]);
    }

    public function draftEdit(mixed $blogId): View|RedirectResponse
    {
        $blog = $this->blogService->find(id: $blogId, relations: ['translations']);

        if ($blog === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.settings.content.blog.view');
        }

        $settings = $this->settings([self::SETTING_PAGINATION_LIMIT, self::SETTING_LANGUAGE]);
        $languages = $this->languages($settings);

        return view('blog::admin-views.blog.draft-edit', [
            'blog' => $blog,
            'categories' => $this->categories($settings),
            'languages' => $languages,
            'defaultLanguage' => $languages[0] ?? null,
        ]);
    }

    public function update(UpdateRequest $request): JsonResponse
    {
        if ($request['clear_draft'] == 1) {
            return $this->blogService->clearDraft(id: $request['id'])
                ? $this->redirectResponse(message: translate('Blog draft cleared successfully'))
                : $this->notFoundResponse();
        }

        $blog = $this->blogService->update(
            id: $request['id'],
            data: $request->all(),
            image: $request->file('image'),
            metaImage: $request->file('meta_image'),
        );

        return $blog === null
            ? $this->notFoundResponse()
            : $this->savedResponse(isDraft: (bool) $request['is_draft']);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::SETTING_ACTIVE_STATUS],
            data: ['value' => $request['status'] ?? 0],
        );

        return response()->json([
            'status' => true,
            'message' => translate(':item updated successfully', ['item' => translate('Status')]),
        ]);
    }

    public function updateBlogStatus(Request $request, mixed $id): JsonResponse
    {
        $this->blogService->updateStatus(id: $id, status: (bool) ($request['status'] ?? 0));

        return response()->json([
            'message' => translate(':item updated successfully', ['item' => translate('Status')]),
        ]);
    }

    public function delete(Request $request): JsonResponse|RedirectResponse
    {
        $isDeleted = $this->blogService->delete(id: $request['id']);
        $message = $isDeleted ? translate('Blog deleted successfully') : translate('Not found');

        if ($request->ajax()) {
            return response()->json(['status' => $isDeleted, 'message' => $message]);
        }

        $isDeleted ? ToastMagic::success($message) : ToastMagic::error($message);

        return back();
    }

    /**
     * @param  array<int, string>  $types
     * @return Collection<string, mixed>
     */
    private function settings(array $types): Collection
    {
        return $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => $types])
            ->pluck('value', 'type');
    }

    /** @return array<int, string> */
    private function languages(Collection $settings): array
    {
        return json_decode((string) $settings->get(self::SETTING_LANGUAGE), true) ?? [];
    }

    /** @return array<string, string> */
    private function decodedSetting(Collection $settings, string $type): array
    {
        return json_decode((string) $settings->get($type), true) ?? [];
    }

    private function categories(Collection $settings): LengthAwarePaginator
    {
        return $this->blogCategoryService->getList(
            dataLimit: $this->paginationLimit($settings),
            relations: ['translations'],
            withCount: ['blogs'],
        );
    }

    private function paginationLimit(Collection $settings): int
    {
        return (int) ($settings->get(self::SETTING_PAGINATION_LIMIT) ?: DEFAULT_DATA_LIMIT);
    }

    /** @return array<string, mixed> */
    private function listFilters(Request $request): array
    {
        $categoryId = $request['category_id'];

        return array_filter([
            'category_id' => $categoryId === 'all' ? null : $categoryId,
            BlogService::FILTER_PUBLISH_DATE => $request['publish_date'],
        ], static fn ($value) => ! empty($value));
    }

    private function savedResponse(bool $isDraft): JsonResponse
    {
        return $this->redirectResponse(message: $isDraft
            ? translate('Blog drafted successfully')
            : translate(':item published successfully', ['item' => translate('Blog')]));
    }

    private function redirectResponse(string $message): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'redirect' => route('admin.settings.content.blog.view'),
            'message' => $message,
        ]);
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json(['status' => 0, 'message' => translate('Not found')]);
    }
}
