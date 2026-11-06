<?php

namespace App\Traits;

use App\Enums\GlobalConstant;
use App\Utils\FileManagerLogic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

trait AttachmentStorageTrait
{
    use FileManagerTrait {
        update as private updateFile;
        delete as private deleteFile;
    }

    private const VECTOR_EXTENSION = '.svg';

    /**
     * @return array{file_name: string, storage: string}
     */
    protected function storeImageAttachment(UploadedFile $file, string $directory): array
    {
        return [
            'file_name' => $this->upload(dir: $directory, format: 'webp', image: $file),
            'storage' => $this->storageDisk(),
        ];
    }

    /**
     * @return array{file_name: string, storage: string}
     */
    protected function storeRawAttachment(UploadedFile $file, string $directory): array
    {
        return [
            'file_name' => $this->fileUpload(dir: $directory, format: $file->getClientOriginalExtension(), file: $file),
            'storage' => $this->storageDisk(),
        ];
    }

    /**
     * @return array<int, array{file_name: string, storage: string}>
     */
    protected function storeAttachments(array $files, string $directory): array
    {
        $stored = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $stored[] = $this->isReEncodableImage($file)
                ? $this->storeImageAttachment(file: $file, directory: $directory)
                : $this->storeRawAttachment(file: $file, directory: $directory);
        }

        return $stored;
    }

    /**
     * @return array{documents: array<int, array<string, mixed>>, media: array<int, array<string, mixed>>}
     */
    public function getAttachmentGroups(Model $model): array
    {
        $groups = ['documents' => [], 'media' => []];

        foreach ($model->attachment_full_url ?? [] as $attachment) {
            $extension = strtolower(strrchr($attachment['key'], '.'));
            $isDocument = in_array($extension, GlobalConstant::DOCUMENT_EXTENSION, true);

            $groups[$isDocument ? 'documents' : 'media'][] = [
                'name' => $attachment['key'],
                'path' => $attachment['path'],
                'extension' => $extension,
                'isVideo' => in_array($extension, GlobalConstant::VIDEO_EXTENSION, true),
                'size' => $isDocument ? FileManagerLogic::getFileSize($attachment['path']) : null,
                'link' => $attachment,
            ];
        }

        return $groups;
    }

    private function isReEncodableImage(UploadedFile $file): bool
    {
        $extension = '.'.strtolower($file->getClientOriginalExtension());

        return $extension !== self::VECTOR_EXTENSION
            && in_array($extension, GlobalConstant::IMAGE_EXTENSION, true);
    }
}
