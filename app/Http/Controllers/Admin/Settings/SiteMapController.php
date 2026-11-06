<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\WebConfigKey;
use App\Http\Controllers\BaseController;
use App\Jobs\GenerateSiteMapJob;
use App\Services\New\BusinessSettingService;
use App\Services\New\SiteMapService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SiteMapController extends BaseController
{
    private const DEFAULT_DATA_LIMIT = 25;

    private const GENERATION_TIME_LIMIT = 600;

    public function __construct(
        private readonly SiteMapService $siteMapService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.seo-settings.sitemap', [
            'siteMapList' => $this->paginateSitemapFiles(files: $this->siteMapService->getSitemapFiles()),
        ]);
    }

    public function getGenerate(): RedirectResponse
    {
        return $this->runGeneration(publish: false);
    }

    public function getGenerateAndUpload(): RedirectResponse
    {
        return $this->runGeneration(publish: true);
    }

    public function getDownload(Request $request): BinaryFileResponse|RedirectResponse
    {
        $filePath = $request['path']
            ? $this->siteMapService->findSitemapPath(fileName: base64_decode($request['path']))
            : null;

        return $filePath === null
            ? redirect()->route('admin.settings.seo-settings.sitemap')
            : response()->download($filePath);
    }

    public function getUpload(Request $request): RedirectResponse
    {
        $file = $request->file('xml_file');

        if ($file === null || $file->getClientOriginalExtension() != 'xml') {
            ToastMagic::error(translate('Please upload a xml file'));

            return redirect()->route('admin.settings.seo-settings.sitemap');
        }

        $this->siteMapService->storeUploadedFile(file: $file);
        ToastMagic::success(translate('Uploaded successfully'));

        return redirect()->route('admin.settings.seo-settings.sitemap');
    }

    public function getDelete(Request $request): RedirectResponse
    {
        if ($request['path'] && $this->siteMapService->delete(fileName: base64_decode($request['path']))) {
            ToastMagic::success(translate('Deleted successfully'));
        }

        return redirect()->route('admin.settings.seo-settings.sitemap');
    }

    private function runGeneration(bool $publish): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('You cannot update this on demo mode'));

            return redirect()->back();
        }

        $this->allowLongGeneration();
        GenerateSiteMapJob::dispatchSync(publish: $publish);
        ToastMagic::success(translate('Sitemap generated successfully'));

        return redirect()->route('admin.settings.seo-settings.sitemap');
    }

    private function allowLongGeneration(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(self::GENERATION_TIME_LIMIT);
        }
    }

    /** @param  array<int, array<string, mixed>>  $files */
    private function paginateSitemapFiles(array $files): LengthAwarePaginator
    {
        $collection = new Collection($files);
        $dataLimit = $this->businessSettingService->findBy(params: ['type' => WebConfigKey::PAGINATION_LIMIT])?->value ?: self::DEFAULT_DATA_LIMIT;
        $currentPage = Paginator::resolveCurrentPage('page');

        return new LengthAwarePaginator(
            items: $collection->forPage($currentPage, $dataLimit),
            total: $collection->count(),
            perPage: $dataLimit,
            currentPage: $currentPage,
            options: ['path' => Paginator::resolveCurrentPath()],
        );
    }
}
