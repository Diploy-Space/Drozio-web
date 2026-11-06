<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_TABLE = 'auction_recent_views';

    private const TABLE = 'product_recent_views';

    private const LEGACY_COLUMN = 'auction_product_id';

    private const COLUMN = 'product_id';

    private const MODULE_INDEX = 'prv_user_module_updated_idx';

    public function up(): void
    {
        if (Schema::hasTable(self::LEGACY_TABLE) && ! Schema::hasTable(self::TABLE)) {
            Schema::rename(self::LEGACY_TABLE, self::TABLE);
        }

        if (! Schema::hasTable(self::TABLE)) {
            $this->createTable();

            return;
        }

        if (Schema::hasColumn(self::TABLE, self::LEGACY_COLUMN) && ! Schema::hasColumn(self::TABLE, self::COLUMN)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->renameColumn(self::LEGACY_COLUMN, self::COLUMN);
            });
        }

        if (Schema::hasColumn(self::TABLE, 'business_module')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->string('business_module', 20)->default(BusinessModule::RETAIL)->after(self::COLUMN);
            $table->index(['user_id', 'business_module', 'updated_at'], self::MODULE_INDEX);
        });

        DB::table(self::TABLE)->update(['business_module' => BusinessModule::AUCTION]);
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if (Schema::hasColumn(self::TABLE, 'business_module')) {
            DB::table(self::TABLE)->where('business_module', '!=', BusinessModule::AUCTION)->delete();

            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropIndex(self::MODULE_INDEX);
                $table->dropColumn('business_module');
            });
        }

        if (Schema::hasColumn(self::TABLE, self::COLUMN) && ! Schema::hasColumn(self::TABLE, self::LEGACY_COLUMN)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->renameColumn(self::COLUMN, self::LEGACY_COLUMN);
            });
        }

        if (! Schema::hasTable(self::LEGACY_TABLE)) {
            Schema::rename(self::TABLE, self::LEGACY_TABLE);
        }
    }

    private function createTable(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->integer(self::COLUMN)->nullable();
            $table->string('business_module', 20)->default(BusinessModule::RETAIL);
            $table->integer('user_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', self::COLUMN], 'arv_user_product_idx');
            $table->index([self::COLUMN], 'arv_product_idx');
            $table->index(['user_id', 'updated_at'], 'arv_user_updated_idx');
            $table->index(['user_id', 'business_module', 'updated_at'], self::MODULE_INDEX);
        });
    }
};
