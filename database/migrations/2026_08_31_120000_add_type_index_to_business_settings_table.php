<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'idx_business_settings_type';

    private function getExistingIndexes(string $table): array
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->unique()
            ->toArray();
    }

    public function up(): void
    {
        $existing = $this->getExistingIndexes('business_settings');

        Schema::table('business_settings', function (Blueprint $table) use ($existing) {
            if (! in_array(self::INDEX, $existing)) {
                $table->index('type', self::INDEX);
            }
        });
    }

    public function down(): void
    {
        $existing = $this->getExistingIndexes('business_settings');

        Schema::table('business_settings', function (Blueprint $table) use ($existing) {
            if (in_array(self::INDEX, $existing)) {
                $table->dropIndex(self::INDEX);
            }
        });
    }
};
