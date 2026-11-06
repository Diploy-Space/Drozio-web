<?php

namespace App\Services\New;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Locale;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ResourceBundle;
use stdClass;

class LanguageService
{
    public const SOURCE_LANGUAGE_CODE = 'en';

    public const DIRECTION_LTR = 'ltr';

    public const DIRECTION_RTL = 'rtl';

    public const STATUS_TRANSLATED = 'translated';

    public const STATUS_PENDING = 'pending';

    public const TRANSLATE_BATCH_SIZE = 200;

    public const REASON_KEY_ABSENT = 'key-absent';

    public const REASON_PROVIDER_UNAVAILABLE = 'provider-unavailable';

    private const LANGUAGE_DIRECTORY = 'resources/lang';

    private const MESSAGES_FILE = 'messages.php';

    private const NEW_MESSAGES_FILE = 'new-messages.php';

    private const FLAG_DIRECTORY = 'public/assets/front-end/img/flags';

    private const ADVANCED_SEARCH_DIRECTORY = 'json/admin/lang';

    private const SCRIPT_VARIANTS = ['Cyrl', 'Latn'];

    private const TRANSLATION_SCAN_DIRECTORIES = ['app', 'resources/views', 'routes', 'Modules', 'config', 'database'];

    private const TRANSLATION_BATCH_MAX_CHARACTERS = 4000;

    private const TRANSLATION_BATCH_MAX_KEYS = 50;

    /** @var array<string, string>|null */
    private static ?array $languageDisplayNames = null;

    /** @return Collection<int, array<string, mixed>> */
    public function getList(?string $searchValue = null, array $filters = []): Collection
    {
        return collect(getWebConfig(name: 'language') ?? [])
            ->filter(fn (array $language) => $this->matchesFilters($language, $filters))
            ->when($searchValue, fn (Collection $languages) => $languages->filter(
                fn (array $language) => str_contains(strtolower($language['name'] ?? ''), strtolower($searchValue))
                    || str_contains(strtolower($language['code'] ?? ''), strtolower($searchValue))
            ))
            ->values();
    }

    /** @return array<string, mixed>|null */
    public function find(mixed $id): ?array
    {
        return $this->getList()->firstWhere('code', $id);
    }

