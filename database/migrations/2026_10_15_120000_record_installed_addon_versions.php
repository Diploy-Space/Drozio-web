<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const RECORD_FILE = 'app/addon-installations.json';

    private const ADDON_INFO_PATTERN = 'Modules/*/Addon/info.php';

    public function up(): void
    {
        $recordFile = storage_path(self::RECORD_FILE);
        $decodedRecords = is_file($recordFile) ? json_decode((string) file_get_contents($recordFile), true) : null;
        $records = is_array($decodedRecords) ? $decodedRecords : [];

        foreach (glob(base_path(self::ADDON_INFO_PATTERN)) ?: [] as $infoPath) {
            $moduleName = basename(dirname($infoPath, 2));

            if (isset($records[$moduleName])) {
                continue;
            }

            $info = include $infoPath;

            if (! is_array($info)) {
                continue;
            }

            $records[$moduleName] = [
                'version' => isset($info['version']) ? (string) $info['version'] : null,
                'is_published' => (int) ($info['is_published'] ?? 0),
                'purchase_code' => $info['purchase_code'] ?? null,
                'username' => $info['username'] ?? null,
                'recorded_at' => now()->toDateTimeString(),
            ];
        }

        @file_put_contents($recordFile, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public function down(): void {}
};
