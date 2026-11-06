<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Removes language keys whose text appears nowhere in the project.
 *
 * A key does not have to be *called* to be live — it can be reached through a variable, a form
 * field name or a config value — so being absent from every translate('...') call is not enough to
 * delete it. The test is whether the text occurs anywhere in the source at all, which is why the
 * second pass reads whole files rather than parsing calls.
 *
 * Deliberately out of scope: vendored and minified bundles. A third-party library containing the
 * word "Install" would mark a key live for no reason, which turns the whole report into noise.
 *
 * Still not provable either way: a key reached only as translate($order->status), where the value
 * comes from the database and never appears in source. --keep-single-word leaves those short,
 * enum-looking keys alone.
 */
class LanguageOrphanPrune extends Command
{
    protected $signature = 'lang:prune-orphans
                            {--fix : Write the pruned language files}
                            {--keep-single-word : Keep one-word keys, which are the likeliest to arrive from a database value}
                            {--no-db-check : Skip the database cross-check (faster, but will delete keys reached only through translate($row->column))}
                            {--records : Also drop keys that are catalogue rows (category, brand or product names) with no literal call site}
                            {--show= : How many orphans to list (default 40)}';

    protected $description = 'Remove translation keys whose text appears nowhere in the project';

    private const CALL_PATTERN = '/(?<!->)(?<!::)\btranslate\s*\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/';

    private const LOCALE_FILES = ['messages.php', 'new-messages.php'];

    public function handle(): int
    {
        $english = $this->load('en');
        if ($english === []) {
            $this->error('resources/lang/en/messages.php is empty or unreadable.');

            return self::FAILURE;
        }

        $candidates = [];
        foreach (array_keys($english) as $key) {
            $candidates[(string) $key] = true;
        }

        $called = $this->literalCallSites();
        foreach (array_keys($called) as $key) {
            unset($candidates[$key]);
        }
        $this->line(sprintf('%d key(s), %d reached by a literal call, %d to check by content.',
            count($english), count($called), count($candidates)));

        $recordOrphans = $this->option('records') ? $this->catalogueRowKeys($called) : [];

        $candidates = $this->dropTextFoundAnywhere($candidates);

        $dynamic = $this->dynamicValueKeys();
        $reachedByValue = 0;
        foreach (array_keys($candidates) as $key) {
            if (isset($dynamic[$key])) {
                unset($candidates[$key]);
                $reachedByValue++;
            }
        }
        if ($reachedByValue > 0) {
            $this->line("Kept {$reachedByValue} key(s) a snake_case value in the source resolves to — reached through translate(\$row->column).");
        }

        if (! $this->option('no-db-check')) {
            $protected = $this->protectDatabaseValues($candidates);
            if ($protected > 0) {
                $this->line("Kept {$protected} key(s) that match a live database value — reached through translate(\$row->column).");
            }
        }

        if ($this->option('keep-single-word')) {
            $kept = 0;
            foreach (array_keys($candidates) as $key) {
                if (! str_contains(trim($key), ' ')) {
                    unset($candidates[$key]);
                    $kept++;
                }
            }
            $this->line("Kept {$kept} one-word key(s) that could arrive from a database value.");
        }

        // Merged after the protections on purpose: a catalogue row is dropped even though its text
        // appears in source, because that occurrence is a hardcoded demo menu rather than a use.
        if ($recordOrphans !== []) {
            $this->line(sprintf('%d catalogue row(s) (category, brand or product names) queued for removal.', count($recordOrphans)));
            $candidates += $recordOrphans;
        }

        $orphans = array_keys($candidates);
        sort($orphans);

        if ($orphans === []) {
            $this->info('No orphans found.');

            return self::SUCCESS;
        }

        $show = (int) ($this->option('show') ?: 40);
        foreach (array_slice($orphans, 0, $show) as $one) {
            $this->line('    '.mb_substr($one, 0, 100));
        }
        if (count($orphans) > $show) {
            $this->line('    ... and '.(count($orphans) - $show).' more');
        }

        $removed = $this->prune($orphans, (bool) $this->option('fix'));

        $this->newLine();
        $this->info($this->option('fix')
            ? sprintf('Pruned %d orphan key(s), %d row(s) across the locales.', count($orphans), $removed)
            : sprintf('Found %d orphan key(s) (%d row(s) across the locales). Re-run with --fix to apply.', count($orphans), $removed));

        return self::SUCCESS;
    }