    /** @return array<string, mixed>|null */
    public function findBy(array $params): ?array
    {
        return $this->getList(filters: $params)->first();
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

    public function count(array $filters = []): int
    {
        return $this->getList(filters: $filters)->count();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $languages
     * @return array{languages: array<int, array<string, mixed>>, codes: array<int, string>}
     */
    public function create(array $data, array $languages): array
    {
        $code = $data['code'];
        $retained = [];
        $codes = [];

        foreach ($languages as $language) {
            if ($language['code'] === $code) {
                continue;
            }

            $retained[] = $language + ['default' => $language['code'] === self::SOURCE_LANGUAGE_CODE];
            $codes[] = $language['code'];
        }

        $codes[] = $code;

        $this->createLanguageFiles(code: $code);
        $this->copyAdvancedSearchKeyWords(code: $code);

        $retained[] = [
            'id' => $this->nextLanguageId(languages: $languages),
            'name' => $data['name'],
            'code' => $code,
            'direction' => $data['direction'] ?? self::DIRECTION_LTR,
            'status' => 0,
            'default' => false,
        ];

        return ['languages' => $retained, 'codes' => $codes];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $languages
     * @return array<int, array<string, mixed>>
     */
    public function update(mixed $id, array $data, array $languages): array
    {
        return $this->rebuild($languages, fn (array $language) => $language['code'] === $id
            ? ['name' => $data['name'], 'direction' => $data['direction'] ?? self::DIRECTION_LTR]
            : []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $languages
     * @return array<int, array<string, mixed>>
     */
    public function updateStatus(mixed $id, bool $status, array $languages): array
    {
        return $this->rebuild($languages, fn (array $language) => $language['code'] === $id
            ? ['status' => $status ? 1 : 0]
            : []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $languages
     * @return array<int, array<string, mixed>>
     */
    public function markAsDefault(mixed $id, array $languages): array
    {
        return $this->rebuild($languages, fn (array $language) => $language['code'] === $id
            ? ['status' => 1, 'default' => true]
            : ['default' => false]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $languages
     * @return array<int, array<string, mixed>>
     */
    public function delete(mixed $id, array $languages): array
    {
        $defaultRemoved = collect($languages)
            ->contains(fn (array $language) => $language['code'] === $id && ! empty($language['default']));

        $remaining = [];
        foreach ($languages as $language) {
            if ($language['code'] === $id) {
                continue;
            }

            $becomesDefault = $defaultRemoved && $language['code'] === self::SOURCE_LANGUAGE_CODE;
            $remaining[] = $this->toLanguageRow($language, $becomesDefault ? ['status' => 1, 'default' => true] : []);
        }

        $this->deleteLanguageFiles(code: $id);

        return $remaining;
    }

    /** @return array<string, array<string, mixed>> */
    public function getLocaleOptions(): array
    {
        $countries = collect(COUNTRIES);
        $availableCountries = [];

        foreach (File::files(base_path(self::FLAG_DIRECTORY)) as $flag) {
            $isoCode = strtoupper($flag->getFilenameWithoutExtension());
            $country = $countries->firstWhere('code', $isoCode);

            if ($country === null) {
                continue;
            }

            $availableCountries[] = [
                'name' => $country['name'],
                'iso2' => $country['code'],
                'language_code' => strtolower($isoCode),
                'flag' => dynamicAsset(path: self::FLAG_DIRECTORY.'/'.$flag->getFilename()),
            ];
        }

        return $this->mapCountriesToLocales(countries: $availableCountries);
    }

    public function getLanguageName(string $code): ?string
    {
        $code = strtolower(trim($code));

        if ($code === '') {
            return null;
        }

        $storedLanguage = $this->find(id: $code) ?? $this->findByLanguageCode(languageCode: $code);

        return $this->recognizedLanguageName(name: (string) ($storedLanguage['name'] ?? ''))
            ?? $this->languageDisplayName(language: $code)
            ?? $this->localeLanguageName(code: $code);
    }

    private function findByLanguageCode(string $languageCode): ?array
    {
        return $this->getList()->first(
            fn (array $language): bool => strtolower(getLanguageCode((string) $language['code'])) === $languageCode,
        );
    }

    /** @return array{total: int, translated: int, untranslated: int} */
    public function getTranslationStats(string $language): array
    {
        $messages = $this->loadMessages($this->languageFilePath($language, self::MESSAGES_FILE));
        $newMessages = $this->loadMessages($this->languageFilePath($language, self::NEW_MESSAGES_FILE));

        $translated = count($messages);
        $total = count($messages + $newMessages);

        return [
            'total' => $total,
            'translated' => $translated,
            'untranslated' => $total - $translated,
        ];
    }

    public function getPendingMessageCount(string $language): int
    {
        return count($this->loadMessages($this->languageFilePath($language, self::NEW_MESSAGES_FILE)));
    }

    public function getTranslatePaginatedList(
        string $language,
        ?string $searchValue = null,
        ?string $status = null,
        int $dataLimit = DEFAULT_DATA_LIMIT,
        int $page = 1,
    ): LengthAwarePaginator {
        $newMessages = $this->loadMessages($this->languageFilePath($language, self::NEW_MESSAGES_FILE));
        $translatedMessages = $this->loadMessages($this->languageFilePath($language, self::MESSAGES_FILE));
        ksort($newMessages);
        ksort($translatedMessages);

        $rows = [];
        foreach ([self::STATUS_PENDING => $newMessages, self::STATUS_TRANSLATED => $translatedMessages] as $rowStatus => $messages) {
            foreach ($messages as $key => $value) {
                $rows[] = [
                    'key' => $key,
                    'value' => $value,
                    'encode' => $key !== '' ? base64_encode($key) : '',
                    'status' => $rowStatus,
                ];
            }
        }

        if (in_array($status, [self::STATUS_TRANSLATED, self::STATUS_PENDING], true)) {
            $rows = array_values(array_filter($rows, fn (array $row) => $row['status'] === $status));
        }

        if ($searchValue !== null && $searchValue !== '') {
            $needle = mb_strtolower($searchValue);
            $rows = array_values(array_filter(
                $rows,
                fn (array $row) => str_contains(mb_strtolower($row['key']), $needle)
                    || str_contains(mb_strtolower((string) $row['value']), $needle),
            ));
        }

        return (new LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $dataLimit, $dataLimit),
            count($rows),
            $dataLimit,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        ))->appends(array_filter(['searchValue' => $searchValue, 'status' => $status]));
    }

    /** @return array{total: int, count: int, junk_count: int, keys: array<int, string>} */
    public function getUnusedTranslationKeys(string $language): array
    {
        $messages = $this->loadMessages($this->languageFilePath($language, self::MESSAGES_FILE));
        $newMessages = $this->loadMessages($this->languageFilePath($language, self::NEW_MESSAGES_FILE));
        $allKeys = array_keys($messages + $newMessages);

        $usedKeys = $this->collectUsedTranslationKeys();
        $sourceKeys = $this->loadMessages($this->languageFilePath(self::SOURCE_LANGUAGE_CODE, self::MESSAGES_FILE))
            + $this->loadMessages($this->languageFilePath(self::SOURCE_LANGUAGE_CODE, self::NEW_MESSAGES_FILE));

        $junkCount = 0;
        $candidateKeys = array_values(array_filter($allKeys, function (string $key) use ($usedKeys, $sourceKeys, &$junkCount) {
            if ($key === '') {
                return false;
            }

            if ($this->isJunkKey($key)) {
                $junkCount++;

                return true;
            }

            return ! isset($sourceKeys[$key]) && ! $this->isKeyUsed($key, $usedKeys);
        }));

        return [
            'total' => count($allKeys),
            'count' => count($candidateKeys),
            'junk_count' => $junkCount,
            'keys' => $candidateKeys,
        ];
    }

    /** @param  array<int, string>  $keys */
    public function removeUnusedTranslationKeysAcrossLocales(array $keys): int
    {
        $removed = 0;

        foreach (File::directories(base_path(self::LANGUAGE_DIRECTORY)) as $directory) {
            if (! File::exists($directory.'/'.self::MESSAGES_FILE)) {
                continue;
            }

            $removed += $this->removeUnusedTranslationKeys(language: basename($directory), keys: $keys);
        }

        return $removed;
    }

    /** @return array{languages: int, added: int, removed: int} */
    public function syncAllLanguagesWithMaster(): array
    {
        $master = $this->getMasterSyncKeys();
        $summary = ['languages' => 0, 'added' => 0, 'removed' => 0];

        foreach (File::directories(base_path(self::LANGUAGE_DIRECTORY)) as $directory) {
            $code = basename($directory);

            if ($code === self::SOURCE_LANGUAGE_CODE || ! File::exists($directory.'/'.self::MESSAGES_FILE)) {
                continue;
            }

            $result = $this->syncLanguageWithMaster(language: $code, master: $master);
            $summary['languages']++;
            $summary['added'] += $result['added'];
            $summary['removed'] += $result['removed'];
        }

        return $summary;
    }

    public function deleteTranslationKey(string $language, string $key): void
    {
        foreach ([self::MESSAGES_FILE, self::NEW_MESSAGES_FILE] as $file) {
            $path = $this->languageFilePath($language, $file);

            if (! File::exists($path)) {
                continue;
            }

            $messages = $this->loadMessages($path);

            if (! array_key_exists($key, $messages)) {
                continue;
            }

            unset($messages[$key]);
            $this->putLangArray($path, $messages);
        }
    }

    public function saveTranslation(string $language, string $key, string $value): bool
    {
        $value = removeSpecialCharacters($value);
        $messagesPath = $this->languageFilePath($language, self::MESSAGES_FILE);
        $translatedMessages = $this->loadMessages($messagesPath);

        if (array_key_exists($key, $translatedMessages)) {
            $normalized = [];
            foreach ($translatedMessages as $storedKey => $storedValue) {
                $normalized[removeSpecialCharacters(text: $storedKey)] = $storedValue;
            }
            $normalized[$key] = $value;

            $this->updateAdvancedSearchKeyWords(language: $language, key: $key, value: $value);
            $this->putLangArray($messagesPath, $normalized);

            return false;
        }

        if (array_key_exists($key, $this->loadMessages($this->languageFilePath($language, self::NEW_MESSAGES_FILE)))) {
            $this->promoteKeyToTranslated(language: $language, key: $key, value: $value);

            return true;
        }

        return false;
    }

    /** @return array{value: ?string, promoted: bool, reason: ?string} */
    public function translateKey(string $language, string $key): array
    {
        if ($key === '') {
            return ['value' => null, 'promoted' => false, 'reason' => self::REASON_KEY_ABSENT];
        }

        $translated = autoTranslator($key, self::SOURCE_LANGUAGE_CODE, getLanguageCode($language));

        if ($translated === null) {
            return ['value' => null, 'promoted' => false, 'reason' => self::REASON_PROVIDER_UNAVAILABLE];
        }

        $translated = removeSpecialCharacters($translated);
        $messagesPath = $this->languageFilePath($language, self::MESSAGES_FILE);
        $translatedMessages = $this->loadMessages($messagesPath);

        if (array_key_exists($key, $translatedMessages)) {
            $translatedMessages[$key] = $translated;
            $this->updateAdvancedSearchKeyWords(language: $language, key: $key, value: $translated);
            $this->putLangArray($messagesPath, $translatedMessages);

            return ['value' => $translated, 'promoted' => false, 'reason' => null];
        }

        if (array_key_exists($key, $this->loadMessages($this->languageFilePath($language, self::NEW_MESSAGES_FILE)))) {
            $this->promoteKeyToTranslated(language: $language, key: $key, value: $translated);

            return ['value' => $translated, 'promoted' => true, 'reason' => null];
        }

        return ['value' => null, 'promoted' => false, 'reason' => self::REASON_KEY_ABSENT];
    }

    /** @return array{status: int, reason: ?string, due: int, translated: int} */
    public function translateAllMessages(string $language, int $limit = self::TRANSLATE_BATCH_SIZE): array
    {
        $newMessagesPath = $this->languageFilePath($language, self::NEW_MESSAGES_FILE);
        $messagesPath = $this->languageFilePath($language, self::MESSAGES_FILE);
        $newMessages = $this->loadMessages($newMessagesPath);
        $translatedMessages = $this->loadMessages($messagesPath);
        ksort($newMessages);

        if ($newMessages === []) {
            return ['status' => 1, 'reason' => null, 'due' => 0, 'translated' => 0];
        }

        $pendingKeys = array_slice(array_keys($newMessages), 0, $limit);

        $providerFailed = false;
        $translations = $this->translateKeysBatched($pendingKeys, getLanguageCode($language), $providerFailed);

        $completedKeys = [];
        foreach ($pendingKeys as $key) {
            if (! isset($translations[$key]) || $translations[$key] === '') {
                continue;
            }

            $completedKeys[$key] = true;
            $this->updateAdvancedSearchKeyWords(language: $language, key: $key, value: $translations[$key]);
            $translatedMessages[$key] = $translations[$key];
        }

        if ($completedKeys !== []) {
            $this->putLangArray($newMessagesPath, array_diff_key($newMessages, $completedKeys));
            $this->putLangArray($messagesPath, $translatedMessages);
        }

        $due = $this->getPendingMessageCount(language: $language);

        return [
            'status' => $providerFailed ? 0 : 1,
            'reason' => $providerFailed ? self::REASON_PROVIDER_UNAVAILABLE : null,
            'due' => $due,
            'translated' => count($completedKeys),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $languages
     * @return array<int, array<string, mixed>>
     */
    private function rebuild(array $languages, callable $overrides): array
    {
        return array_map(fn (array $language) => $this->toLanguageRow($language, $overrides($language)), $languages);
    }

    /**
     * @param  array<string, mixed>  $language
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function toLanguageRow(array $language, array $overrides = []): array
    {
        return array_merge([
            'id' => $language['id'],
            'name' => $language['name'],
            'direction' => $language['direction'] ?? self::DIRECTION_LTR,
            'code' => $language['code'],
            'status' => $language['status'],
            'default' => $language['default'] ?? $language['code'] === self::SOURCE_LANGUAGE_CODE,
        ], $overrides);
    }

    private function recognizedLanguageName(string $name): ?string
    {
        $name = trim((string) preg_replace('/\s*\([^)]*\)/', '', $name));

        return $name === '' ? null : ($this->languageDisplayNames()[strtolower($name)] ?? null);
    }

    private function localeLanguageName(string $code): ?string
    {
        $suffix = '-'.strtoupper($code);
        $languages = [];

        foreach (getLanguageCodeList() as $locale) {
            if (str_ends_with($locale, $suffix)) {
                $languages[Locale::getPrimaryLanguage($locale)] = true;
            }
        }

        if (count($languages) !== 1) {
            return null;
        }

        return $this->languageDisplayName(language: (string) array_key_first($languages));
    }

    private function languageDisplayName(string $language): ?string
    {
        $displayName = Locale::getDisplayLanguage($language, self::SOURCE_LANGUAGE_CODE);

        return $displayName === '' || strcasecmp($displayName, $language) === 0 ? null : $displayName;
    }

    /** @return array<string, string> */
    private function languageDisplayNames(): array
    {
        if (self::$languageDisplayNames !== null) {
            return self::$languageDisplayNames;
        }

        $displayNames = [];

        foreach (ResourceBundle::getLocales('') as $locale) {
            $displayName = $this->languageDisplayName(language: (string) Locale::getPrimaryLanguage($locale));

            if ($displayName !== null) {
                $displayNames[strtolower($displayName)] = $displayName;
            }
        }

        return self::$languageDisplayNames = $displayNames;
    }

    /**
     * @param  array<string, mixed>  $language
     * @param  array<string, mixed>  $filters
     */
    private function matchesFilters(array $language, array $filters): bool
    {
        foreach ($filters as $column => $value) {
            $stored = $language[$column] ?? null;

            $matched = is_array($value) ? in_array($stored, $value) : $stored == $value;

            if (! $matched) {
                return false;
            }
        }

        return true;
    }

    /** @param  array<int, array<string, mixed>>  $languages */
    private function nextLanguageId(array $languages): int
    {
        $lastKey = array_key_last($languages);

        return $lastKey === null ? 1 : (int) $languages[$lastKey]['id'] + 1;
    }

    /**
     * @param  array<int, array<string, mixed>>  $countries
     * @return array<string, array<string, mixed>>
     */
    private function mapCountriesToLocales(array $countries): array
    {
        $locales = getLanguageCodeList();
        $mappedLocales = [];

        foreach ($countries as $country) {
            $isoCode = strtolower($country['iso2']);
            $matched = false;

            foreach ($locales as $locale) {
                $parts = explode('-', $locale);

                if (strtoupper($country['iso2']) !== strtoupper(end($parts))) {
                    continue;
                }

                $matched = true;

                if (count($parts) === 3 && in_array($parts[1], self::SCRIPT_VARIANTS, true)) {
                    foreach (self::SCRIPT_VARIANTS as $script) {
                        $mappedLocales[$isoCode.'-'.strtolower($script)] = $country + [
                            'locale' => $isoCode.'-'.strtolower($script),
                            'script' => $script,
                        ];
                    }

                    continue;
                }

                $mappedLocales[$isoCode] = $country + ['locale' => $isoCode];
            }

            if (! $matched) {
                $mappedLocales[$isoCode] = $country + ['locale' => $isoCode];
            }
        }

        return $mappedLocales;
    }

    private function createLanguageFiles(string $code): void
    {
        $directory = base_path(self::LANGUAGE_DIRECTORY.'/'.$code);
        File::ensureDirectoryExists($directory);

        $sourceMessages = array_merge(
            $this->loadMessages($this->languageFilePath(self::SOURCE_LANGUAGE_CODE, self::MESSAGES_FILE)),
            $this->loadMessages($this->languageFilePath(self::SOURCE_LANGUAGE_CODE, self::NEW_MESSAGES_FILE)),
        );

        $pendingMessages = [];
        foreach ($sourceMessages as $key => $value) {
            $pendingMessages[removeSpecialCharacters(text: $key)] = $value;
        }

        $this->putLangArray($directory.'/'.self::MESSAGES_FILE, []);
        $this->putLangArray($directory.'/'.self::NEW_MESSAGES_FILE, $pendingMessages);
    }

    private function deleteLanguageFiles(string $code): void
    {
        $directory = base_path(self::LANGUAGE_DIRECTORY.'/'.$code);

        if (File::isDirectory($directory)) {
            $entries = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($entries as $entry) {
                $entry->isDir() ? rmdir($entry->getRealPath()) : unlink($entry->getRealPath());
            }

            rmdir($directory);
        }

        File::delete($this->advancedSearchFilePath($code));
    }

    private function copyAdvancedSearchKeyWords(string $code): void
    {
        $target = $this->advancedSearchFilePath($code);

        if (File::exists($target)) {
            return;
        }

        File::ensureDirectoryExists(dirname($target));
        $source = $this->advancedSearchFilePath(self::SOURCE_LANGUAGE_CODE);

        File::put($target, File::exists($source) ? File::get($source) : json_encode(new stdClass, JSON_PRETTY_PRINT));
    }

    private function promoteKeyToTranslated(string $language, string $key, string $value): void
    {
        $newMessagesPath = $this->languageFilePath($language, self::NEW_MESSAGES_FILE);
        $messagesPath = $this->languageFilePath($language, self::MESSAGES_FILE);

        $newMessages = $this->loadMessages($newMessagesPath);
        unset($newMessages[$key]);
        $this->putLangArray($newMessagesPath, $newMessages);

        $translatedMessages = $this->loadMessages($messagesPath);
        $translatedMessages[$key] = $value;
        $this->putLangArray($messagesPath, $translatedMessages);

        $this->updateAdvancedSearchKeyWords(language: $language, key: $key, value: $value);
    }

    /** @param  array<int, string>  $keys */
    private function removeUnusedTranslationKeys(string $language, array $keys): int
    {
        $removableKeys = array_flip($keys);
        $removed = 0;

        foreach ([self::MESSAGES_FILE, self::NEW_MESSAGES_FILE] as $file) {
            $path = $this->languageFilePath($language, $file);

            if (! File::exists($path)) {
                continue;
            }

            $messages = $this->loadMessages($path);
            $retained = array_diff_key($messages, $removableKeys);
            $removed += count($messages) - count($retained);

            $this->putLangArray($path, $retained);
        }

        $this->pruneAdvancedSearchKeyWords(language: $language, keys: $keys);

        return $removed;
    }

    /** @return array<string, string> */
    private function getMasterSyncKeys(): array
    {
        $messages = array_merge(
            $this->loadMessages($this->languageFilePath(self::SOURCE_LANGUAGE_CODE, self::MESSAGES_FILE)),
            $this->loadMessages($this->languageFilePath(self::SOURCE_LANGUAGE_CODE, self::NEW_MESSAGES_FILE)),
        );

        $master = [];
        foreach ($messages as $key => $value) {
            if ($key === '' || $this->isJunkKey($key)) {
                continue;
            }

            $master[$key] = $value;
        }

        return $master;
    }

    /**
     * @param  array<string, string>  $master
     * @return array{added: int, removed: int}
     */
    private function syncLanguageWithMaster(string $language, array $master): array
    {
        $messagesPath = $this->languageFilePath($language, self::MESSAGES_FILE);
        $newMessagesPath = $this->languageFilePath($language, self::NEW_MESSAGES_FILE);
        $localeMessages = $this->loadMessages($messagesPath);
        $localeNewMessages = $this->loadMessages($newMessagesPath);

        $added = 0;
        $translated = [];
        $untranslated = [];

        foreach ($master as $key => $sourceValue) {
            if (array_key_exists($key, $localeMessages)) {
                $translated[$key] = $localeMessages[$key];

                continue;
            }

            if (array_key_exists($key, $localeNewMessages)) {
                $untranslated[$key] = $localeNewMessages[$key];

                continue;
            }

            $untranslated[$key] = $sourceValue;
            $added++;
        }

        $removed = count(array_diff_key($localeMessages + $localeNewMessages, $master));

        ksort($translated);
        ksort($untranslated);
        $this->putLangArray($messagesPath, $translated);
        $this->putLangArray($newMessagesPath, $untranslated);

        return ['added' => $added, 'removed' => $removed];
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, string>
     */
    private function translateKeysBatched(array $keys, string $targetLanguageCode, bool &$providerFailed = false): array
    {
        if ($targetLanguageCode === self::SOURCE_LANGUAGE_CODE) {
            return array_combine($keys, array_map(fn (string $key) => str_replace('_', ' ', $key), $keys));
        }

        $translations = [];

        foreach ($this->chunkKeysForTranslation($keys) as $batch) {
            $batchTranslations = $this->translateChunk($batch, $targetLanguageCode);

            if ($batchTranslations === null) {
                $providerFailed = true;

                break;
            }

            $translations += $batchTranslations;
        }

        return $translations;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<int, array<int, string>>
     */
    private function chunkKeysForTranslation(array $keys): array
    {
        $separatorLength = strlen(urlencode("\n"));

        $batches = [];
        $batch = [];
        $batchCharacters = 0;

        foreach ($keys as $key) {
            $length = strlen(urlencode(str_replace('_', ' ', $key))) + $separatorLength;

            $exceedsBatch = $batchCharacters + $length > self::TRANSLATION_BATCH_MAX_CHARACTERS
                || count($batch) >= self::TRANSLATION_BATCH_MAX_KEYS;

            if ($batch !== [] && $exceedsBatch) {
                $batches[] = $batch;
                $batch = [];
                $batchCharacters = 0;
            }

            $batch[] = $key;
            $batchCharacters += $length;
        }

        if ($batch !== []) {
            $batches[] = $batch;
        }

        return $batches;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, string>|null
     */
    private function translateChunk(array $keys, string $targetLanguageCode): ?array
    {
        if ($keys === []) {
            return [];
        }

        $sources = array_map(fn (string $key) => str_replace('_', ' ', $key), $keys);
        $translated = autoTranslatorBatchedMode(implode("\n", $sources), self::SOURCE_LANGUAGE_CODE, $targetLanguageCode);

        if ($translated === null) {
            return null;
        }

        $lines = $translated === '' ? [] : explode("\n", $translated);

        if (count($lines) === count($sources)) {
            $translations = [];
            foreach (array_values($keys) as $index => $key) {
                $translations[$key] = removeSpecialCharacters($lines[$index]);
            }

            return $translations;
        }

        if (count($keys) === 1) {
            $single = autoTranslator($sources[0], self::SOURCE_LANGUAGE_CODE, $targetLanguageCode);

            return $single === null ? null : [$keys[0] => removeSpecialCharacters($single)];
        }

        $middle = intdiv(count($keys), 2);
        $firstHalf = $this->translateChunk(array_slice($keys, 0, $middle), $targetLanguageCode);

        if ($firstHalf === null) {
            return null;
        }

        $secondHalf = $this->translateChunk(array_slice($keys, $middle), $targetLanguageCode);

        return $secondHalf === null ? null : $firstHalf + $secondHalf;
    }

    /** @param  array<string, bool>  $usedKeys */
    private function isKeyUsed(string $key, array $usedKeys): bool
    {
        return isset($usedKeys[$key]) || isset($usedKeys[str_replace(' ', '_', str_replace('"', '', $key))]);
    }

    private function isJunkKey(string $key): bool
    {
        $trimmed = trim($key);

        return match (true) {
            $trimmed === '' => true,
            is_numeric($trimmed) => true,
            (bool) preg_match('/^[\p{P}\p{S}\s]+$/u', $trimmed) => true,
            (bool) preg_match('/^[0-9][0-9.,\/%\s-]*%?$/', $trimmed) => true,
            (bool) preg_match('/^\+?[0-9][0-9\sxX+()-]{5,}$/', $trimmed) => true,
            default => (bool) preg_match('/^[a-f0-9]{8,}$/i', $trimmed) && (bool) preg_match('/[0-9]/', $trimmed),
        };
    }

    /** @return array<string, bool> */
    private function collectUsedTranslationKeys(): array
    {
        $pattern = '/(?:translate|__|trans|@lang)\s*\(\s*([\'"])(.*?)(?<!\\\\)\1/s';
        $usedKeys = [];

        foreach (self::TRANSLATION_SCAN_DIRECTORIES as $directory) {
            $path = base_path($directory);

            if (! File::isDirectory($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                if (! in_array($file->getExtension(), ['php', 'blade'], true) && ! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                if (! preg_match_all($pattern, File::get($file->getRealPath()), $matches)) {
                    continue;
                }

                foreach ($matches[2] as $literal) {
                    $literal = str_replace(['\\\'', '\\"'], ["'", '"'], $literal);
                    $normalizedKey = str_replace(' ', '_', str_replace('"', '', $literal));

                    if ($normalizedKey !== '') {
                        $usedKeys[$normalizedKey] = true;
                    }
                }
            }
        }

        return $usedKeys;
    }

    /** @param  array<int, string>  $keys */
    private function pruneAdvancedSearchKeyWords(string $language, array $keys): void
    {
        $path = $this->advancedSearchFilePath($language);

        if (! File::exists($path)) {
            return;
        }

        $entries = json_decode(File::get($path), true);

        if (! is_array($entries)) {
            return;
        }

        $removableKeys = array_flip($keys);
        $retained = array_values(array_filter($entries, function ($entry) use ($removableKeys) {
            $entryKey = is_array($entry) ? ($entry['key'] ?? null) : null;

            return $entryKey === null || ! isset($removableKeys[$entryKey]);
        }));

        File::put($path, json_encode($retained, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function updateAdvancedSearchKeyWords(string $language, string $key, string $value): void
    {
        if ($language === self::SOURCE_LANGUAGE_CODE) {
            return;
        }

        $path = $this->advancedSearchFilePath($language);
        $this->copyAdvancedSearchKeyWords(code: $language);

        if (! File::exists($path)) {
            return;
        }

        $normalizedKey = $this->normalizeSearchKeyWord($key);
        $content = File::get($path);

        $content = preg_replace_callback(
            '/\{\s*"key"\s*:\s*"[^"]*",\s*"page_title"\s*:\s*"([^"]+)",\s*"page_title_value"\s*:\s*"([^"]*)",\s*"keywords"\s*:\s*\{[^}]*\}\s*\}/',
            function (array $matches) use ($normalizedKey, $value) {
                if ($this->normalizeSearchKeyWord($matches[1]) !== $normalizedKey) {
                    return $matches[0];
                }

                return str_replace(
                    '"page_title_value": "'.$matches[2].'"',
                    '"page_title_value": "'.$value.'"',
                    $matches[0],
                );
            },
            $content,
        );

        $content = preg_replace_callback(
            '/"([^"]+)"\s*:\s*"([^"]*)"/',
            function (array $matches) use ($normalizedKey, $value) {
                $isPageTitleField = in_array($matches[1], ['page_title', 'page_title_value'], true);

                if ($isPageTitleField || $this->normalizeSearchKeyWord($matches[2]) !== $normalizedKey) {
                    return $matches[0];
                }

                return '"'.$matches[1].'": "'.$value.'"';
            },
            $content,
        );

        $entries = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            File::put($path, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    private function normalizeSearchKeyWord(string $value): string
    {
        return strtolower(preg_replace('/\s+/', ' ', str_replace(['_', '-', '.', ' '], ' ', $value)));
    }

    /** @param  array<string, mixed>  $messages */
    private function putLangArray(string $path, array $messages): void
    {
        $content = "<?php\n\nreturn [\n";
        foreach ($messages as $key => $value) {
            $content .= '    '.var_export($key, true).' => '.var_export($value, true).",\n";
        }
        $content .= "];\n";

        File::put($path, $content);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }

    /** @return array<string, mixed> */
    private function loadMessages(string $path): array
    {
        return File::exists($path) ? include $path : [];
    }

    private function languageFilePath(string $language, string $file): string
    {
        return base_path(self::LANGUAGE_DIRECTORY.'/'.$language.'/'.$file);
    }

    private function advancedSearchFilePath(string $language): string
    {
        return public_path(self::ADVANCED_SEARCH_DIRECTORY.'/'.$language.'.json');
    }
}
