<?php

namespace App\Console\Commands;

use App\Services\New\AddonService;
use Illuminate\Console\Command;

class CompleteAddonInstallation extends Command
{
    protected $signature = 'addon:complete-installation {name : Addon folder name, e.g. Auction}';

    protected $description = 'Finish an addon whose files were extracted manually: publish its assets, run its migrations and clear the caches';

    private const MODULES_DIRECTORY = 'Modules';

    public function handle(AddonService $addonService): int
    {
        $path = self::MODULES_DIRECTORY.'/'.$this->argument('name');

        if (! in_array($path, $addonService->getInstalledPaths(), true)) {
            $this->error("No installed addon found at {$path}.");

            return self::FAILURE;
        }

        $result = $addonService->completeInstallation(id: $path);

        if (! $result['status']) {
            $this->error(trim($result['reason'].' '.$result['detail']));

            return self::FAILURE;
        }

        $this->info("{$path} is installed and up to date.");

        return self::SUCCESS;
    }
}
