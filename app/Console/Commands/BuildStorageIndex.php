<?php

namespace App\Console\Commands;

use App\Utils\FileExistenceCache;
use Illuminate\Console\Command;

class BuildStorageIndex extends Command
{
    protected $signature = 'storage:index';

    protected $description = 'Rebuild the local storage file index (bootstrap/cache/storage_files.php) for O(1) image existence checks';

    public function handle(): int
    {
        $count = FileExistenceCache::rebuild();

        $this->info("Storage index built: {$count} files indexed at " . FileExistenceCache::manifestPath());

        return self::SUCCESS;
    }
}
