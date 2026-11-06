<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Folds keys that say the same thing onto one, so the same sentence is not translated three times
 * under three spellings: 'Action' / 'action', 'Add fund to wallet' / 'add fund to wallet'.
 *
 * Case is the only fold that is safe by default. translate() applies ucfirst() to the English
 * result, so 'action' and 'Action' already render identically in en — folding them changes nothing
 * on screen and gives every other locale a single row to translate. Punctuation is NOT folded
 * unless --punctuation is passed: 'Discount' and 'Discount (%)' are different labels, and
 * 'Delivered' and 'Delivered!' read differently.
 *
 * A key is only deleted when every one of its uses can be seen and rewritten — that is, it has at
 * least one literal call site and no chance of arriving from translate($variable). A variant with
 * no literal call site may be reached dynamically ($order->status), so it is kept and merely
 * pointed at the surviving translation.
 */
class LanguageDuplicateMerge extends Command
{
    protected $signature = 'lang:merge-duplicates
                            {--fix : Write the merged files and rewrite call sites}
                            {--punctuation : Also fold keys that differ only by trailing punctuation}
                            {--similar : Also fold keys that differ only by punctuation anywhere (In-Stock / In Stock)}
                            {--review : List the near-duplicates that are never merged automatically}';

    protected $description = 'Merge translation keys that differ only by case, and point every call site at one';

    private const CALL_PATTERN = '/(?<!->)(?<!::)\btranslate\s*\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/';

    private const LOCALE_FILES = ['messages.php', 'new-messages.php'];

    /** @var array<string,int> literal => number of call sites */
    private array $sites = [];

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $this->sites = $this->callSiteCounts();

        $english = $this->load('en');
        if ($english === []) {
            $this->error('resources/lang/en/messages.php is empty or unreadable.');

            return self::FAILURE;
        }

        if ($this->option('review')) {
            $this->reportNearDuplicates(array_keys($english));

            return self::SUCCESS;
        }

        $groups = $this->duplicateGroups(array_keys($english));
        if ($groups === []) {
            $this->info('No duplicate keys found.');

            return self::SUCCESS;
        }

        [$rename, $keepAligned] = $this->planMerges($groups);

        $this->line(sprintf(
            '%d duplicate group(s): %d key(s) removable, %d kept but aligned (reachable only through a variable).',
            count($groups),
            count($rename),
            count($keepAligned)
        ));

        foreach (array_slice($groups, 0, 12) as $canonical => $group) {
            $others = array_values(array_diff($group, [$canonical]));
            $this->line(sprintf('    %-46s <= %s', $canonical, implode(' | ', $others)));
        }
        if (count($groups) > 12) {
            $this->line('    ... and '.(count($groups) - 12).' more');
        }

        $rewritten = $this->rewriteCallSites($rename, $fix);
        $this->line(sprintf('    %s %d call site(s) onto the surviving key.', $fix ? 'Pointed' : 'Would point', $rewritten));

        $removed = $this->mergeLanguageFiles($groups, $rename, $keepAligned, $fix);

        $this->newLine();
        $this->info($fix
            ? "Merged: removed {$removed} key(s) across the locales and repointed {$rewritten} call site(s)."
            : "Would remove {$removed} key(s) and repoint {$rewritten} call site(s). Re-run with --fix to apply.");

