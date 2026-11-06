<?php

namespace App\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager as InterventionImageManager;

class ImageManager
{
    public static function upload(string $dir, string $format, $image, $file_type = 'image'): string
    {
        $storage = config('filesystems.disks.default') ?? 'public';
        if ($image != null) {
            if (! Storage::disk($storage)->exists($dir)) {
                Storage::disk($storage)->makeDirectory($dir);
            }

            $imageName = Carbon::now()->toDateString().'-'.uniqid().'.'.$image->getClientOriginalExtension();
            Storage::disk($storage)->put($dir.$imageName, file_get_contents($image));

            if (in_array($image->getClientOriginalExtension(), ['gif', 'svg'])) {
                $imageName = Carbon::now()->toDateString().'-'.uniqid().'.'.$image->getClientOriginalExtension();
                Storage::disk($storage)->put($dir.$imageName, file_get_contents($image));
            } else {
                if (in_array(request()->ip(), ['127.0.0.1', '::1']) && ! (imagetypes() & IMG_WEBP) || config('app.debug') && ! (imagetypes() & IMG_WEBP)) {
                    $format = 'png';
                }
                $decoded = InterventionImageManager::gd()->read($image);
                $imageWebp = $format === 'png'
                    ? $decoded->encodeByExtension($format)
                    : $decoded->encodeByExtension($format, quality: 85);
                $imageName = Carbon::now()->toDateString().'-'.uniqid().'.'.$format;
                Storage::disk($storage)->put($dir.$imageName, (string) $imageWebp);
            }
        } else {
            $imageName = 'def.webp';
        }
        if ($storage === 'public' && $imageName !== 'def.webp') {
            FileExistenceCache::remember(disk: $storage, path: $dir.$imageName);
        }

        return $imageName;
    }

    public static function file_upload(string $dir, string $format, $file = null): string
    {
        $storage = config('filesystems.disks.default') ?? 'public';
        if ($file != null) {
            $fileName = Carbon::now()->toDateString().'-'.uniqid().'.'.$format;
            if (! Storage::disk($storage)->exists($dir)) {
                Storage::disk($storage)->makeDirectory($dir);
            }
            Storage::disk($storage)->put($dir.$fileName, file_get_contents($file));
            if ($storage === 'public') {
                FileExistenceCache::remember(disk: $storage, path: $dir.$fileName);
            }
        } else {
            $fileName = 'def.png';
        }

        return $fileName;
    }

    public static function update(string $dir, $old_image, string $format, $image, $file_type = 'image'): string
    {
        $fileInfo = self::checkFileExists(filePath: $dir.$old_image);
        if ($fileInfo['status']) {
            Storage::disk($fileInfo['disk'])->delete($dir.$old_image);
            FileExistenceCache::forget(disk: $fileInfo['disk'], path: $dir.$old_image);
        }

        return $file_type == 'file' ? ImageManager::file_upload($dir, $format, $image) : ImageManager::upload($dir, $format, $image);
    }

    public static function delete($full_path): array
    {
        $fileInfo = self::checkFileExists(filePath: $full_path);
        if ($fileInfo['status']) {
            Storage::disk($fileInfo['disk'])->delete($full_path);
            FileExistenceCache::forget(disk: $fileInfo['disk'], path: $full_path);
        }

        return [
            'success' => 1,
            'message' => 'Removed successfully !',
        ];

    }

    public static function checkFileExists(string $filePath): array
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
