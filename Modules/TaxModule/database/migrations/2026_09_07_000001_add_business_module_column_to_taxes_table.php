<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('taxes') || Schema::hasColumn('taxes', 'business_module')) {
            return;
        }

        Schema::table('taxes', function (Blueprint $table) {
            $table->string('business_module', 20)
                ->default(BusinessModule::RETAIL)
                ->after('country_code')
                ->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('taxes') || ! Schema::hasColumn('taxes', 'business_module')) {
            return;
        }

        Schema::table('taxes', function (Blueprint $table) {
            $table->dropIndex(['business_module']);
            $table->dropColumn('business_module');
        });
    }
};
