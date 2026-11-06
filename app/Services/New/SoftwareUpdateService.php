<?php

namespace App\Services\New;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class SoftwareUpdateService
{
    private const PACKAGE_DISK = 'local';

    private const PACKAGE_DIRECTORY = 'uploads';

    private const PACKAGE_FILE_NAME = 'update.zip';

    private const VERSION_FILE = 'Library/Constant.php';

    private const ENVIRONMENT_FILE = '.env';

    private const VERSION_PATTERN = "/const\s+SOFTWARE_VERSION\s*=\s*['\"]?([\d\.]+)['\"]?/";

    private const ROUTE_SERVICE_PROVIDER = 'app/Providers/RouteServiceProvider.php';

    private const ROUTE_SERVICE_PROVIDER_LIVE = 'app/Providers/RouteServiceProvider.txt';

    private const COMPLETION_TOKEN_FILE = 'software-update-completion.json';

    private const COMPLETION_TOKEN_LENGTH = 64;

    private const COMPLETION_TOKEN_LIFETIME_MINUTES = 60;

    private const VERSION_FORMAT = '/^\d+(\.\d+)*$/';

    public function storePackage(UploadedFile $package): string
    {
        Storage::disk(self::PACKAGE_DISK)->putFileAs(self::PACKAGE_DIRECTORY, $package, self::PACKAGE_FILE_NAME);

        return $this->packagePath();
    }

    public function isNewerPackage(?string $installedVersion): bool
    {
        $packageVersion = $this->getPackageVersion();

        return $packageVersion !== null && version_compare($packageVersion, (string) $installedVersion, '>');
    }

    public function isUpdateUnfinished(mixed $installedVersion): bool
    {
        return defined('SOFTWARE_VERSION')
            && is_string($installedVersion)
            && preg_match(self::VERSION_FORMAT, $installedVersion) === 1
            && version_compare($installedVersion, SOFTWARE_VERSION, '<');
    }

    public function applyPackage(): bool
    {
        $zip = new ZipArchive;

        if ($zip->open($this->packagePath()) !== true) {
            return false;
        }

        $isExtracted = $zip->extractTo(base_path());
        $zip->close();

        $this->discardCompiledPhpCache();

        return $isExtracted;
    }

    public function issueCompletionToken(): string
    {
        $token = Str::random(self::COMPLETION_TOKEN_LENGTH);

        Storage::disk(self::PACKAGE_DISK)->put(self::COMPLETION_TOKEN_FILE, json_encode([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(self::COMPLETION_TOKEN_LIFETIME_MINUTES)->timestamp,
        ]));

        return $token;
    }

    public function consumeCompletionToken(string $token): bool
    {
        $disk = Storage::disk(self::PACKAGE_DISK);

        if (! $disk->exists(self::COMPLETION_TOKEN_FILE)) {
            return false;
        }

        $completion = json_decode((string) $disk->get(self::COMPLETION_TOKEN_FILE), true);
        $disk->delete(self::COMPLETION_TOKEN_FILE);

        return is_array($completion)
            && (int) ($completion['expires_at'] ?? 0) >= now()->timestamp
            && hash_equals((string) ($completion['token_hash'] ?? ''), hash('sha256', $token));
    }

    public function completeUpdate(): void
    {
        Artisan::call('migrate', ['--force' => true]);

        $this->restoreRouteServiceProvider();
        $this->linkPublicDirectories();

        Artisan::call('optimize:clear');
    }

    public function discardCompiledPhpCache(): void
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    public function getPackageVersion(): ?string
    {
        $zip = new ZipArchive;

        if ($zip->open($this->packagePath()) !== true) {
            return null;
        }

        $version = null;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->getNameIndex($index);

            if (! strpos($entry, self::VERSION_FILE) || strpos($entry, self::ENVIRONMENT_FILE)) {
                continue;
            }

            if (preg_match(self::VERSION_PATTERN, $zip->getFromIndex($index), $matches) && ($version === null || $matches[1] > $version)) {
                $version = $matches[1];
            }
        }
        $zip->close();

        return $version;
    }

    private function restoreRouteServiceProvider(): void
    {
        if (file_exists(base_path(self::ROUTE_SERVICE_PROVIDER_LIVE))) {
            copy(base_path(self::ROUTE_SERVICE_PROVIDER_LIVE), base_path(self::ROUTE_SERVICE_PROVIDER));
        }
    }

    private function linkPublicDirectories(): void
    {
        if (DOMAIN_POINTED_DIRECTORY != 'public') {
            return;
        }

        shell_exec('ln -s ../resources/themes themes');
        Artisan::call('storage:link');
    }

    private function packagePath(): string
    {
        return base_path('storage/app/'.self::PACKAGE_DIRECTORY.'/'.self::PACKAGE_FILE_NAME);
    }
}
