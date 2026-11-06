<?php

namespace App\Services\New;

use App\Utils\FileManagerLogic;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use XMLWriter;

class SiteMapService
{
    public const STORAGE_DIRECTORY = 'sitemap';

    private const PUBLISHED_FILE_NAME = 'sitemap.xml';

    private const FILE_EXTENSION = '.xml';

    private const URL_PRIORITY = '0.8';

    private const URL_CHANGE_FREQUENCY = 'weekly';

    private const SITEMAP_NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    private const MAX_URLS_PER_FILE = 25000;

    private const FLUSH_EVERY_URLS = 1000;

    private const PUBLISHED_FILE_PERMISSION = 0777;

    /** @return array<int, array{name: string, size: string, created_at: Carbon, modified_at: Carbon}> */
    public function getSitemapFiles(): array
    {
        $directory = $this->directory();
        File::ensureDirectoryExists($directory);

        $files = [];
        foreach (File::files($directory) as $file) {
            $files[] = [
                'name' => $file->getFilename(),
                'size' => FileManagerLogic::formatBytes($file->getSize()),
                'created_at' => Carbon::createFromTimestamp($file->getCTime()),
                'modified_at' => Carbon::createFromTimestamp($file->getMTime()),
            ];
        }

        usort($files, fn (array $first, array $second) => $second['modified_at'] <=> $first['modified_at']);

        return $files;
    }

    /** @param  iterable<int, array{loc: string, lastmod?: ?CarbonInterface}>  $urls */
    public function generate(iterable $urls, bool $publish = false): string
    {
        $generatedAt = Carbon::now();
        $directory = $this->directory();
        File::ensureDirectoryExists($directory);

        $baseName = 'sitemap-'.Str::slug($generatedAt);
        $urlSetFiles = $this->writeUrlSets(urls: $urls, directory: $directory, baseName: $baseName, generatedAt: $generatedAt);

        $primaryFileName = $baseName.self::FILE_EXTENSION;

        count($urlSetFiles) === 1
            ? File::move($directory.'/'.$urlSetFiles[0], $directory.'/'.$primaryFileName)
            : $this->writeIndex(path: $directory.'/'.$primaryFileName, urlSetFiles: $urlSetFiles, generatedAt: $generatedAt);

        if ($publish) {
            $this->publish(path: $directory.'/'.$primaryFileName);
        }

        return $primaryFileName;
    }

    public function findSitemapPath(string $fileName): ?string
    {
        $fileName = basename($fileName);
        $path = $this->directory().'/'.$fileName;

        return Str::endsWith($fileName, self::FILE_EXTENSION) && File::exists($path) ? $path : null;
    }

    public function storeUploadedFile(UploadedFile $file): void
    {
        $directory = $this->directory();
        File::ensureDirectoryExists($directory);

        $contents = File::get($file->getRealPath());

        File::put($directory.'/sitemap-'.Str::slug(Carbon::now()).self::FILE_EXTENSION, $contents);
        File::put(public_path(self::PUBLISHED_FILE_NAME), $contents);
        File::put(base_path(self::PUBLISHED_FILE_NAME), $contents);
    }

    public function delete(string $fileName): bool
    {
        $path = $this->findSitemapPath(fileName: $fileName);

        if ($path === null) {
            return false;
        }

        File::delete($this->urlSetPathsOf(path: $path));

        return File::delete($path);
    }

    /**
     * @param  iterable<int, array{loc: string, lastmod?: ?CarbonInterface}>  $urls
     * @return array<int, string>
     */
    private function writeUrlSets(iterable $urls, string $directory, string $baseName, Carbon $generatedAt): array
    {
        $fileNames = [];
        $writer = null;
        $writtenToFile = 0;
        $seen = [];

        foreach ($urls as $url) {
            $location = $url['loc'] ?? null;

            if (! is_string($location) || $location === '' || isset($seen[$location])) {
                continue;
            }

            $seen[$location] = true;

            if ($writer === null) {
                $fileNames[] = $baseName.'-'.(count($fileNames) + 1).self::FILE_EXTENSION;
                $writer = $this->openUrlSet(path: $directory.'/'.end($fileNames));
            }

            $this->writeUrl(writer: $writer, location: $location, lastModifiedAt: $url['lastmod'] ?? $generatedAt);
            $writtenToFile++;

            if ($writtenToFile % self::FLUSH_EVERY_URLS === 0) {
                $writer->flush();
            }

            if ($writtenToFile === self::MAX_URLS_PER_FILE) {
                $this->closeDocument(writer: $writer);
                $writer = null;
                $writtenToFile = 0;
            }
        }

        if ($writer === null && $fileNames === []) {
            $fileNames[] = $baseName.'-1'.self::FILE_EXTENSION;
            $writer = $this->openUrlSet(path: $directory.'/'.end($fileNames));
        }

        if ($writer !== null) {
            $this->closeDocument(writer: $writer);
        }

        return $fileNames;
    }

    private function openUrlSet(string $path): XMLWriter
    {
        $writer = new XMLWriter;
        $writer->openUri($path);
        $writer->setIndent(true);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', self::SITEMAP_NAMESPACE);

        return $writer;
    }

    private function writeUrl(XMLWriter $writer, string $location, ?CarbonInterface $lastModifiedAt): void
    {
        $writer->startElement('url');
        $writer->writeElement('loc', $location);

        if ($lastModifiedAt !== null) {
            $writer->writeElement('lastmod', $lastModifiedAt->toAtomString());
        }

        $writer->writeElement('changefreq', self::URL_CHANGE_FREQUENCY);
        $writer->writeElement('priority', self::URL_PRIORITY);
        $writer->endElement();
    }

    /** @param  array<int, string>  $urlSetFiles */
    private function writeIndex(string $path, array $urlSetFiles, Carbon $generatedAt): void
    {
        $writer = new XMLWriter;
        $writer->openUri($path);
        $writer->setIndent(true);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('sitemapindex');
        $writer->writeAttribute('xmlns', self::SITEMAP_NAMESPACE);

        foreach ($urlSetFiles as $fileName) {
            $writer->startElement('sitemap');
            $writer->writeElement('loc', $this->publicUrl(fileName: $fileName));
            $writer->writeElement('lastmod', $generatedAt->toAtomString());
            $writer->endElement();
        }

        $this->closeDocument(writer: $writer);
    }

    private function closeDocument(XMLWriter $writer): void
    {
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    private function publish(string $path): void
    {
        foreach ([public_path(self::PUBLISHED_FILE_NAME), base_path(self::PUBLISHED_FILE_NAME)] as $publishedPath) {
            $this->allowWriting(path: $publishedPath);
            File::copy($path, $publishedPath);
            $this->allowWriting(path: $publishedPath);
        }
    }

    private function allowWriting(string $path): void
    {
        if (File::exists($path)) {
            @chmod($path, self::PUBLISHED_FILE_PERMISSION);
        }
    }

    /** @return array<int, string> */
    private function urlSetPathsOf(string $path): array
    {
        return File::glob(Str::beforeLast($path, self::FILE_EXTENSION).'-*'.self::FILE_EXTENSION) ?: [];
    }

    private function publicUrl(string $fileName): string
    {
        return dynamicStorage('storage/app/public/'.self::STORAGE_DIRECTORY.'/'.$fileName);
    }

    private function directory(): string
    {
        return storage_path('app/public/'.self::STORAGE_DIRECTORY);
    }
}
