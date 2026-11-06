<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncCampaignRename extends Command
{
    use ConfirmableTrait;

    protected $signature = 'db:sync-campaign-rename {--force : Skip the production confirmation}';

    protected $description = 'Drop the pre-rename b2b_campaign* tables and their stale migration records so migrate recreates them as campaigns / campaign_shops / campaign_shop_products';

    private const LEGACY_TABLES = [
        'b2b_campaign_shop_products',
        'b2b_campaign_shops',
        'b2b_campaigns',
    ];

    private const LEGACY_MIGRATIONS = [
        '2026_07_14_000000_create_b2b_campaigns_table',
        '2026_07_14_100000_create_b2b_campaign_shops_table',
        '2026_07_14_100100_create_b2b_campaign_shop_products_table',
        '2026_08_12_000000_drop_foreign_keys_from_b2b_campaign_tables',
    ];

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $droppedTables = $this->dropLegacyTables();
        $deletedRecords = $this->deleteLegacyMigrationRecords();

        if ($droppedTables === [] && $deletedRecords === 0) {
            $this->info('Already in sync — no legacy campaign tables or migration records found.');

            return self::SUCCESS;
        }

        foreach ($droppedTables as $tableName) {
            $this->line("Dropped table {$tableName}");
        }

        $this->line("Removed {$deletedRecords} stale migration record(s)");
        $this->info('Now run: php artisan migrate');

        return self::SUCCESS;
    }

    private function dropLegacyTables(): array
    {
        $droppedTables = [];

        Schema::disableForeignKeyConstraints();

        foreach (self::LEGACY_TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::drop($tableName);
            $droppedTables[] = $tableName;
        }

        Schema::enableForeignKeyConstraints();

        return $droppedTables;
    }

    private function deleteLegacyMigrationRecords(): int
    {
        return DB::table('migrations')
            ->whereIn('migration', self::LEGACY_MIGRATIONS)
            ->delete();
    }
}
