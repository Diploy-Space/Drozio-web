<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Applies a hand-written map of key rewrites: renames the key in every locale, carries its
 * translations across, and repoints every literal call site in one pass.
 *
 * Shortening copy cannot be done by rule — dropping words by pattern produces broken English — so
 * the map is authored by a person and this only applies it. The map is a PHP file returning
 * ['old source text' => 'new source text'].
 *
 * Translations are carried over untouched. The old translation still says the right thing at the
 * old length, so keeping it beats discarding it; a translator tightens it later.
 */
class LanguageKeyRename extends Command
{
    protected $signature = 'lang:rename
                            {--map= : PHP file returning [old => new]}
                            {--fix : Write the renamed files and rewrite call sites}
                            {--with-strings : Also rewrite the old text where it is a whole PHP string literal outside translate() — help text kept in provider/config arrays}';

    protected $description = 'Apply a hand-written map of translation key rewrites across the language files and call sites';

    private const CALL_PATTERN = '/(?<!->)(?<!::)\btranslate\s*\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/';

    private const LOCALE_FILES = ['messages.php', 'new-messages.php'];

    public function handle(): int
    {
        $mapPath = (string) $this->option('map');
        if ($mapPath === '' || ! is_file($mapPath)) {
            $this->error('Pass --map= pointing at a PHP file returning [old => new].');

            return self::FAILURE;
        }

        $map = include $mapPath;
        if (! is_array($map) || $map === []) {
            $this->error('The map file must return a non-empty array.');

            return self::FAILURE;
        }

        $fix = (bool) $this->option('fix');
        $english = $this->load('en');

        $missing = array_values(array_filter(array_keys($map), fn ($old) => ! array_key_exists($old, $english)));
        $collisions = array_values(array_filter($map, fn ($new) => array_key_exists($new, $english)));

        if ($missing !== []) {
            $this->warn(count($missing).' key(s) in the map are not in en/messages.php and will be skipped:');
            foreach (array_slice($missing, 0, 10) as $one) {
                $this->line('    '.$one);
            }
            $map = array_diff_key($map, array_flip($missing));
        }

        if ($collisions !== []) {
            $this->warn(count($collisions).' target(s) already exist and will be merged into:');
            foreach (array_slice($collisions, 0, 10) as $one) {
                $this->line('    '.$one);
            }
        }

        $renamedKeys = $this->renameInLanguageFiles($map, $fix);
        $rewrittenSites = $this->rewriteCallSites($map, $fix);
        $rewrittenStrings = $this->option('with-strings') ? $this->rewriteBareStrings($map, $fix) : 0;
        $this->reportStaleSourceText($map);

        $this->newLine();
        $this->info($fix
            ? "Renamed {$renamedKeys} key row(s) across the locales, repointed {$rewrittenSites} call site(s) and {$rewrittenStrings} bare string(s)."
            : "Would rename {$renamedKeys} key row(s), repoint {$rewrittenSites} call site(s) and {$rewrittenStrings} bare string(s). Re-run with --fix to apply.");

        return self::SUCCESS;
    }

    /** @param  array<string,string>  $map */
    private function renameInLanguageFiles(array $map, bool $fix): int
    {
        $renamed = 0;

        foreach ($this->locales() as $locale) {
            foreach (self::LOCALE_FILES as $file) {
                $path = base_path("resources/lang/$locale/$file");
                if (! is_file($path)) {
                    continue;
                }

                $messages = include $path;
                if (! is_array($messages)) {
                    continue;
                }

                // A rename whose target already exists is a merge, and the existing row may sit
                // either side of the renamed one in the file. Collect every candidate value per
                // surviving key first, then pick, so the outcome does not depend on file order.
                $updated = [];
                $candidates = [];
                $changed = 0;

                foreach ($messages as $key => $value) {
                    $key = (string) $key;
                    $target = $map[$key] ?? $key;

                    if (isset($map[$key])) {
                        $changed++;
                        // Where the value was only an echo of the old English, it has to become an
                        // echo of the new English instead.
                        $value = $this->looksTranslated($key, $value) ? $value : $target;
                    }

                    $updated[$target] = $updated[$target] ?? $value;
                    $candidates[$target][] = $value;
                }

                foreach ($candidates as $target => $values) {
                    foreach ($values as $value) {
                        if ($this->looksTranslated((string) $target, $value)) {
                            $updated[$target] = $value;
                            break;
                        }
                    }
                }

                $renamed += $changed;

                if ($fix && $changed > 0) {
                    $contents = "<?php\n\nreturn [\n";
                    foreach ($updated as $key => $value) {
                        $contents .= '    '.exportLanguageFileEntry((string) $key, (string) $value);
                    }
                    file_put_contents($path, $contents."];\n", LOCK_EX);
                }
            }
        }

        return $renamed;
    }

