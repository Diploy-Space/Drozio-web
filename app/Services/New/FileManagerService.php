<?php

namespace App\Services\New;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class FileManagerService
{
    public const STORAGE_PUBLIC = 'public';

    public const STORAGE_S3 = 's3';

    private const ROOT_FOLDER = 'public';

    private const RECENT_FILE_LIMIT = 10;

    private const SKIPPED_FILE_PREFIXES = ['__MACOSX/', '.DS_Store', 'Thumbs.db'];

    private const SIZE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    /** @return array{files: array<int, array<string, mixed>>, folders: array<int, array<string, mixed>>} */
    public function getAllFilesWithInfo(string $targetFolder, string $storage = self::STORAGE_PUBLIC): array
    {
        $path = $this->normalizePath($targetFolder);

        return [
            'files' => $this->filesWithInfo(path: $path, storage: $storage),
            'folders' => $this->foldersWithInfo(path: $path, storage: $storage),
        ];
    }

    public function getFolderTotalSize(string $targetFolder, string $storage = self::STORAGE_PUBLIC): string
    {
        $path = $this->normalizePath($targetFolder);
        $mapKey = 'fm_total_size_map_'.$storage;

        $map = Cache::get($mapKey, []);
        if (array_key_exists($path, $map)) {
            return $map[$path];
        }

        $disk = Storage::disk($storage);
        $totalBytes = 0;
        foreach ($disk->allFiles($path) as $file) {
            if ($this->shouldSkip(basename($file))) {
                continue;
            }
            $totalBytes += $disk->size($file);
        }

        $map[$path] = $this->formatSize(bytes: $totalBytes);
        Cache::put($mapKey, $map, CACHE_FOR_3_HOURS);

        return $map[$path];
    }

