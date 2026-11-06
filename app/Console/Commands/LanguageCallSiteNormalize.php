<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Rewrites literal translate('...') arguments onto the key standard, so the source reads as the
 * English sentence it renders: translate('3rd Party Setup') becomes translate('3rd Party Setup').
 *
 * normalizeTranslationKey() already folds both spellings onto one key, so this changes no
 * behaviour — it is the source-side half of the same migration lang:normalize-keys applied to
 * resources/lang, and what stops the old shape from being reintroduced by copy-paste.
 *
 * Only a literal is touched. translate($variable) is left alone: there is nothing to rewrite, and
 * the value is resolved at runtime.
 */
class LanguageCallSiteNormalize extends Command
{
    protected $signature = 'lang:normalize-call-sites
                            {--fix : Write the rewritten files instead of only reporting}
                            {--path= : Restrict to one directory below the project root}';

    protected $description = "Rewrite literal translate('...') keys as English source text without underscores";

    /**
     * `translate(` as a free function only. The negative lookbehind skips `$this->translate(` and
     * `self::translate(`, which are App\Traits\Processor::translate() — a different function
     * writing to a different file.
     */
    private const CALL_PATTERN = '/(?<!->)(?<!::)\btranslate\s*\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $roots = $this->option('path')
            ? [$this->option('path')]
            : ['resources/views', 'resources/themes', 'app', 'Modules', 'routes'];

        $filesChanged = 0;
        $callsRewritten = 0;
        $skipped = [];

        foreach ($this->phpFiles($roots) as $path) {
            $source = file_get_contents($path);
            if ($source === false || ! str_contains($source, 'translate(')) {
                continue;
            }

            $rewritten = 0;
            $updated = preg_replace_callback(
                self::CALL_PATTERN,
                function (array $match) use (&$rewritten, &$skipped, $path) {
                    [$whole, $quote, $raw] = $match;

                    $literal = $this->decode($raw, $quote);

                    if ($literal === null) {
                        $interpolated = $this->normalizeInterpolated($raw, $quote);
                        if ($interpolated === null) {
                            // Only worth a report if there is actually an underscore left behind.
                            if (str_contains($raw, '_')) {
                                $skipped[] = $path.'  '.$whole;
                            }

                            return $whole;
                        }
                        $rewritten++;

                        return 'translate("'.$interpolated.'"';
                    }

                    $canonical = normalizeTranslationKey($literal);
                    if ($canonical === '' || $canonical === $literal) {
                        return $whole;
                    }

                    $rewritten++;

                    return 'translate('.$this->encode($canonical);
                },
                $source
            );

            if ($rewritten === 0 || $updated === null || $updated === $source) {
                continue;
            }

            $filesChanged++;
            $callsRewritten += $rewritten;

            if ($fix) {
                file_put_contents($path, $updated, LOCK_EX);
            }

            if ($this->output->isVerbose()) {
                $this->line(sprintf('  %-90s %d', $this->relative($path), $rewritten));
            }
        }

        if (! empty($skipped)) {
            $this->newLine();
            $this->warn('Left alone — the literal cannot be decoded safely:');
            foreach (array_slice($skipped, 0, 20) as $one) {
                $this->line('    '.$one);
            }
            if (count($skipped) > 20) {
                $this->line('    ... and '.(count($skipped) - 20).' more');
            }
        }

        $this->newLine();
        $this->info($fix
            ? "Rewrote {$callsRewritten} call site(s) across {$filesChanged} file(s)."
            : "Would rewrite {$callsRewritten} call site(s) across {$filesChanged} file(s). Re-run with --fix to apply.");

        return self::SUCCESS;
    }

    /**
     * The PHP string a literal denotes, or null when it cannot be read without guessing.
     *
     * A single-quoted string has exactly two escapes. A double-quoted one has many, plus variable
     * interpolation — so it is only accepted when it contains none of the characters that would
     * make it mean something other than itself.
     */
    private function decode(string $raw, string $quote): ?string
    {
        if ($quote === "'") {
            return str_replace(['\\\\', "\\'"], ['\\', "'"], $raw);
        }

        if (preg_match('/[\\\\${]/', $raw)) {
            return null;
        }

        return $raw;
    }

    /**
     * A double-quoted literal that interpolates a variable — translate("The name in {$lang} is required").
     * The interpolation has to survive verbatim, so only the text between the tokens is converted;
     * the runtime key is the same one normalizeTranslationKey() would produce anyway.
     *
     * Returns null when the string carries a backslash escape, where "what it denotes" stops being
     * readable without evaluating it.
     */
    private function normalizeInterpolated(string $raw, string $quote): ?string
    {
        if ($quote !== '"' || str_contains($raw, '\\') || ! str_contains($raw, '$') || ! str_contains($raw, '_')) {
            return null;
        }

        $parts = preg_split(
            '/(\{\$[^}]*\}|\$[A-Za-z_]\w*(?:->[A-Za-z_]\w*|\[[^\]]*\])*)/',
            $raw,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $rebuilt = '';
        foreach ($parts as $index => $part) {
            $rebuilt .= $index % 2 === 1 ? $part : str_replace('_', ' ', $part);
        }

        return $rebuilt === $raw ? null : $rebuilt;
    }

    /** Always single-quoted: normalizeTranslationKey() has already removed any double quote. */
    private function encode(string $value): string
    {
        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $value)."'";
    }

    /** @return iterable<string> */
    private function phpFiles(array $roots): iterable
    {
        foreach ($roots as $dir) {
            $root = base_path($dir);
            if (! is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                $path = $file->getPathname();
                if (! $file->isFile() || ! str_ends_with($path, '.php')) {
                    continue;
                }
                // "vendor" is a domain word here, not only Composer's directory:
                // resources/views/layouts/vendor/ and vendor-views/ are the seller panel. Match
                // the real dependency roots by prefix instead of by any path segment.
                if (str_contains($path, '/node_modules/')
                    || str_starts_with($path, base_path('vendor').'/')
                    || str_starts_with($path, base_path('storage').'/')) {
                    continue;
                }
                yield $path;
            }
        }
    }

    private function relative(string $path): string
    {
        return str_replace(base_path().'/', '', $path);
    }
}
