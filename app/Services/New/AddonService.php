<?php

namespace App\Services\New;

use App\Traits\ActivationClass;
use FilesystemIterator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class AddonService
{
    use ActivationClass;

    public const REASON_ADDON_NOT_FOUND = 'addon-not-found';

    public const REASON_ARCHIVE_UNREADABLE = 'archive-unreadable';

    public const REASON_INVALID_ARCHIVE = 'invalid-archive';

    public const REASON_ALREADY_INSTALLED = 'already-installed';

    public const REASON_MODULES_DIRECTORY_NOT_WRITABLE = 'modules-directory-not-writable';

    public const REASON_SERVER_REQUIREMENT_UNMET = 'server-requirement-unmet';

    public const REASON_BUNDLE_EXTRACTION_FAILED = 'bundle-extraction-failed';

    public const REASON_INCOMPATIBLE_VERSION = 'incompatible-version';

    public const REASON_UPDATE_PENDING = 'update-pending';

    private const PRESERVED_ACTIVATION_KEYS = ['purchase_code', 'username', 'is_published'];

    private const MODULES_DIRECTORY = 'Modules';

    private const BUNDLED_STOREFRONT_ADDON = 'Builder';

    private const HIDDEN_ADDONS = ['Blog', 'TaxModule', 'AI'];

    private const MACOS_METADATA_FOLDER = '__MACOSX';

    private const MODULE_ASSETS_DIRECTORY = 'module-assets';

    private const THUMBNAIL_CANDIDATES = ['module-assets/thumbnail.png', 'public/thumbnail.png'];

    private const GATEWAYS_ADDON = 'Gateways';

    private const UNDECLARED_VERSION_LABEL = '1.0';

    private const GATEWAYS_THUMBNAIL = 'public/assets/backend/media/modules-thumb/gateways-thumbnail.webp';

    private string $bundleFailureDetail = '';

    /** @return array<int, array{path: string, info: array, isVersionSupported: bool, isUpdatePending: bool, isActive: bool, displayVersion: string, supportedVersions: ?string, thumbnailUrl: ?string}> */
    public function getList(): array
    {
        $addons = [];

        foreach ($this->getInstalledPaths() as $path) {
            $info = $this->find(id: $path);

            if ($info !== null) {
                $isVersionSupported = $this->isVersionSupported(path: $path, info: $info);
                $isUpdatePending = $this->isUpdatePending(path: $path, info: $info);

                $addons[] = [
                    'path' => $path,
                    'info' => $info,
                    'isVersionSupported' => $isVersionSupported,
                    'isUpdatePending' => $isUpdatePending,
                    'isActive' => $isVersionSupported && ! $isUpdatePending && ! empty($info['is_published']),
                    'displayVersion' => isset($info['version']) ? (string) $info['version'] : self::UNDECLARED_VERSION_LABEL,
                    'supportedVersions' => getAddonSupportedVersions(moduleName: basename($path)),
                    'thumbnailUrl' => $this->thumbnailUrl(path: $path, info: $info),
                ];
            }
        }

        return $addons;
    }

    public function find(mixed $id): ?array
    {
        $infoPath = $this->infoPath($id);

        return file_exists($infoPath) ? include $infoPath : null;
    }

    /** @return array<string, mixed>|null */
    public function findBy(array $params): ?array
    {
        foreach ($this->getList() as $addon) {
            if ($this->matchesParams($addon['info'], $params)) {
                return $addon['info'];
            }
        }

        return null;
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function exists(array $params): bool
    {
        return $this->findBy(params: $params) !== null;
    }

    /** @return array{status: bool, reason: ?string, detail: string} */
    public function create(UploadedFile $archive): array
    {
        $temporaryDirectory = storage_path('app/temp/');

        if (! File::exists($temporaryDirectory)) {
            File::makeDirectory($temporaryDirectory, 0775, true);
        }

        $temporaryPath = $archive->storeAs('temp', $archive->getClientOriginalName());

        $zip = new ZipArchive;

        if ($zip->open(storage_path('app/'.$temporaryPath)) !== true) {
            $this->cleanUpAfterUpload();

            return $this->failed(reason: self::REASON_ARCHIVE_UNREADABLE);
        }

        $addonFolder = $this->resolveAddonFolder($zip);
        $zip->extractTo(storage_path('app/temp'));

        $uploadedInfoPath = storage_path('app/temp/'.$addonFolder.'/Addon/info.php');

        if (! File::exists($uploadedInfoPath)) {
            $zip->close();
            $this->cleanUpAfterUpload();

            return $this->failed(reason: self::REASON_INVALID_ARCHIVE);
        }

        $uploadedInfo = include $uploadedInfoPath;

        if (! $this->isVersionSupported(path: $addonFolder, info: is_array($uploadedInfo) ? $uploadedInfo : [])) {
            $zip->close();
            $this->cleanUpAfterUpload();

            return $this->failed(reason: self::REASON_INCOMPATIBLE_VERSION, detail: (string) getAddonSupportedVersions(moduleName: $addonFolder));
        }

        $modulesPath = base_path(self::MODULES_DIRECTORY);

        if (! File::exists($modulesPath)) {
            File::makeDirectory($modulesPath, 0775, true);
        }

        $installedPath = self::MODULES_DIRECTORY.'/'.$addonFolder;
        $replacedInfo = null;

        if (File::exists($modulesPath.'/'.$addonFolder)) {
            $installedInfo = $this->find(id: $installedPath) ?? [];

            if ($this->isVersionSupported(path: $installedPath, info: $installedInfo)) {
                $zip->close();
                $this->cleanUpAfterUpload();

                return $this->failed(reason: self::REASON_ALREADY_INSTALLED);
            }

            $replacedInfo = $installedInfo;
            File::deleteDirectory($modulesPath.'/'.$addonFolder);
        }

        if (! is_writable($modulesPath)) {
            @chmod($modulesPath, 0775);
        }

        if (! is_writable($modulesPath) || ! @$zip->extractTo($modulesPath)) {
            $zip->close();
            $this->cleanUpAfterUpload();

            return $this->failed(reason: self::REASON_MODULES_DIRECTORY_NOT_WRITABLE, detail: $modulesPath);
        }

        $zip->close();
        @File::chmod($modulesPath.'/'.$addonFolder.'/Addon', 0777);

        $this->sanitizeExtractedAddon($modulesPath.'/'.$addonFolder);
        $this->publishModuleAssets($addonFolder);
        $this->syncPublicModuleLinks();

        if ($replacedInfo !== null) {
            $this->restoreActivation(path: $installedPath, replacedInfo: $replacedInfo);
        }

        if ($addonFolder === self::BUNDLED_STOREFRONT_ADDON) {
            $this->extractStorefrontBundle();
        }

        $this->recordInstallation(path: $installedPath);
        $this->cleanUpAfterUpload();

        return $this->succeeded();
    }

    public function update(mixed $id, array $data): ?array
    {
        $info = $this->find(id: $id);

        if ($info === null) {
            return null;
        }

        $info = array_merge($info, $data);
        $this->writeInfo(path: $id, info: $info);

        return $info;
    }

    /** @return array{status: bool, reason: ?string, detail: string} */
    public function updateStatus(mixed $id, bool $status): array
    {
        $info = $this->find(id: $id);

        if ($info === null) {
            return $this->failed(reason: self::REASON_ADDON_NOT_FOUND);
        }

        if ($status && ! $this->isVersionSupported(path: $id, info: $info)) {
            return $this->failed(reason: self::REASON_INCOMPATIBLE_VERSION, detail: (string) getAddonSupportedVersions(moduleName: basename($id)));
        }

        if ($status && $this->isUpdatePending(path: $id, info: $info)) {
            return $this->failed(reason: self::REASON_UPDATE_PENDING);
        }

        $info['is_published'] = $status ? 1 : 0;
        $this->writeInfo(path: $id, info: $info);

        if ($status) {
            $this->runMigrations(path: $id);
        }

        if (($info['name'] ?? '') === self::BUNDLED_STOREFRONT_ADDON) {
            if (! $status) {
                $this->removeStorefrontBundle();
            } else {
                $bundle = $this->activateStorefrontBundle();

                if (! $bundle['status']) {
                    $info['is_published'] = 0;
                    $this->writeInfo(path: $id, info: $info);

                    return $bundle;
                }
            }
        }

        $this->clearCompiledFiles();

        return $this->succeeded();
    }

    /** @return array{status: bool, reason: ?string, detail: string} */
    public function completeInstallation(string $id): array
    {
        $info = $this->find(id: $id);

        if ($info === null) {
            return $this->failed(reason: self::REASON_ADDON_NOT_FOUND);
        }

        if (! $this->isVersionSupported(path: $id, info: $info)) {
            return $this->failed(reason: self::REASON_INCOMPATIBLE_VERSION, detail: (string) getAddonSupportedVersions(moduleName: basename($id)));
        }

        $addonFolder = basename($id);
        $previousInstallation = getAddonInstallationRecords(refresh: true)[$addonFolder] ?? null;

        $this->publishModuleAssets($addonFolder);
        $this->syncPublicModuleLinks();

        if ($previousInstallation !== null) {
            $this->restoreActivation(path: $id, replacedInfo: $previousInstallation);
        } elseif (! empty($info['is_published'])) {
            $this->runMigrations(path: $id);
        }

        if ($addonFolder === self::BUNDLED_STOREFRONT_ADDON && ! empty($this->find(id: $id)['is_published'])) {
            $bundle = $this->activateStorefrontBundle();

            if (! $bundle['status']) {
                return $bundle;
            }
        }

        $this->recordInstallation(path: $id);
        $this->clearCompiledFiles();

        return $this->succeeded();
    }

    public function updateWhere(array $params, array $data): int
    {
        $affected = 0;

        foreach ($this->getList() as $addon) {
            if ($this->matchesParams($addon['info'], $params)) {
                $this->update(id: $addon['path'], data: $data);
                $affected++;
            }
        }

        return $affected;
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return $this->updateWhere(params: $params, data: $data) > 0;
    }

    /** @return array{active: int, inactive: int} */
    public function getStatusCounts(): array
    {
        $counts = ['active' => 0, 'inactive' => 0];

        foreach ($this->getList() as $addon) {
            $counts[$addon['isActive'] ? 'active' : 'inactive']++;
        }

        return $counts;
    }

    public function delete(mixed $id): bool
    {
        if (basename($id) === 'Gateways') {
            copy(base_path('app/Traits/Payment.txt'), base_path('app/Traits/Payment.php'));
        }

        if (! File::deleteDirectory(base_path($id))) {
            return false;
        }

        $this->syncPublicModuleLinks();

        return true;
    }

    /**
     * @return array{status: int, activationUrl: string}
     *
     * @throws ConnectionException
     */
    public function activateByDomain(string $path, string $username, string $purchaseCode): array
    {
        $info = $this->find(id: $path);

        $response = Http::post(base64_decode('aHR0cHM6Ly9jaGVjay42YW10ZWNoLmNvbS9hcGkvdjEvYWN0aXZhdGlvbi1jaGVjaw=='), [
            base64_decode('dXNlcm5hbWU=') => $username,
            base64_decode('cHVyY2hhc2Vfa2V5') => $purchaseCode,
            base64_decode('c29mdHdhcmVfaWQ=') => $info['software_id'] ?? null,
            base64_decode('ZG9tYWlu') => $this->getCurrentDomain(),
        ])->json();

        $status = isset($response['active']) ? (int) (base64_decode($response['active']) ?? 1) : 0;

        if ($status && $info !== null) {
            $this->writeInfo(path: $path, info: array_merge($info, [
                'is_published' => 1,
                'username' => $username,
                'purchase_code' => $purchaseCode,
            ]));
        }

        return [
            'status' => $status,
            'activationUrl' => base64_decode('aHR0cHM6Ly9hY3RpdmF0aW9uLjZhbXRlY2guY29t')
                .'?username='.$username
                .'&purchase_code='.$purchaseCode
                .'&domain='.url('/').'&',
        ];
    }

    /** @return array{status: int, message: string} */
    public function activateByPurchaseKey(array $data): array
    {
        $response = $this->getRequestConfig(
            username: $data['username'],
            purchaseKey: $data['purchase_key'],
            softwareId: $data['software_id'] ?? SOFTWARE_ID,
            softwareType: $data['software_type'] ?? base64_decode('cHJvZHVjdA=='),
            name: $data['name'] ?? null,
            identifier: $data['email'] ?? null,
        );

        $this->updateActivationConfig(app: $data['addon_name'] ?? null, response: $response);

        return [
            'status' => (int) ($response['active'] ?? 0),
            'message' => $response['message'] ?? '',
        ];
    }

    private function getCurrentDomain(): string
    {
        return str_replace(['http://', 'https://', 'www.'], '', url('/'));
    }

    public function getInstalledPaths(): array
    {
        $paths = [];

        foreach (scandir(base_path(self::MODULES_DIRECTORY)) ?: [] as $entry) {
            if (in_array($entry, ['.', '..', '.DS_Store', ...self::HIDDEN_ADDONS], true)) {
                continue;
            }

            $path = self::MODULES_DIRECTORY.'/'.$entry;

            if (is_dir(base_path($path)) && file_exists($this->infoPath($path))) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    private function isVersionSupported(string $path, array $info): bool
    {
        return isAddonVersionSupported(moduleName: basename($path), version: $info['version'] ?? null);
    }

    private function thumbnailUrl(string $path, array $info): ?string
    {
        foreach (self::THUMBNAIL_CANDIDATES as $candidate) {
            if (is_file(base_path('public/'.$path.'/'.$candidate))) {
                return dynamicAsset(path: $path.'/'.$candidate);
            }
        }

        $configuredThumbnail = isset($info['module_name']) ? config(strtolower($info['module_name']).'.thumbnail') : null;

        if (is_string($configuredThumbnail) && $configuredThumbnail !== '') {
            return $configuredThumbnail;
        }

        return basename($path) === self::GATEWAYS_ADDON ? dynamicAsset(path: self::GATEWAYS_THUMBNAIL) : null;
    }

    private function isUpdatePending(string $path, array $info): bool
    {
        return isAddonUpdatePending(moduleName: basename($path), version: $info['version'] ?? null);
    }

    private function recordInstallation(string $path): void
    {
        $info = $this->find(id: $path);

        if ($info !== null) {
            recordAddonInstallation(moduleName: basename($path), info: $info);
        }
    }

    private function syncInstallationActivation(string $path, array $info): void
    {
        $moduleName = basename($path);
        $installation = getAddonInstallationRecords()[$moduleName] ?? null;

        if ($installation !== null && ($installation['version'] ?? null) === (isset($info['version']) ? (string) $info['version'] : null)) {
            recordAddonInstallation(moduleName: $moduleName, info: $info);
        }
    }

    private function restoreActivation(string $path, array $replacedInfo): void
    {
        $activation = array_intersect_key($replacedInfo, array_flip(self::PRESERVED_ACTIVATION_KEYS));

        $this->forgetCachedInfo(infoPath: $this->infoPath($path));
        $this->update(id: $path, data: $activation);

        if (! empty($activation['is_published'])) {
            $this->runMigrations(path: $path);
        }
    }

    private function infoPath(string $path): string
    {
        return base_path(trim($path, '/').'/Addon/info.php');
    }

    private function writeInfo(string $path, array $info): void
    {
        $infoPath = $this->infoPath($path);

        file_put_contents($infoPath, '<?php return '.var_export($info, true).';');
        $this->forgetCachedInfo(infoPath: $infoPath);
        $this->syncInstallationActivation(path: $path, info: $info);
    }

    private function forgetCachedInfo(string $infoPath): void
    {
        clearstatcache(true, $infoPath);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($infoPath, true);
        }
    }

    private function runMigrations(string $path): void
    {
        $migrationsPath = trim($path, '/').'/database/migrations';

        if (File::isDirectory(base_path($migrationsPath))) {
            Artisan::call('migrate', ['--path' => $migrationsPath, '--force' => true]);
        }
    }

    private function clearCompiledFiles(): void
    {
        Artisan::call('optimize:clear');
        Artisan::call('view:clear');
    }

    private function resolveAddonFolder(ZipArchive $zip): string
    {
        $firstEntryFolder = explode('/', $zip->getNameIndex(0))[0];

        if ($firstEntryFolder !== self::MACOS_METADATA_FOLDER) {
            return explode('.', $firstEntryFolder)[0];
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            if (! str_contains($zip->getNameIndex($index), self::MACOS_METADATA_FOLDER)) {
                return explode('/', $zip->getNameIndex($index))[0];
            }
        }

        return explode('.', $firstEntryFolder)[0];
    }

    private function cleanUpAfterUpload(): void
    {
        if (File::exists(base_path(self::MODULES_DIRECTORY.'/'.self::MACOS_METADATA_FOLDER))) {
            File::deleteDirectory(base_path(self::MODULES_DIRECTORY.'/'.self::MACOS_METADATA_FOLDER));
        }

        if (File::isDirectory(storage_path('app/temp'))) {
            File::cleanDirectory(storage_path('app/temp'));
        }
    }

    /** @return array{status: bool, reason: ?string, detail: string} */
    private function activateStorefrontBundle(): array
    {
        $issues = $this->storefrontBundleRequirementIssues();

        if ($issues !== []) {
            return $this->failed(
                reason: self::REASON_SERVER_REQUIREMENT_UNMET,
                detail: $issues[0]['message'].' — '.$issues[0]['fix'],
            );
        }

        if (! $this->extractStorefrontBundle()) {
            Log::error('Builder bundle extraction failed: '.($this->bundleFailureDetail ?: 'unknown'));

            return $this->failed(
                reason: self::REASON_BUNDLE_EXTRACTION_FAILED,
                detail: $this->bundleFailureDetail,
            );
        }

        return $this->succeeded();
    }

    /** @return array<int, array{message: string, fix: string}> */
    private function storefrontBundleRequirementIssues(): array
    {
        $issues = [];

        if (PHP_VERSION_ID < 80100) {
            $issues[] = ['message' => translate('PHP 8.1 or higher is required').' ('.PHP_VERSION.')', 'fix' => translate('Upgrade php from your hosting control panel')];
        }

        foreach (['zip', 'pdo_mysql', 'mbstring', 'fileinfo', 'openssl', 'tokenizer', 'xml', 'ctype', 'bcmath'] as $extension) {
            if (! extension_loaded($extension)) {
                $issues[] = ['message' => translate('Php extension not loaded').": {$extension}", 'fix' => translate('Enable it in php ini or via your hosting panel')];
            }
        }

        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            $issues[] = ['message' => translate('No image extension gd or imagick is loaded'), 'fix' => translate('Enable the gd extension in php ini')];
        }

        foreach (['symlink', 'copy', 'unlink', 'rmdir', 'glob', 'realpath'] as $function) {
            if (! function_exists($function)) {
                $issues[] = ['message' => translate('Php function is disabled').": {$function}", 'fix' => translate('Remove it from disable functions in php ini')];
            }
        }

        foreach ([public_path(), base_path('bootstrap/cache'), storage_path(), base_path(self::MODULES_DIRECTORY.'/'.self::BUNDLED_STOREFRONT_ADDON)] as $writablePath) {
            if (is_dir($writablePath) && ! is_writable($writablePath)) {
                $label = str_replace(base_path().DIRECTORY_SEPARATOR, '', $writablePath);
                $issues[] = ['message' => translate('Path is not writable').": {$label}", 'fix' => translate('Grant write permission to the web server user')];
            }
        }

        if (! file_exists($this->storefrontBundlePath())) {
            $issues[] = ['message' => translate('Builder pre built bundle is missing'), 'fix' => translate('Re-upload the addon zip, the bundle appears incomplete')];
        }

        return $issues;
    }

    private function extractStorefrontBundle(): bool
    {
        $this->bundleFailureDetail = '';

        $failure = $this->extractZipTo($this->storefrontBundlePath(), public_path('build'));

        if ($failure !== '') {
            $this->bundleFailureDetail = $failure;

            return false;
        }

        if (! $this->linkPublicBuildToProjectRoot()) {
            $this->bundleFailureDetail = 'could not create the "build" entry at the project root '.base_path()
                .' (required for this docroot). Grant the web-server user write permission to the project root, or enable symlink().';

            return false;
        }

        return true;
    }

    private function removeStorefrontBundle(): void
    {
        $rootLink = base_path('build');

        if (is_link($rootLink)) {
            @unlink($rootLink);
        }

        $bundle = public_path('build');

        if (! is_link($bundle) && File::isDirectory($bundle)) {
            File::deleteDirectory($bundle);
        }
    }

    private function storefrontBundlePath(): string
    {
        return base_path(self::MODULES_DIRECTORY.'/'.self::BUNDLED_STOREFRONT_ADDON.'/resources/dist/build.zip');
    }

    private function linkPublicBuildToProjectRoot(): bool
    {
        $target = public_path('build');

        if (! File::isDirectory($target)) {
            return false;
        }

        if ($this->rebuildProjectRootBuild($target, base_path('build'))) {
            return true;
        }

        Log::warning('Builder: could not create "build" at the project root '.base_path()
            .' — grant the web-server user write permission there (or create the symlink over SSH). public/build was created normally.');

        $rootDocroot = (defined('DOMAIN_POINTED_DIRECTORY') ? DOMAIN_POINTED_DIRECTORY : 'public') !== 'public';

        return ! $rootDocroot;
    }

    private function rebuildProjectRootBuild(string $target, string $link): bool
    {
        try {
            if (is_link($link) || is_file($link)) {
                @unlink($link);
            } elseif (File::isDirectory($link)) {
                File::deleteDirectory($link);
            }

            if (@symlink($target, $link)) {
                return true;
            }

            return File::copyDirectory($target, $link);
        } catch (\Throwable) {
            return false;
        }
    }

    private function extractZipTo(string $zipPath, string $targetDir): string
    {
        if (! file_exists($zipPath)) {
            return 'source bundle not found at '.$zipPath;
        }

        $stagingDir = $targetDir.'_tmp_'.getmypid();

        if (File::isDirectory($stagingDir)) {
            File::deleteDirectory($stagingDir);
        }

        if (! File::makeDirectory($stagingDir, 0755, true, true)) {
            return 'could not create a staging directory next to '.$targetDir.' — the parent directory is not writable by the web-server user';
        }

        $zip = new ZipArchive;
        $opened = $zip->open($zipPath);

        if ($opened !== true) {
            File::deleteDirectory($stagingDir);

            return 'ZipArchive could not open the bundle (error code '.$opened.') — the zip is likely corrupt or truncated; re-upload the addon';
        }

        if (! $zip->extractTo($stagingDir)) {
            $zip->close();
            File::deleteDirectory($stagingDir);

            return 'extraction failed — likely out of disk space or an open_basedir restriction';
        }

        $zip->close();

        if (is_link($targetDir)) {
            @unlink($targetDir);
        } elseif (File::isDirectory($targetDir) && ! File::deleteDirectory($targetDir)) {
            File::deleteDirectory($stagingDir);

            return 'could not remove the existing '.$targetDir.' — its files are owned by a different user than the web server; delete public/build over SSH and retry';
        }

        if (! @rename($stagingDir, $targetDir)) {
            File::deleteDirectory($stagingDir);

            return 'could not move the extracted bundle into '.$targetDir;
        }

        return '';
    }

    private function syncPublicModuleLinks(): void
    {
        if (DOMAIN_POINTED_DIRECTORY != 'public') {
            return;
        }

        try {
            $this->doSyncPublicModuleLinks();
        } catch (\Throwable) {
        }
    }

    private function doSyncPublicModuleLinks(): void
    {
        $publicModulesPath = public_path(self::MODULES_DIRECTORY);
        $modulesBasePath = base_path(self::MODULES_DIRECTORY);

        if (is_link($publicModulesPath)) {
            @unlink($publicModulesPath);
        }

        if (! File::isDirectory($publicModulesPath)) {
            File::makeDirectory($publicModulesPath, 0775, true);
        }

        foreach (scandir($publicModulesPath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $publicModulesPath.DIRECTORY_SEPARATOR.$entry;
            $sourceAssets = $modulesBasePath.DIRECTORY_SEPARATOR.$entry.DIRECTORY_SEPARATOR.'module-assets';

            if ($entry === self::MACOS_METADATA_FOLDER) {
                $this->removePath($entryPath);

                continue;
            }

            if (is_link($entryPath)) {
                @unlink($entryPath);

                continue;
            }

            if (! File::isDirectory($entryPath)) {
                continue;
            }

            $sourceMissing = ! File::isDirectory($sourceAssets);

            if ($sourceMissing) {
                $this->removePath($entryPath.DIRECTORY_SEPARATOR.'module-assets');
            }

            if ($sourceMissing && File::isDirectory($entryPath) && count(scandir($entryPath)) === 2) {
                @rmdir($entryPath);
            }
        }

        foreach (File::directories($modulesBasePath) as $modulePath) {
            $sourceAssets = $modulePath.DIRECTORY_SEPARATOR.'module-assets';

            if (! File::isDirectory($sourceAssets)) {
                continue;
            }

            $moduleStubPath = $publicModulesPath.DIRECTORY_SEPARATOR.basename($modulePath);
            $assetsPath = $moduleStubPath.DIRECTORY_SEPARATOR.'module-assets';

            if (! File::isDirectory($moduleStubPath)) {
                File::makeDirectory($moduleStubPath, 0775, true);
            }

            $this->removePath($assetsPath);

            if (! File::copyDirectory($sourceAssets, $assetsPath)) {
                $this->copyAddonAssetsBestEffort($sourceAssets, $assetsPath);
            }
        }

        $this->clearCompiledFiles();
    }

    private function publishModuleAssets(string $addonFolder): void
    {
        $sourceAssets = base_path(self::MODULES_DIRECTORY.'/'.$addonFolder.'/'.self::MODULE_ASSETS_DIRECTORY);

        if (! File::isDirectory($sourceAssets)) {
            return;
        }

        $publicModulesPath = public_path(self::MODULES_DIRECTORY);

        if (is_link($publicModulesPath)) {
            @unlink($publicModulesPath);
        }

        $publicAssets = $publicModulesPath.'/'.$addonFolder.'/'.self::MODULE_ASSETS_DIRECTORY;

        $this->removePath($publicAssets);
        File::ensureDirectoryExists(dirname($publicAssets), 0775);

        if (! File::copyDirectory($sourceAssets, $publicAssets)) {
            $this->copyAddonAssetsBestEffort($sourceAssets, $publicAssets);
        }
    }

    private function copyAddonAssetsBestEffort(string $source, string $destination): void
    {
        $this->removePath($destination);
        File::makeDirectory($destination, 0775, true);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination.DIRECTORY_SEPARATOR.substr($item->getPathname(), strlen($source) + 1);

            if ($item->isDir()) {
                if (is_link($target) || (file_exists($target) && ! is_dir($target))) {
                    @unlink($target);
                }

                if (! is_dir($target)) {
                    @mkdir($target, 0775, true);
                }

                continue;
            }

            if (is_link($target) || is_dir($target)) {
                $this->removePath($target);
            }

            @copy($item->getPathname(), $target);
        }
    }

    private function sanitizeExtractedAddon(string $moduleDir): void
    {
        if (! File::isDirectory($moduleDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($moduleDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getFilename() === '.DS_Store') {
                @unlink($item->getPathname());
            }
        }

        foreach (['build', 'build-public'] as $strayBuildDirectory) {
            $strayPath = $moduleDir.'/resources/dist/'.$strayBuildDirectory;

            if (File::isDirectory($strayPath)) {
                File::deleteDirectory($strayPath);
            }
        }
    }

    private function removePath(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        }
    }

    /** @return array{status: bool, reason: ?string, detail: string} */
    private function succeeded(): array
    {
        return ['status' => true, 'reason' => null, 'detail' => ''];
    }

    /** @return array{status: bool, reason: ?string, detail: string} */
    private function failed(string $reason, string $detail = ''): array
    {
        return ['status' => false, 'reason' => $reason, 'detail' => $detail];
    }

    private function matchesParams(array $info, array $params): bool
    {
        foreach ($params as $key => $value) {
            $stored = $info[$key] ?? null;

            $isMatch = is_array($value) ? in_array($stored, $value, false) : $stored == $value;

            if (! $isMatch) {
                return false;
            }
        }

        return true;
    }
}
