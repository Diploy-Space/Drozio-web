<?php

namespace App\Services\New;

use App\Traits\SettingsTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class ThemeService
{
    use SettingsTrait;

    public const REASON_ARCHIVE_UNREADABLE = 'archive-unreadable';

    public const REASON_INVALID_ARCHIVE = 'invalid-archive';

    private const THEMES_DIRECTORY = 'resources/themes';

    private const ACTIVE_THEME_ENVIRONMENT_KEY = 'WEB_THEME';

    private const MACOS_METADATA_FOLDER = '__MACOSX';

    /** @return array<string, array<string, mixed>> */
    public function getList(): array
    {
        $themes = [];

        foreach ($this->installedFolders() as $folder) {
            $themes[$folder] = $this->find(id: $folder);
        }

        return $themes;
    }

    /** @return array<string, mixed>|null */
    public function find(mixed $id): ?array
    {
        if ($id === null || $id === '' || ! File::isDirectory($this->themePath($id))) {
            return null;
        }

        $infoPath = $this->infoPath($id);
        $info = File::exists($infoPath) ? include $infoPath : [];
        $routes = $this->routes($id);

        $info['comfortable_panel_version'] = $routes['comfortable_panel_version'] ?? '';
        $info['routes'] = $routes;
        $info['is_published'] = theme_root_path() === $id;

        return $info;
    }

    /** @return array<string, mixed>|null */
    public function findBy(array $params): ?array
    {
        foreach ($this->getList() as $info) {
            if ($this->matchesParams($info, $params)) {
                return $info;
            }
        }

        return null;
    }

    public function exists(array $params): bool
    {
        return $this->findBy(params: $params) !== null;
    }

    /** @return array{status: bool, reason: ?string} */
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

            return ['status' => false, 'reason' => self::REASON_ARCHIVE_UNREADABLE];
        }

        $themeFolder = $this->resolveThemeFolder($zip);
        $zip->extractTo(storage_path('app/temp'));

        if (! File::exists(storage_path('app/temp/'.$themeFolder.'/public/addon/info.php'))) {
            $zip->close();
            $this->cleanUpAfterUpload();

            return ['status' => false, 'reason' => self::REASON_INVALID_ARCHIVE];
        }

        $zip->extractTo(base_path(self::THEMES_DIRECTORY));
        $zip->close();

        File::chmod($this->themePath($themeFolder).'/public/addon', 0777);
        $this->cleanUpAfterUpload();

        return ['status' => true, 'reason' => null];
    }

    /** @return array<string, mixed>|null */
    public function update(mixed $id, array $data): ?array
    {
        $info = $this->find(id: $id);

        if ($info === null) {
            return null;
        }

        $info = array_merge($this->storedInfo($id), $data);
        $this->writeInfo(theme: $id, info: $info);

        return $info;
    }

    public function delete(mixed $id): bool
    {
        $info = $this->find(id: $id);

        if ($info === null || $info['is_published']) {
            return false;
        }

        return File::deleteDirectory($this->themePath($id));
    }

    public function publish(mixed $id): bool
    {
        if ($this->find(id: $id) === null) {
            return false;
        }

        $this->setEnvironmentValue(self::ACTIVE_THEME_ENVIRONMENT_KEY, $id);

        Artisan::call('optimize:clear');
        Artisan::call('view:clear');
        cacheRemoveByType(type: 'banners');

        return true;
    }

    public function activateByDomain(string $theme, string $username, string $purchaseCode): bool
    {
        $info = $this->find(id: $theme);

        if ($info === null) {
            return false;
        }

        $response = Http::post(base64_decode('aHR0cHM6Ly9jaGVjay42YW10ZWNoLmNvbS9hcGkvdjEvZG9tYWluLXJlZ2lzdGVy'), [
            base64_decode('dXNlcm5hbWU=') => $username,
            base64_decode('cHVyY2hhc2Vfa2V5') => $purchaseCode,
            base64_decode('ZG9tYWlu') => $this->currentDomain(),
        ])->json();

        $status = isset($response['active']) ? (int) (base64_decode($response['active']) ?? 1) : 0;

        if (! $status) {
            return false;
        }

        return $this->update(id: $theme, data: [
            'is_active' => 1,
            'username' => $username,
            'purchase_code' => $purchaseCode,
        ]) !== null;
    }

    /** @return array<int, string> */
    private function installedFolders(): array
    {
        $folders = [];

        foreach (scandir(base_path(self::THEMES_DIRECTORY)) ?: [] as $entry) {
            if (in_array($entry, ['.', '..', '.DS_Store'], true)) {
                continue;
            }

            if (File::isDirectory($this->themePath($entry))) {
                $folders[] = $entry;
            }
        }

        return $folders;
    }

    /** @return array<string, mixed> */
    private function storedInfo(string $theme): array
    {
        $infoPath = $this->infoPath($theme);

        return File::exists($infoPath) ? include $infoPath : [];
    }

    /** @return array<string, mixed> */
    private function routes(string $theme): array
    {
        $routesPath = $this->themePath($theme).'/public/addon/theme_routes.php';

        return is_file($routesPath) ? include $routesPath : [];
    }

    private function themePath(string $theme): string
    {
        return base_path(self::THEMES_DIRECTORY.'/'.$theme);
    }

    private function infoPath(string $theme): string
    {
        return $this->themePath($theme).'/public/addon/info.php';
    }

    private function writeInfo(string $theme, array $info): void
    {
        unset($info['comfortable_panel_version'], $info['routes'], $info['is_published']);

        $contents = '<?php return '.var_export($info, true).';';
        file_put_contents($this->infoPath($theme), $contents);

        $publishedInfoPath = base_path('public/themes/'.$theme.'/public/addon/info.php');

        if (DOMAIN_POINTED_DIRECTORY === 'public' && File::exists(dirname($publishedInfoPath))) {
            file_put_contents($publishedInfoPath, $contents);
        }
    }

    private function currentDomain(): string
    {
        return str_replace(['http://', 'https://', 'www.'], '', url('/'));
    }

    private function resolveThemeFolder(ZipArchive $zip): string
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
        $metadataPath = base_path(self::THEMES_DIRECTORY.'/'.self::MACOS_METADATA_FOLDER);

        if (File::exists($metadataPath)) {
            File::deleteDirectory($metadataPath);
        }

        if (File::isDirectory(storage_path('app/temp'))) {
            File::cleanDirectory(storage_path('app/temp'));
        }
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