    /** @return array<int, array<string, mixed>> */
    public function getRecentFiles(string $storage = self::STORAGE_PUBLIC): array
    {
        $cacheKey = $storage === self::STORAGE_S3 ? 'cache_for_recent_file_list_s3' : 'cache_for_recent_file_list_public';

        return Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($storage): array {
            $disk = Storage::disk($storage);
            $paths = $disk->allFiles('/');

            usort($paths, fn ($first, $second) => $disk->lastModified($second) - $disk->lastModified($first));

            $files = [];
            foreach (array_slice($paths, 0, self::RECENT_FILE_LIMIT) as $file) {
                if ($this->shouldSkip(basename($file))) {
                    continue;
                }

                $files[] = $this->fileInfo(file: $file, storage: $storage) + [
                    'last_modified' => Carbon::parse(date('Y-m-d H:i:s', $disk->lastModified($file))),
                ];
            }

            return $files;
        });
    }

    /** @return array<int, array{name: string, path: string, decode_path: string}> */
    public function getFileManagerBreadcrumb(string $path): array
    {
        $segments = explode('/', trim($path, '/'));
        $segments = in_array(self::ROOT_FOLDER, $segments) ? $segments : [self::ROOT_FOLDER, ...$segments];

        $breadcrumb = [];
        $relativePath = '';

        foreach ($segments as $segment) {
            if ($segment === self::ROOT_FOLDER) {
                $decodePath = '/';
                $relativePath = '';
            } else {
                $relativePath .= ($relativePath === '' ? '' : '/').$segment;
                $decodePath = $relativePath;
            }

            if ($decodePath === '') {
                continue;
            }

            $breadcrumb[] = [
                'name' => $segment === self::ROOT_FOLDER
                    ? (getDefaultLanguage() === 'en' ? 'Public' : translate('Main'))
                    : ucwords(str_replace(['-', '_'], ' ', $segment)),
                'path' => base64_encode($decodePath),
                'decode_path' => $decodePath,
            ];
        }

        return $breadcrumb;
    }

    /** @param  array<int, UploadedFile>  $images */
    public function uploadImages(array $images, string $path, string $storage): void
    {
        foreach ($images as $image) {
            $name = Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$image->getClientOriginalExtension();

            if ($storage === self::STORAGE_S3) {
                Storage::disk(self::STORAGE_S3)->putFileAs($path, $image, $name);

                continue;
            }

            Storage::disk(self::STORAGE_PUBLIC)->put(
                $path === self::ROOT_FOLDER ? $name : $path.'/'.$name,
                file_get_contents($image),
            );
        }
    }

    public function uploadArchive(UploadedFile $file, string $path, string $storage): void
    {
        $storage === self::STORAGE_S3
            ? $this->extractArchiveToS3(file: $file, path: $path)
            : $this->extractArchiveToPublicDisk(file: $file, path: $path);
    }

    private function extractArchiveToS3(UploadedFile $file, string $path): void
    {
        $zip = new ZipArchive;

        if ($zip->open($file->path()) !== true) {
            return;
        }

        $disk = Storage::disk(self::STORAGE_S3);
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->statIndex($index)['name'];

            if (! $entryName || $this->shouldSkip($entryName)) {
                continue;
            }

            $storedName = Carbon::now()->toDateString().'-'.uniqid().'.'.pathinfo($entryName, PATHINFO_EXTENSION);
            $disk->put($path.'/'.$storedName, $zip->getFromIndex($index), self::STORAGE_PUBLIC);
        }

        $zip->close();
    }

    private function extractArchiveToPublicDisk(UploadedFile $file, string $path): void
    {
        $zip = new ZipArchive;

        if ($zip->open($file->path()) !== true) {
            return;
        }

        $temporaryPath = storage_path('app/temp-zip-'.Str::random(10));
        File::makeDirectory($temporaryPath);
        $zip->extractTo($temporaryPath);
        $zip->close();

        if (File::exists($temporaryPath.'/__MACOSX')) {
            File::deleteDirectory($temporaryPath.'/__MACOSX');
        }

        $this->copyAndTouch(
            source: $temporaryPath,
            destination: $path === self::ROOT_FOLDER ? storage_path('app/public') : storage_path('app/public/'.$path),
        );

        File::deleteDirectory($temporaryPath);
    }

    private function copyAndTouch(string $source, string $destination): void
    {
        foreach (File::allFiles($source) as $file) {
            $destinationPath = $destination.Str::after($file->getPathname(), $source);
            File::ensureDirectoryExists(dirname($destinationPath));
            File::copy($file->getPathname(), $destinationPath);
            touch($destinationPath);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function foldersWithInfo(string $path, string $storage): array
    {
        $disk = Storage::disk($storage);
        $folders = [];

        foreach ($disk->directories($path) as $folder) {
            $segments = explode('/', $folder);
            $folderName = end($segments);

            $folders[] = [
                'name' => str_ireplace(
                    ['\'', '"', ',', ';', '<', '>', '?', '“', '”', '_', '-'],
                    ' ',
                    preg_replace('/\s\s+/', ' ', $folderName),
                ),
                'path' => $folder,
                'encodePath' => base64_encode($folder),
                'lastPath' => str_replace($folderName, '', $folder),
                'type' => 'Folder',
                'totalFiles' => count($this->visibleFiles(path: $folder, storage: $storage)),
                'size' => $this->getFolderTotalSize(targetFolder: $folder, storage: $storage),
                'totalFolders' => count($disk->directories($folder)),
            ];
        }

        usort($folders, fn ($first, $second) => strcmp($first['name'], $second['name']));

        return $folders;
    }

    /** @return array<int, array<string, mixed>> */
    private function filesWithInfo(string $path, string $storage): array
    {
        $files = [];

        foreach ($this->visibleFiles(path: $path, storage: $storage) as $file) {
            $files[] = $this->fileInfo(file: $file, storage: $storage);
        }

        usort($files, fn ($first, $second) => strcmp($first['name'], $second['name']));

        return $files;
    }

    /** @return array<int, string> */
    private function visibleFiles(string $path, string $storage): array
    {
        return array_filter(
            Storage::disk($storage)->files($path),
            fn (string $file): bool => basename($file) !== '' && ! $this->shouldSkip(basename($file)),
        );
    }

    /** @return array<string, mixed> */
    private function fileInfo(string $file, string $storage): array
    {
        $disk = Storage::disk($storage);
        $segments = explode('/', $file);
        $sizeInBytes = $disk->size($file);

        return [
            'name' => end($segments),
            'short_name' => $this->minifyFileName(name: end($segments)),
            'driver' => $storage,
            'path' => $file,
            'db_path' => $file,
            'encodePath' => Crypt::encryptString($file),
            'type' => explode('/', $disk->mimeType($file))[0],
            'size' => $this->formatSize(bytes: $sizeInBytes),
            'sizeInInteger' => $sizeInBytes,
            'extension' => pathinfo($file, PATHINFO_EXTENSION),
        ];
    }

    private function minifyFileName(string $name, int $prefixLength = 15, int $suffixLength = 8, string $ellipsis = '.....'): string
    {
        if (strlen($name) <= $prefixLength + $suffixLength) {
            return $name;
        }

        return substr($name, 0, $prefixLength).$ellipsis.substr($name, -$suffixLength);
    }

    private function formatSize(float|int $bytes): string
    {
        $unitIndex = 0;
        while ($bytes >= 1024 && $unitIndex < count(self::SIZE_UNITS) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2).' '.self::SIZE_UNITS[$unitIndex];
    }

    private function shouldSkip(string $fileName): bool
    {
        foreach (self::SKIPPED_FILE_PREFIXES as $skipped) {
            if (str_starts_with($fileName, $skipped)) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(string $targetFolder): string
    {
        return $targetFolder === self::ROOT_FOLDER ? '/' : $targetFolder;
    }
}
