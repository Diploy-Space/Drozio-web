<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SHARED_DELIVERY_MAN_TOKEN = '6yIRXJRRfp78qJsAoKZZ6TTqhzuNJ3TcdvPBmk6n';

    private function indexes(): array
    {
        return [
            ['table' => 'phone_or_email_verifications', 'name' => 'idx_poev_phone_or_email', 'cols' => '`phone_or_email`'],
            ['table' => 'password_resets', 'name' => 'idx_password_resets_user_type_identity', 'cols' => '`user_type`, `identity`'],
            ['table' => 'delivery_men', 'name' => 'idx_delivery_men_phone', 'cols' => '`phone`'],
            ['table' => 'delivery_men', 'name' => 'idx_delivery_men_email', 'cols' => '`email`'],
            ['table' => 'sellers', 'name' => 'idx_sellers_phone', 'cols' => '`phone`'],
        ];
    }

    public function up(): void
    {
        foreach ($this->indexes() as $index) {
            if (! $this->indexExists($index['table'], $index['name'])) {
                DB::statement("ALTER TABLE `{$index['table']}` ADD KEY `{$index['name']}` ({$index['cols']})");
            }
        }

        $this->releaseSharedDeliveryManToken();
    }

    public function down(): void
    {
        foreach (array_reverse($this->indexes()) as $index) {
            if ($this->indexExists($index['table'], $index['name'])) {
                DB::statement("ALTER TABLE `{$index['table']}` DROP INDEX `{$index['name']}`");
            }
        }
    }

    private function releaseSharedDeliveryManToken(): void
    {
        if (! Schema::hasColumn('delivery_men', 'auth_token')) {
            return;
        }

        DB::statement('ALTER TABLE `delivery_men` MODIFY `auth_token` VARCHAR(191) NULL DEFAULT NULL');

        DB::table('delivery_men')
            ->where('auth_token', self::SHARED_DELIVERY_MAN_TOKEN)
            ->update(['auth_token' => null]);
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
