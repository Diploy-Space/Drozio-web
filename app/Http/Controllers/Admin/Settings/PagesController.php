<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Website\PageAddRequest;
use App\Http\Requests\Admin\Setting\Website\PageUpdateRequest;
use App\Services\New\BusinessPageService;
use App\Traits\UpdateClass;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PagesController extends BaseController
{
    use UpdateClass;

    public function __construct(
        private readonly BusinessPageService $businessPageService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|RedirectResponse
    {
        $this->deleteDuplicateDefaultPages();

        $businessPages = $this->pageList(searchValue: $request['search'] ?? null);

        if ($businessPages->count() <= 0) {
            $this->addOrUpdateBusinessPagesData();
            $businessPages = $this->pageList();
        }

        return view('admin-views.pages-and-media.list', compact('businessPages'));
    }

    public function getAddView(): View|RedirectResponse
    {
        if ($this->businessPageService->getList(dataLimit: 'all')->count() >= BusinessPageService::MAX_PAGES) {
            ToastMagic::warning(translate('Cannot add more than 10 custom business pages'));

            return back();
        }

        return view('admin-views.pages-and-media.add');
    }

    public function getAdd(PageAddRequest $request): RedirectResponse
    {
        $this->businessPageService->create(data: $request->all(), banner: $request->file('banner'));
        ToastMagic::success(translate(':item added successfully', ['item' => translate('Page')]));

        return redirect()->route('admin.settings.pages-and-media.list');
    }

    public function getUpdateView(Request $request): View
    {
        return view('admin-views.pages-and-media.update', [
            'businessPage' => $this->businessPageService->findBy(params: ['slug' => $request['slug']], relations: ['banner']),
        ]);
    }

    public function getUpdate(PageUpdateRequest $request): RedirectResponse
    {
        $this->businessPageService->update(
            id: $request['id'],
            data: $request->all(),
            banner: $request->file('banner'),
        );
        ToastMagic::success(translate('Updated successfully'));

        return redirect()->route('admin.settings.pages-and-media.list');
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->businessPageService->updateStatus(id: $request['id'], status: (bool) ($request['status'] ?? 0));

        return response()->json([
            'status' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Status')]),
        ]);
    }

    public function getDeleteImage(Request $request): RedirectResponse
    {
        $this->businessPageService->deleteBanner(id: $request['id'])
            ? ToastMagic::success(translate('Banner image deleted successfully'))
            : ToastMagic::error(translate('Not found'));

        return redirect()->route('admin.settings.pages-and-media.list');
    }

    public function getDelete(Request $request): RedirectResponse
    {
        $businessPage = $this->businessPageService->findBy(params: ['slug' => $request['slug']], relations: []);

        $businessPage !== null && $this->businessPageService->delete(id: $businessPage->id)
            ? ToastMagic::success(translate('Deleted successfully'))
            : ToastMagic::error(translate('Page not found'));

        return redirect()->route('admin.settings.pages-and-media.list');
    }

    private function deleteDuplicateDefaultPages(): void
    {
        $duplicateIds = $this->businessPageService
            ->getList(dataLimit: 'all', filters: ['slug' => BusinessPageService::DEFAULT_PAGE_SLUGS])
            ->sortBy('id')
            ->groupBy('slug')
            ->flatMap(fn (Collection $pages) => $pages->skip(1)->pluck('id'));

        if ($duplicateIds->isEmpty()) {
            return;
        }

        foreach ($duplicateIds as $id) {
            $this->businessPageService->delete(id: $id);
        }

        cacheRemoveByType(type: 'business_pages');
    }

    private function pageList(?string $searchValue = null): Collection
    {
        return $this->businessPageService->getList(
            searchValue: $searchValue,
            dataLimit: 'all',
            relations: ['banner'],
            sortBy: BusinessPageService::SORT_DEFAULT_FIRST,
        );
    }
}
