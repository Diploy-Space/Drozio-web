<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LOOKUP_INDEX = 'taxables_owner_index';

    private const UNIQUE_INDEX = 'taxables_owner_tax_unique';

    private const DEFAULT_TAX_FOR = 'tax_for_orders';

    public function up(): void
    {
        if (! Schema::hasTable('taxables')) {
            return;
        }

        $this->normalizeTaxFor();
        $this->removeDuplicateAssignments();

        if (! $this->indexExists(self::LOOKUP_INDEX)) {
            Schema::table('taxables', function (Blueprint $table) {
                $table->index(['taxable_type', 'taxable_id'], self::LOOKUP_INDEX);
            });
        }

        if (! $this->indexExists(self::UNIQUE_INDEX)) {
            Schema::table('taxables', function (Blueprint $table) {
                $table->unique(['taxable_type', 'taxable_id', 'tax_id', 'tax_for'], self::UNIQUE_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('taxables')) {
            return;
        }

        Schema::table('taxables', function (Blueprint $table) {
            if ($this->indexExists(self::UNIQUE_INDEX)) {
                $table->dropUnique(self::UNIQUE_INDEX);
            }

            if ($this->indexExists(self::LOOKUP_INDEX)) {
                $table->dropIndex(self::LOOKUP_INDEX);
            }
        });
    }

    private function normalizeTaxFor(): void
    {
        DB::table('taxables')
            ->where(function ($query): void {
                $query->whereNull('tax_for')->orWhere('tax_for', '');
            })
            ->update(['tax_for' => self::DEFAULT_TAX_FOR]);
    }

    private function removeDuplicateAssignments(): void
    {
        DB::statement(<<<'SQL'
            DELETE duplicate FROM `taxables` duplicate
            INNER JOIN (
                SELECT MIN(`id`) AS surviving_id, `taxable_type`, `taxable_id`, `tax_id`, `tax_for`
                FROM `taxables`
                GROUP BY `taxable_type`, `taxable_id`, `tax_id`, `tax_for`
                HAVING COUNT(*) > 1
            ) surviving
                ON surviving.`taxable_type` = duplicate.`taxable_type`
               AND surviving.`taxable_id` = duplicate.`taxable_id`
               AND surviving.`tax_id` = duplicate.`tax_id`
               AND surviving.`tax_for` <=> duplicate.`tax_for`
            WHERE duplicate.`id` > surviving.`surviving_id`
        SQL);
    }

    private function indexExists(string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'taxables')
            ->where('index_name', $index)
            ->exists();
    }
};
