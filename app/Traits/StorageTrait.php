<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait StorageTrait
{
    public function storage(): MorphMany
    {
        return $this->morphMany(\App\Models\Storage::class, 'data');
    }

    public function storageLink($path, $data, $type): string|array
    {
        return storageLink($path, $data, $type);
    }

    protected function storageConnectionCheck(): string
    {
        return config('filesystems.disks.default') ?? 'public';
    }
}
