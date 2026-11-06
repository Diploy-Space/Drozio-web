<?php

namespace App\Console\Commands;

use App\Jobs\GenerateSiteMapJob;
use App\Services\New\SiteMapService;
use Illuminate\Console\Command;

class GenerateSiteMapCommand extends Command
{
    protected $signature = 'sitemap:generate {--publish : Also write the sitemap to the web root as sitemap.xml}';

    protected $description = 'Build the storefront sitemap from the database without going through the queue';

    public function handle(): int
    {
        $publish = (bool) $this->option('publish');

        $this->line('Base url: '.url('/'));

        $fileName = $this->laravel->call([new GenerateSiteMapJob(publish: $publish), 'handle']);
        $path = storage_path('app/public/'.SiteMapService::STORAGE_DIRECTORY.'/'.$fileName);

        $parts = glob(substr($path, 0, -4).'-*.xml') ?: [$path];

        $this->info('Generated '.$fileName.' ('.$this->urlCount($parts).' urls across '.count($parts).' file(s))');

        if ($publish) {
            $this->info('Published to '.public_path('sitemap.xml'));
        }

        return self::SUCCESS;
    }

    /** @param  array<int, string>  $paths */
    private function urlCount(array $paths): int
    {
        return array_sum(array_map(
            static fn (string $path): int => substr_count((string) file_get_contents($path), '<loc>'),
            array_filter($paths, 'file_exists'),
        ));
    }
}