    /** @return array<string,true> */
    private function literalCallSites(): array
    {
        $called = [];
        foreach ($this->projectFiles(['php']) as $path) {
            $source = file_get_contents($path);
            if ($source === false || ! str_contains($source, 'translate(')) {
                continue;
            }
            if (! preg_match_all(self::CALL_PATTERN, $source, $matches, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($matches as $match) {
                $literal = $match[1] === "'"
                    ? str_replace(['\\\\', "\\'"], ['\\', "'"], $match[2])
                    : $match[2];

                // Both spellings: the key on disk is the normalised form, so a call site written
                // translate('Removed successfully') reaches the stored key 'Successfully removed!'
                // and matching the raw literal alone would report that key as dead.
                $called[$literal] = true;
                $called[normalizeTranslationKey($literal)] = true;
            }
        }

        return $called;
    }

    /**
     * Keys a snake_case value in the source resolves to — 'ready_to_claim' reaches the stored key
     * 'Ready to claim'.
     *
     * These are the enum constants, status columns and array keys handed to translate($row->column).
     * The text of the key itself never appears in source, and the database cross-check only sees
     * the values some row happens to hold today, so a status no order is currently in would
     * otherwise look dead.
     *
     * @return array<string,true>
     */
    private function dynamicValueKeys(): array
    {
        $reached = [];
        foreach ($this->projectFiles(['php', 'js', 'json']) as $path) {
            $source = file_get_contents($path);
            if ($source === false) {
                continue;
            }
            if (! preg_match_all('/([\'"])([a-z][a-z0-9]*(?:_[a-z0-9]+)+)\1/', $source, $matches, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($matches as $match) {
                $reached[normalizeTranslationKey($match[2])] = true;
            }
        }

        return $reached;
    }

    /**
     * Drops every candidate whose text occurs anywhere in the project, testing one file against the
     * shrinking candidate set. Shrinking is what makes this quick: a fixed-string grep over the
     * same corpus with hundreds of patterns takes minutes.
     *
     * @param  array<string,true>  $candidates
     * @return array<string,true>
     */
    private function dropTextFoundAnywhere(array $candidates): array
    {
        foreach ($this->projectFiles(['php', 'js', 'json', 'vue', 'jsx', 'blade.php']) as $path) {
            if ($candidates === []) {
                break;
            }
            $source = file_get_contents($path);
            if ($source === false) {
                continue;
            }
            foreach (array_keys($candidates) as $key) {
                if ($key !== '' && str_contains($source, $key)) {
                    unset($candidates[$key]);
                }
            }
        }

        return $candidates;
    }

    /**
     * Columns whose values are handed straight to translate($row->column) somewhere in the app.
     * A key reached that way never appears in source, so content matching alone would call it dead.
     *
     * Only system enumerations belong here. `name` and `title` are deliberately absent: those hold
     * user-authored records — category names, product names, brands — which must never become
     * translation keys in the first place. Protecting them would keep every catalogue row alive in
     * the language file, which is the leak this whole cleanup exists to stop.
     */
    private const DYNAMIC_VALUE_COLUMNS = [
        'order_status', 'status', 'auction_current_status', 'input_name', 'type',
        'payment_method', 'payment_status', 'address_type', 'condition', 'item_condition',
        'transaction_type', 'product_type', 'login_medium', 'delivery_type', 'refund_status', 'module_type',
    ];

    /**
     * Removes from the orphan set any key that equals a value currently stored in one of those
     * columns. Returns how many were rescued.
     *
     * @param  array<string,true>  $candidates
     */
    private function protectDatabaseValues(array &$candidates): int
    {
        try {
            $schema = config('database.connections.'.config('database.default').'.database');
            $columns = DB::select(
                'SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND COLUMN_NAME IN ('
                .implode(',', array_fill(0, count(self::DYNAMIC_VALUE_COLUMNS), '?')).')',
                array_merge([$schema], self::DYNAMIC_VALUE_COLUMNS)
            );
        } catch (\Throwable $e) {
            $this->warn('Database unreachable — skipping the dynamic-value cross-check. Keys reached only through translate($row->column) may be reported as orphans.');

            return 0;
        }

        $live = [];
        foreach ($columns as $column) {
            try {
                $rows = DB::select("SELECT DISTINCT `{$column->c}` v FROM `{$column->t}` WHERE `{$column->c}` IS NOT NULL AND `{$column->c}` <> '' LIMIT 300");
            } catch (\Throwable $e) {
                continue;
            }
            foreach ($rows as $row) {
                if (is_string($row->v) && mb_strlen($row->v) <= 120) {
                    $live[mb_strtolower(trim(str_replace('_', ' ', $row->v)))] = true;
                }
            }
        }

        $rescued = 0;
        foreach (array_keys($candidates) as $key) {
            if (isset($live[mb_strtolower(trim($key))])) {
                unset($candidates[$key]);
                $rescued++;
            }
        }

        return $rescued;
    }

    /** @param  array<int,string>  $orphans */
    /**
     * Keys that are catalogue rows rather than copy — a category, brand or product name that leaked
     * into the language file through translate($category->name).
     *
     * These survive the ordinary orphan check because their text does appear in source: a demo
     * mega-menu or a filter panel hardcodes the same words. Requiring the absence of a literal
     * translate() call is what separates the leaked row from a genuine label that happens to match
     * one — "Size" and "green" are attribute rows and UI labels at the same time.
     *
     * @param  array<string,true>  $called
     * @return array<string,true>
     */
    private function catalogueRowKeys(array $called): array
    {
        $names = [];
        foreach (['categories', 'brands', 'products', 'attributes', 'units', 'colors', 'tags'] as $table) {
            foreach (['name', 'title'] as $column) {
                try {
                    foreach (DB::table($table)->distinct()->whereNotNull($column)->pluck($column) as $value) {
                        if (is_string($value) && trim($value) !== '') {
                            $names[mb_strtolower(trim($value))] = true;
                        }
                    }
                } catch (\Throwable $e) {
                    // table or column absent in this installation
                }
            }
        }

        // Gateway credential field names — sid, api_key, template_id — are the keys inside the
        // addon_settings value blobs. They reach the language file the same way a catalogue row
        // does, through translate($fieldName), and are just as much data as the rows themselves.
        try {
            foreach (DB::table('addon_settings')->pluck('live_values') as $blob) {
                $decoded = is_string($blob) ? json_decode($blob, true) : $blob;
                if (! is_array($decoded)) {
                    continue;
                }
                foreach (array_keys($decoded) as $field) {
                    $label = trim(str_replace('_', ' ', (string) $field));
                    if ($label !== '') {
                        $names[mb_strtolower($label)] = true;
                    }
                }
            }
        } catch (\Throwable $e) {
            // addon_settings absent in this installation
        }

        if ($names === []) {
            return [];
        }

        $found = [];
        foreach (array_keys($this->load('en')) as $key) {
            $key = (string) $key;
            if (isset($called[$key])) {
                continue;
            }
            if (isset($names[mb_strtolower(trim($key))])) {
                $found[$key] = true;
            }
        }

        return $found;
    }

    private function prune(array $orphans, bool $fix): int
    {
        $drop = array_flip($orphans);
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

                $kept = [];
                $changed = 0;
                foreach ($messages as $key => $value) {
                    if (isset($drop[(string) $key])) {
                        $changed++;

                        continue;
                    }
                    $kept[(string) $key] = $value;
                }

                $removed += $changed;

                if ($fix && $changed > 0) {
                    $contents = "<?php\n\nreturn [\n";
                    foreach ($kept as $key => $value) {
                        $contents .= '    '.exportLanguageFileEntry((string) $key, (string) $value);
                    }
                    file_put_contents($path, $contents."];\n", LOCK_EX);
                }
            }
        }

        return $removed;
    }

    /**
     * Every project-owned file with one of the given extensions. Vendored code, minified bundles
     * and the language files themselves are excluded — the last of those would make every key look
     * live, since it is sitting right there as a key.
     *
     * @param  array<int,string>  $extensions
     * @return iterable<string>
     */
    private function projectFiles(array $extensions): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path(), FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();

            if ($file->isDir()) {
                continue;
            }
            // Anchored to the real dependency roots. "vendor" is a domain word in this project —
            // resources/views/layouts/vendor/ and vendor-views/ are the seller panel, and matching
            // it as a path segment silently hides thousands of live call sites.
            if (str_starts_with($path, base_path('vendor').'/')
                || str_starts_with($path, base_path('storage').'/')
                || str_starts_with($path, base_path('resources/lang').'/')
                || preg_match('#/(json|lang)/[^/]*/?lang/#', $path)
                || str_contains($path, '/node_modules/')
                || str_contains($path, '/.git/')) {
                continue;
            }
            if (str_ends_with($path, '.min.js')) {
                continue;
            }

            foreach ($extensions as $extension) {
                if (str_ends_with($path, '.'.$extension)) {
                    yield $path;

                    continue 2;
                }
            }
        }
    }

    /** @return array<int,string> */
    private function locales(): array
    {
        return array_map('basename', glob(base_path('resources/lang/*'), GLOB_ONLYDIR));
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
}
