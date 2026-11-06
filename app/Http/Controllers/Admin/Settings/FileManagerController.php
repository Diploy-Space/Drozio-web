<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Storage\FileUploadRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\FileManagerService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileManagerController extends BaseController
{
    private const STORAGE_CONNECTION_TYPE_KEY = 'storage_connection_type';

    private const ROOT_FOLDER = 'public';

    private const ENCODED_ROOT_FOLDER = 'cHVibGlj';

    private const ALLOWED_STORAGES = [FileManagerService::STORAGE_PUBLIC, FileManagerService::STORAGE_S3];

    private const DOWNLOAD_DISKS = ['local', 'public', 's3'];

    private const ITEMS_PER_PAGE = 30;

    public function __construct(
        private readonly FileManagerService $fileManagerService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $storage = $this->resolveStorage(storage: $request['storage'] ?? null);
        $targetFolder = $request['targetFolder'] ?? self::ENCODED_ROOT_FOLDER;
        $decodedPath = $this->decodePath(encodedPath: $targetFolder);

        $currentFolder = explode('/', $decodedPath);
        $allFiles = $this->fileManagerService->getAllFilesWithInfo(targetFolder: $decodedPath, storage: $storage);

        return view('admin-views.file-manager.index', [
            'storage' => $storage,
            'storageConnectionType' => $this->businessSettingService->findBy(params: ['type' => self::STORAGE_CONNECTION_TYPE_KEY])?->value,
            'currentFolderSize' => $this->fileManagerService->getFolderTotalSize(targetFolder: $decodedPath, storage: $storage),
            'allItemList' => $this->paginateItems(
                items: $this->searchItems(
                    items: collect([...$allFiles['folders'], ...$allFiles['files']]),
                    searchValue: $request['search'] ?? null,
                ),
            ),
            'targetFolder' => $targetFolder,
            'currentFolder' => $currentFolder,
            'previousFolder' => count($currentFolder) > 1
                ? str_replace('/'.end($currentFolder), '', $decodedPath)
                : self::ROOT_FOLDER,
            'recentFiles' => $this->fileManagerService->getRecentFiles(storage: $storage),
            'breadcrumb' => $this->fileManagerService->getFileManagerBreadcrumb(path: $decodedPath),
        ]);
    }

    public function upload(FileUploadRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        $storage = $this->resolveStorage(storage: $request['storage'] ?? null);
        $hasImages = $request->hasFile('images');

        if ($hasImages) {
            $this->fileManagerService->uploadImages(
                images: $request->file('images'),
                path: $request['path'],
                storage: $storage,
            );
        }

        if ($request->hasFile('file')) {
            $this->fileManagerService->uploadArchive(
                file: $request->file('file'),
                path: $request['path'],
                storage: $storage,
            );
        }

        cacheRemoveByType(type: 'file_manager');

        ToastMagic::success(translate(':item uploaded successfully', [
            'item' => $hasImages ? translate('Image') : translate('File'),
        ]));

        return back();
    }

    public function download(Request $request, $fileName): StreamedResponse
    {
        $disk = $request['storage'] == self::ROOT_FOLDER ? 'local' : $request['storage'];
        $path = base64_decode($fileName, true);

        abort_if(
            $path === false
            || ! in_array($disk, self::DOWNLOAD_DISKS, true)
            || $this->hasTraversal(path: $path),
            404
        );

        $storage = Storage::disk($disk);
        abort_unless($storage->exists($path), 404);

        return $storage->download($path);
    }

    private function resolveStorage(?string $storage): string
    {
        return in_array($storage, self::ALLOWED_STORAGES, true) ? $storage : FileManagerService::STORAGE_PUBLIC;
    }

    private function decodePath(string $encodedPath): string
    {
        $decodedPath = base64_decode($encodedPath, true);

        abort_if($decodedPath === false || $this->hasTraversal(path: $decodedPath), 404);

        return $decodedPath;
    }

    private function hasTraversal(string $path): bool
    {
        return str_contains($path, "\0")
            || in_array('..', explode('/', str_replace('\\', '/', $path)), true);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function searchItems(Collection $items, ?string $searchValue): Collection
    {
        if (empty($searchValue)) {
            return $items;
        }

        $searchValue = strtolower($searchValue);

        return $items->filter(fn (array $item): bool => str_contains(strtolower($item['name']), $searchValue));
    }

    /** @param  Collection<int, array<string, mixed>>  $items */
    private function paginateItems(Collection $items): LengthAwarePaginator
    {
        $page = (int) request('page', 1);

        return new LengthAwarePaginator(
            $items->slice(($page - 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE)->values(),
            $items->count(),
            self::ITEMS_PER_PAGE,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }
}
