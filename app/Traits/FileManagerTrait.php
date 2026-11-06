<?php

namespace App\Traits;

use App\Utils\FileExistenceCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager as InterventionImageManager;

trait FileManagerTrait
{
    /**
     * upload method working for image
     */
    protected function upload(string $dir, string $format, $image = null): string
    {
        $storage = config('filesystems.disks.default') ?? 'public';
        Cache::forget('cache_all_files_for_public_storage');
        if (! is_null($image)) {
            if (! $this->checkFileExists($dir)['status']) {
                Storage::disk($storage)->makeDirectory($dir);
            }

            $isOriginalImage = in_array($image->getClientOriginalExtension(), ['gif', 'svg']);
            if ($isOriginalImage) {
                $imageName = Carbon::now()->toDateString().'-'.uniqid().'.'.$image->getClientOriginalExtension();
                Storage::disk($storage)->put($dir.$imageName, file_get_contents($image));
            } else {
                if (in_array(request()->ip(), ['127.0.0.1', '::1']) && ! (imagetypes() & IMG_WEBP) || config('app.debug') && ! (imagetypes() & IMG_WEBP)) {
                    $format = 'png';
                }
                $imageWebp = InterventionImageManager::gd()->read($image)->encodeByExtension($format);
                $imageName = Carbon::now()->toDateString().'-'.uniqid().'.'.$format;
                Storage::disk($storage)->put($dir.$imageName, (string) $imageWebp);
            }
        } else {
            $imageName = 'def.png';
        }

        if ($storage === 'public' && $imageName !== 'def.png') {
            FileExistenceCache::remember(disk: $storage, path: $dir.$imageName);
        }

        cacheRemoveByType(type: 'file_manager');

        return $imageName;
    }

    public function fileUpload(string $dir, string $format, $file = null): string
    {
        $storage = config('filesystems.disks.default') ?? 'public';
        Cache::forget('cache_all_files_for_public_storage');

        if (! is_null($file)) {
            $fileName = Carbon::now()->toDateString().'-'.uniqid().'.'.$format;
            if (! $this->checkFileExists($dir)['status']) {
                Storage::disk($storage)->makeDirectory($dir);
            }
            if ($file) {
                Storage::disk($storage)->put($dir.$fileName, file_get_contents($file));
                if ($storage === 'public') {
                    FileExistenceCache::remember(disk: $storage, path: $dir.$fileName);
                }
            }
        } else {
            $fileName = 'def.png';
        }

        return $fileName;
    }

    /**
     * @param  string  $fileType  image/file
     */
    public function update(string $dir, $oldImage, string $format, $image, string $fileType = 'image'): string
    {
        $fileInfo = $this->checkFileExists(filePath: $dir.$oldImage);
        if ($fileInfo['status']) {
            Storage::disk($fileInfo['disk'])->delete($dir.$oldImage);
            FileExistenceCache::forget(disk: $fileInfo['disk'], path: $dir.$oldImage);
        }

        return $fileType == 'file' ? $this->fileUpload($dir, $format, $image) : $this->upload($dir, $format, $image);
    }

    protected function duplicateStoredFile(string $dir, string $fileName): ?string
    {
        $sourceInfo = $this->checkFileExists(filePath: $dir.$fileName);

        if (! $sourceInfo['status']) {
            return null;
        }

        $storage = config('filesystems.disks.default') ?? 'public';
        $extension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'png';
        $duplicateName = Carbon::now()->toDateString().'-'.uniqid().'.'.$extension;

        if (! $this->checkFileExists(filePath: $dir)['status']) {
            Storage::disk($storage)->makeDirectory($dir);
        }

        Storage::disk($storage)->put($dir.$duplicateName, Storage::disk($sourceInfo['disk'])->get($dir.$fileName));

        if ($storage === 'public') {
            FileExistenceCache::remember(disk: $storage, path: $dir.$duplicateName);
        }

        Cache::forget('cache_all_files_for_public_storage');
        cacheRemoveByType(type: 'file_manager');

        return $duplicateName;
    }

    protected function delete(string $filePath): array
    {
        $fileInfo = $this->checkFileExists(filePath: $filePath);
        if ($fileInfo['status']) {
            Storage::disk($fileInfo['disk'])->delete($filePath);
            FileExistenceCache::forget(disk: $fileInfo['disk'], path: $filePath);
        }
        cacheRemoveByType(type: 'file_manager');
        Cache::forget('cache_all_files_for_public_storage');

        return [
            'success' => 1,
            'message' => translate('Removed successfully'),
        ];
    }

    public function setStorageConnectionEnvironment(): void
    {
        $storageConnectionType = getWebConfig(name: 'storage_connection_type') ?? 'public';
        Config::set('filesystems.disks.default', $storageConnectionType);
        $storageConnectionS3Credential = getWebConfig(name: 'storage_connection_s3_credential');
        if ($storageConnectionType == 's3' && ! empty($storageConnectionS3Credential)) {
            Config::set('filesystems.disks.'.$storageConnectionType, $storageConnectionS3Credential);
        }
    }

    private function checkFileExists(string $filePath): array
    {
        if (Storage::disk('public')->exists($filePath)) {
            return [
                'status' => true,
                'disk' => 'public',
            ];
        } elseif (config('filesystems.disks.default') == 's3' && Storage::disk('s3')->exists($filePath)) {
            return [
                'status' => true,
                'disk' => 's3',
            ];
        } else {
            return [
                'status' => false,
                'disk' => config('filesystems.disks.default') ?? 'public',
            ];
        }
    }
}
