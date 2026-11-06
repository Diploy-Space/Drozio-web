<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OWNER_INDEX = 'idx_shipping_addresses_customer_id_is_guest';

    public function up(): void
    {
        Schema::table('shipping_addresses', function (Blueprint $table): void {
            if (! $this->indexExists(self::OWNER_INDEX)) {
                $table->index(['customer_id', 'is_guest'], self::OWNER_INDEX);
            }

            if (! Schema::hasColumn('shipping_addresses', 'is_saved')) {
                $table->boolean('is_saved')->default(true)->after('is_billing');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipping_addresses', function (Blueprint $table): void {
            if ($this->indexExists(self::OWNER_INDEX)) {
                $table->dropIndex(self::OWNER_INDEX);
            }

            if (Schema::hasColumn('shipping_addresses', 'is_saved')) {
                $table->dropColumn('is_saved');
            }
        });
    }

    private function indexExists(string $index): bool
    {
        return collect(Schema::getIndexes('shipping_addresses'))->contains(fn (array $existing): bool => $existing['name'] === $index);
    }
};
