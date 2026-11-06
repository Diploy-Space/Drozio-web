<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_tax_setups')) {
            return;
        }

        if (! Schema::hasColumn('system_tax_setups', 'business_module')) {
            Schema::table('system_tax_setups', function (Blueprint $table) {
                $table->string('business_module', 20)
                    ->default(BusinessModule::RETAIL)
                    ->after('tax_payer')
                    ->index();
            });
        }

        DB::table('system_tax_setups')
            ->where('tax_payer', BusinessModule::WHOLESALE)
            ->update([
                'business_module' => BusinessModule::WHOLESALE,
                'tax_payer' => 'vendor',
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_tax_setups') || ! Schema::hasColumn('system_tax_setups', 'business_module')) {
            return;
        }

        DB::table('system_tax_setups')
            ->where('business_module', BusinessModule::WHOLESALE)
            ->update(['tax_payer' => BusinessModule::WHOLESALE]);

        Schema::table('system_tax_setups', function (Blueprint $table) {
            $table->dropIndex(['business_module']);
            $table->dropColumn('business_module');
        });
    }
};
