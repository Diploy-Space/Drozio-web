<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Migrates the language files onto the key standard: the key IS the English source text, with real
 * spaces, because that is what the admin Language table shows a translator.
 *
 * Two things put underscores there. Genuine legacy snake_case keys ('3rd_party_setup'), and
 * translate() itself, which used to turn every space into an underscore before storing — so
 * translate('Active Vendors') landed on disk as 'Active_Vendors'. normalizeTranslationKey() now
 * collapses both spellings onto one form at lookup time, so renaming the keys on disk needs no
 * call-site edits: the same call that resolved 'Active_Vendors' yesterday resolves
 * 'Active vendors' today.
 *
 * Keys that collapse onto the same form are merged, keeping the variant that carries a real
 * translation — losing one is the single irreversible move in this migration.
 */
class LanguageKeyNormalize extends Command
{
    protected $signature = 'lang:normalize-keys
                            {--fix : Write the migrated files instead of only reporting}
                            {--locale= : Restrict to a single locale directory}';

    protected $description = 'Rewrite translation keys as English source text with spaces instead of underscores';

    public function handle(): int
    {
        $files = $this->targetFiles();

        if (empty($files)) {
            $this->error('No language files found under resources/lang.');

            return self::FAILURE;
        }

        $fix = (bool) $this->option('fix');
        $totalRenamed = 0;
        $totalMerged = 0;

        foreach ($files as $label => $path) {
            $original = include $path;
            if (! is_array($original)) {
                $this->warn("$label: not a PHP array, skipped.");

                continue;
            }

            $migrated = [];
            $renamed = 0;
            $merged = 0;

            foreach ($original as $key => $value) {
                $raw = (string) $key;
                $canonical = normalizeTranslationKey($raw);

                if ($canonical === '') {
                    continue;
                }

                $value = $this->migrateValue($raw, $canonical, $value);

                if ($canonical !== $raw) {
                    $renamed++;
                }

                if (! array_key_exists($canonical, $migrated)) {
                    $migrated[$canonical] = ['key' => $raw, 'value' => $value];

                    continue;
                }

                $merged++;
                if ($this->winner($canonical, $migrated[$canonical], ['key' => $raw, 'value' => $value])['key'] === $raw) {
                    $migrated[$canonical] = ['key' => $raw, 'value' => $value];
                }
            }

            $totalRenamed += $renamed;
            $totalMerged += $merged;

            $this->line(sprintf(
                '%-24s %6d keys -> %6d  renamed %-6d merged %-4d %s',
                $label,
                count($original),
                count($migrated),
                $renamed,
                $merged,
                $fix && $renamed ? '-> written' : ''
            ));

            if ($fix && $renamed) {
                $contents = "<?php\n\nreturn [\n";
                foreach ($migrated as $key => $entry) {
                    $contents .= '    '.exportLanguageFileEntry((string) $key, (string) $entry['value']);
                }
                file_put_contents($path, $contents."];\n", LOCK_EX);
            }
        }

        $this->newLine();
        $this->info($fix
            ? "Migrated: renamed {$totalRenamed} key(s), merged {$totalMerged} duplicate(s)."
            : "Would rename {$totalRenamed} key(s) and merge {$totalMerged} duplicate(s). Re-run with --fix to apply.");

        return self::SUCCESS;
    }

    /**
     * Values are carried across verbatim. Only a value that is unusable — empty, or the raw old
     * key echoed back with its underscores still in it — is regenerated.
     *
     * Rewriting anything else corrupts copy that reads correctly today: several keys are sentence
     * fragments a call site concatenates (' button', ' as the SMS Gateway'), and regenerating them
     * would capitalise the word and eat the leading space mid-sentence.
     */
    private function migrateValue(string $oldKey, string $canonical, $value): string
    {
        // The canonical key verbatim — no ucfirst, no punctuation stripping. Only `en` is
        // capitalised on the way out of translate(), so imposing it here would rewrite
        // 'copy@6amtech.com' to 'Copy@6amtech.com' in every other locale.
        if (! is_string($value) || trim($value) === '' || $value === $oldKey) {
            return $canonical;
        }

        return $value;
    }

    /**
     * False when the value is just the auto-generated echo of the key, which is what translate()
     * writes for a key nobody has translated yet.
     */
    private function looksTranslated(string $key, $value): bool
    {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        $auto = ucfirst(str_replace('_', ' ', removeSpecialCharacters($key)));

        return $value !== $key && trim($value) !== trim($auto);
    }

    /**
     * Of two entries colliding on one canonical key, a real translation always wins — losing one
     * is the only irreversible move here. Otherwise the entry whose key was already canonical wins
     * over its untrimmed twin ('Active_Vendors' over 'Active_Vendors_').
     *
     * @param  array{key: string, value: string}  $current
     * @param  array{key: string, value: string}  $candidate
     * @return array{key: string, value: string}
     */
    private function winner(string $canonical, array $current, array $candidate): array
    {
        $currentIsReal = $this->looksTranslated($current['key'], $current['value']);
        $candidateIsReal = $this->looksTranslated($candidate['key'], $candidate['value']);

        if ($currentIsReal !== $candidateIsReal) {
            return $candidateIsReal ? $candidate : $current;
        }

        $currentIsClean = $current['key'] === trim($current['key'], '_ ');
        $candidateIsClean = $candidate['key'] === trim($candidate['key'], '_ ');

        if ($currentIsClean !== $candidateIsClean) {
            return $candidateIsClean ? $candidate : $current;
        }

        return mb_strlen($candidate['value']) > mb_strlen($current['value']) ? $candidate : $current;
    }

    /** @return array<string,string> label => absolute path */
    private function targetFiles(): array
    {
        $only = $this->option('locale');
        $found = [];

        foreach (glob(base_path('resources/lang/*'), GLOB_ONLYDIR) as $dir) {
            $locale = basename($dir);
            if ($only && $locale !== $only) {
                continue;
            }

            foreach (['messages.php', 'new-messages.php'] as $file) {
                if (is_file("$dir/$file")) {
                    $found["$locale/$file"] = "$dir/$file";
                }
            }
        }

        return $found;
    }
}
