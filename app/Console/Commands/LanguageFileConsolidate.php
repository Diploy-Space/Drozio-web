<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Folds new-messages.php back into messages.php, one file per locale.
 *
 * new-messages.php is where translate() parks a key it has just met. Nothing ever moves it on, so
 * the file grows forever and accumulates two kinds of waste: rows whose key already exists in
 * messages.php — unreachable, because the lookup checks messages.php first — and rows that are
 * simply stranded in staging, which is why one locale can be hundreds of keys short of its
 * siblings while holding those same keys in the other file.
 *
 * A stranded row keeps its translation. A duplicate row is dropped, unless it is the only one of
 * the pair carrying a translation, in which case its value wins.
 */
class LanguageFileConsolidate extends Command
{
    protected $signature = 'lang:consolidate
                            {--fix : Write the consolidated files}
                            {--locale= : Restrict to a single locale directory}';

    protected $description = 'Fold new-messages.php into messages.php so each locale has one source file';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $only = $this->option('locale');
        $totalMoved = 0;
        $totalDropped = 0;
        $totalRescued = 0;

        foreach (glob(base_path('resources/lang/*'), GLOB_ONLYDIR) as $dir) {
            $locale = basename($dir);
            if ($only && $locale !== $only) {
                continue;
            }

            $messagesPath = "$dir/messages.php";
            $newPath = "$dir/new-messages.php";
            if (! is_file($messagesPath) || ! is_file($newPath)) {
                continue;
            }

            $messages = include $messagesPath;
            $new = include $newPath;
            if (! is_array($messages) || ! is_array($new)) {
                continue;
            }

            $moved = 0;
            $dropped = 0;
            $rescued = 0;

            foreach ($new as $key => $value) {
                $key = (string) $key;
                $value = (string) $value;

                if (! array_key_exists($key, $messages)) {
                    $messages[$key] = $value;
                    $moved++;

                    continue;
                }

                $dropped++;

                // The staged row is the only one that was ever translated — keep its value.
                if ($this->looksTranslated($key, $value) && ! $this->looksTranslated($key, (string) $messages[$key])) {
                    $messages[$key] = $value;
                    $rescued++;
                }
            }

            $totalMoved += $moved;
            $totalDropped += $dropped;
            $totalRescued += $rescued;

            $this->line(sprintf(
                '%-6s messages %5d -> %5d   moved %-5d dropped %-4d rescued %-3d %s',
                $locale, count($messages) - $moved, count($messages), $moved, $dropped, $rescued,
                $fix && ($moved || $dropped) ? '-> written' : ''
            ));

            if ($fix && ($moved || $dropped)) {
                $contents = "<?php\n\nreturn [\n";
                foreach ($messages as $key => $value) {
                    $contents .= '    '.exportLanguageFileEntry((string) $key, (string) $value);
                }
                file_put_contents($messagesPath, $contents."];\n", LOCK_EX);
                file_put_contents($newPath, "<?php\n\nreturn [\n];\n", LOCK_EX);
            }
        }

        $this->newLine();
        $this->info($fix
            ? "Consolidated: moved {$totalMoved} staged row(s), dropped {$totalDropped} unreachable duplicate(s), rescued {$totalRescued} translation(s)."
            : "Would move {$totalMoved} staged row(s) and drop {$totalDropped} unreachable duplicate(s), rescuing {$totalRescued} translation(s). Re-run with --fix to apply.");

        return self::SUCCESS;
    }

    private function looksTranslated(string $key, string $value): bool
    {
        if (trim($value) === '') {
            return false;
        }

        return trim($value) !== trim($key) && trim($value) !== trim(ucfirst($key));
    }
}