        return self::SUCCESS;
    }

    /**
     * Duplicate groups keyed by the variant that should survive.
     *
     * @param  array<int,string>  $keys
     * @return array<string,array<int,string>>
     */
    private function duplicateGroups(array $keys): array
    {
        $buckets = [];
        foreach ($keys as $key) {
            $buckets[$this->foldedForm((string) $key)][] = (string) $key;
        }

        $groups = [];
        foreach ($buckets as $variants) {
            if (count($variants) < 2 || ! $this->isSameLabel($variants)) {
                continue;
            }
            $groups[$this->canonical($variants)] = $variants;
        }

        return $groups;
    }

    private function foldedForm(string $key): string
    {
        $folded = mb_strtolower($key);

        if ($this->option('similar')) {
            return trim(preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $folded)));
        }

        return $this->option('punctuation') ? rtrim($folded, ' .!:;,') : $folded;
    }

    /**
     * Whether the variants in a bucket really are one label spelled differently.
     *
     * Under --similar the fold throws punctuation away, and punctuation is sometimes the whole
     * meaning: 'Discount' and 'Discount (%)' collapse to the same folded form but name different
     * fields, as do 'Discount Limit' and 'Discount Limit(%)'. A bracket, a percent or currency
     * sign, or a different set of numbers is a difference in what the label says — not in how it
     * is written — so the bucket is left alone.
     *
     * @param  array<int,string>  $variants
     */
    private function isSameLabel(array $variants): bool
    {
        $signature = null;

        foreach ($variants as $variant) {
            preg_match_all('/\d+/', $variant, $numbers);
            sort($numbers[0]);

            $current = [
                'brackets' => (bool) preg_match('/[(){}\[\]]/', $variant),
                'measure' => (bool) preg_match('/[%$€£¥₹৳]/u', $variant),
                'numbers' => $numbers[0],
            ];

            if ($signature === null) {
                $signature = $current;

                continue;
            }
            if ($signature !== $current) {
                return false;
            }
        }

        return true;
    }

    /**
     * The spelling to keep: sentence case first, then the most-used, then the shortest. Chosen from
     * among the spellings that already exist — never synthesised, because English grammar does not
     * survive substitution.
     *
     * Deliberately blind to which variant carries translations: bestValue() moves those onto the
     * survivor whichever one wins, so letting them vote here would keep '3rd-party storage' over
     * '3rd Party Storage' purely because it happened to be translated first.
     *
     * @param  array<int,string>  $variants
     */
    private function canonical(array $variants): string
    {
        $scored = [];
        foreach ($variants as $variant) {
            $scored[$variant] = [
                preg_match('/^\p{Lu}/u', $variant) ? 0 : 1,
                -($this->sites[$variant] ?? 0),
                mb_strlen($variant),
                $variant,
            ];
        }
        uasort($scored, fn ($a, $b) => $a <=> $b);

        return (string) array_key_first($scored);
    }

    /**
     * Splits each group into the variants that can be deleted and those that must stay.
     *
     * @param  array<string,array<int,string>>  $groups
     * @return array{0: array<string,string>, 1: array<string,string>} [dropped => canonical, kept => canonical]
     */
    private function planMerges(array $groups): array
    {
        $rename = [];
        $keepAligned = [];

        foreach ($groups as $canonical => $variants) {
            foreach ($variants as $variant) {
                if ($variant === $canonical) {
                    continue;
                }
                if (($this->sites[$variant] ?? 0) > 0) {
                    $rename[$variant] = $canonical;

                    continue;
                }
                $keepAligned[$variant] = $canonical;
            }
        }

        return [$rename, $keepAligned];
    }

    /** @param  array<string,string>  $rename */
    private function rewriteCallSites(array $rename, bool $fix): int
    {
        if ($rename === []) {
            return 0;
        }

        $total = 0;
        foreach ($this->phpFiles() as $path) {
            $source = file_get_contents($path);
            if ($source === false || ! str_contains($source, 'translate(')) {
                continue;
            }

            $changed = 0;
            $updated = preg_replace_callback(
                self::CALL_PATTERN,
                function (array $match) use ($rename, &$changed) {
                    [$whole, $quote, $raw] = $match;

                    // A double-quoted literal counts too — several live in Blade attributes,
                    // translate('Cancel') — but only when it denotes itself, with no escape or
                    // interpolation to evaluate.
                    if ($quote === '"' && preg_match('/[\\\\${]/', $raw)) {
                        return $whole;
                    }

                    $literal = $quote === "'"
                        ? str_replace(['\\\\', "\\'"], ['\\', "'"], $raw)
                        : $raw;

                    if (! isset($rename[$literal])) {
                        return $whole;
                    }

                    $changed++;

                    return 'translate('.var_export($rename[$literal], true);
                },
                $source
            );

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
     * @param  array<string,array<int,string>>  $groups
     * @param  array<string,string>  $rename
     * @param  array<string,string>  $keepAligned
     */
    private function mergeLanguageFiles(array $groups, array $rename, array $keepAligned, bool $fix): int
    {
        $removed = 0;

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

                $survivorValue = [];
                foreach ($groups as $canonical => $variants) {
                    $survivorValue[$canonical] = $this->bestValue($canonical, $variants, $messages);
                }

                $merged = [];
                foreach ($messages as $key => $value) {
                    $key = (string) $key;

                    if (isset($rename[$key])) {
                        $removed++;

                        continue;
                    }

                    if (isset($keepAligned[$key])) {
                        $canonical = $keepAligned[$key];
                        $merged[$key] = $survivorValue[$canonical] ?? $value;

                        continue;
                    }

                    $merged[$key] = $survivorValue[$key] ?? $value;
                }

                if ($fix) {
                    $contents = "<?php\n\nreturn [\n";
                    foreach ($merged as $key => $value) {
                        $contents .= '    '.exportLanguageFileEntry((string) $key, (string) $value);
                    }
                    file_put_contents($path, $contents."];\n", LOCK_EX);
                }
            }
        }

        return $removed;
    }

    /**
     * The translation to keep for a merged group in one locale: a real translation beats the
     * auto-generated echo of a key, and the longest real one beats a truncated twin.
     *
     * When no variant carries a translation the values are all echoes of their own key, and the
     * longest is the wrong tie-break — it leaves the surviving 'Delivered' rendering 'Delivered!'
     * off its dropped twin. The canonical spelling wins that case.
     *
     * @param  array<int,string>  $variants
     * @param  array<string,mixed>  $messages
     */
    private function bestValue(string $canonical, array $variants, array $messages): string
    {
        $best = (string) ($messages[$canonical] ?? $canonical);
        $bestIsReal = $this->looksTranslated($canonical, $best);

        if (! $bestIsReal) {
            foreach ($variants as $variant) {
                if (array_key_exists($variant, $messages) && $this->looksTranslated($variant, $messages[$variant])) {
                    return (string) $messages[$variant];
                }
            }

            return $canonical;
        }

        foreach ($variants as $variant) {
            if (! array_key_exists($variant, $messages)) {
                continue;
            }
            $value = (string) $messages[$variant];
            $isReal = $this->looksTranslated($variant, $value);

            if ($isReal && ! $bestIsReal) {
                $best = $value;
                $bestIsReal = true;

                continue;
            }
            if ($isReal === $bestIsReal && mb_strlen($value) > mb_strlen($best)) {
                $best = $value;
            }
        }

        return $best;
    }

    private function looksTranslated(string $key, $value): bool
    {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        return trim($value) !== trim($key) && trim($value) !== trim(ucfirst($key));
    }

    /** @return array<string,int> */
    private function callSiteCounts(): array
    {
        $counts = [];
        foreach ($this->phpFiles() as $path) {
            $source = file_get_contents($path);
            if ($source === false || ! str_contains($source, 'translate(')) {
                continue;
            }
            if (! preg_match_all(self::CALL_PATTERN, $source, $matches, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($matches as $match) {
                $literal = str_replace(['\\\\', "\\'"], ['\\', "'"], $match[2]);
                $counts[$literal] = ($counts[$literal] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /** @param  array<int,string>  $keys */
    private function reportNearDuplicates(array $keys): void
    {
        $buckets = [];
        foreach ($keys as $key) {
            $stripped = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', (string) $key));
            $buckets[trim(preg_replace('/\s+/', ' ', $stripped))][] = (string) $key;
        }

        $shown = 0;
        $this->warn('Near-duplicates — punctuation or wording differs, so these are never merged automatically:');
        foreach ($buckets as $variants) {
            if (count($variants) < 2 || count(array_unique(array_map('mb_strtolower', $variants))) < 2) {
                continue;
            }
            $this->line('    '.implode('   |   ', $variants));
            if (++$shown >= 60) {
                $this->line('    ...');
                break;
            }
        }
    }

    /** @return array<int,string> */
    private function locales(): array
    {
        static $locales = null;
        if ($locales !== null) {
            return $locales;
        }

        $locales = [];
        foreach (glob(base_path('resources/lang/*'), GLOB_ONLYDIR) as $dir) {
            $locales[] = basename($dir);
        }

        return $locales;
    }

    /** @return array<string,mixed> */
    private function load(string $locale): array
    {
        static $cache = [];
        if (isset($cache[$locale])) {
            return $cache[$locale];
        }

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

        return $cache[$locale] = $messages;
    }

    /** @return iterable<string> */
    private function phpFiles(): iterable
    {
        foreach (['resources/views', 'resources/themes', 'app', 'Modules', 'routes'] as $dir) {
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