    /** @param  array<string,string>  $map */
    private function rewriteCallSites(array $map, bool $fix): int
    {
        $total = 0;

        foreach ($this->phpFiles() as $path) {
            $source = file_get_contents($path);
            if ($source === false || ! str_contains($source, 'translate(')) {
                continue;
            }

            $changed = 0;
            $updated = preg_replace_callback(self::CALL_PATTERN, function (array $match) use ($map, &$changed) {
                [$whole, $quote, $raw] = $match;

                if ($quote === '"' && preg_match('/[\\\\${]/', $raw)) {
                    return $whole;
                }

                $literal = $quote === "'"
                    ? str_replace(['\\\\', "\\'"], ['\\', "'"], $raw)
                    : $raw;

                if (! isset($map[$literal])) {
                    return $whole;
                }

                $changed++;

                return 'translate('.var_export($map[$literal], true);
            }, $source);

            if ($changed === 0 || $updated === null || $updated === $source) {
                continue;
            }

            $total += $changed;
            if ($fix) {
                file_put_contents($path, $updated, LOCK_EX);
            }
        }

        return $total;
    }

    /**
     * Rewrites the old text where it sits as a whole PHP string literal outside translate() — help
     * text kept in provider and config arrays, e.g. ['help' => 'The ID of the RedX pickup store…'],
     * which reaches the helper as translate($field['help']).
     *
     * Whole-literal only: a partial match could be a substring of unrelated copy, and rewriting a
     * fragment of a string is how a rename corrupts something it does not understand.
     *
     * @param  array<string,string>  $map
     */
    private function rewriteBareStrings(array $map, bool $fix): int
    {
        $total = 0;

        foreach ($this->phpFiles() as $path) {
            $source = file_get_contents($path);
            if ($source === false) {
                continue;
            }

            $changed = 0;
            $updated = $source;

            foreach ($map as $old => $new) {
                foreach (["'", '"'] as $quote) {
                    $escape = fn (string $text) => $quote === "'"
                        ? str_replace(['\\', "'"], ['\\\\', "\\'"], $text)
                        : str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $text);

                    $needle = $quote.$escape($old).$quote;
                    if (! str_contains($updated, $needle)) {
                        continue;
                    }
                    $updated = str_replace($needle, $quote.$escape($new).$quote, $updated, $count);
                    $changed += $count;
                }
            }

            if ($changed === 0 || $updated === $source) {
                continue;
            }

            $total += $changed;
            if ($fix) {
                file_put_contents($path, $updated, LOCK_EX);
            }
        }

        return $total;
    }

    /**
     * The check that a rename actually took: if the old text is still somewhere in the source, the
     * key was renamed out from under a live use and translate() will simply re-learn the old string
     * while the new key sits orphaned. Silent until someone reads the language file, so it is
     * reported loudly.
     *
     * @param  array<string,string>  $map
     */
    private function reportStaleSourceText(array $map): void
    {
        $corpus = '';
        foreach ($this->phpFiles() as $path) {
            $contents = file_get_contents($path);
            if ($contents !== false) {
                $corpus .= $contents."\n";
            }
        }

        $stale = [];
        foreach ($map as $old => $new) {
            if (str_contains($corpus, $old) && ! str_contains($corpus, $new)) {
                $stale[] = $old;
            }
        }

        if ($stale === []) {
            return;
        }

        $this->newLine();
        $this->warn(sprintf(
            '%d rewrite(s) left the old text in the source — those keys will be re-learned and the new ones orphaned.',
            count($stale)
        ));
        $this->line('Re-run with --with-strings, or update the source by hand:');
        foreach (array_slice($stale, 0, 10) as $one) {
            $this->line('    '.mb_substr($one, 0, 90));
        }
        if (count($stale) > 10) {
            $this->line('    ... and '.(count($stale) - 10).' more');
        }
    }

    private function looksTranslated(string $key, $value): bool
    {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        return trim($value) !== trim($key) && trim($value) !== trim(ucfirst($key));
    }

    /** @return array<string,mixed> */
    private function load(string $locale): array
    {
        $messages = [];
        foreach (self::LOCALE_FILES as $file) {
            $path = base_path("resources/lang/$locale/$file");
            if (is_file($path)) {
                $loaded = include $path;
                if (is_array($loaded)) {
                    $messages += $loaded;
                }
            }
        }

        return $messages;
    }

    /** @return array<int,string> */
    private function locales(): array
    {
        return array_map('basename', glob(base_path('resources/lang/*'), GLOB_ONLYDIR));
    }

    /** @return iterable<string> */
    private function phpFiles(): iterable
    {
        foreach (['resources/views', 'resources/themes', 'app', 'Modules', 'routes'] as $dir) {
            $root = base_path($dir);
            if (! is_dir($root)) {
                continue;
            }

            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
                $path = $file->getPathname();
                if (! $file->isFile() || ! str_ends_with($path, '.php')) {
                    continue;
                }
                if (str_contains($path, '/node_modules/')
                    || str_starts_with($path, base_path('vendor').'/')
                    || str_starts_with($path, base_path('storage').'/')) {
                    continue;
                }

                yield $path;
            }
        }
    }
}
