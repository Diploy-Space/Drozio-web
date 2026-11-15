<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Courier\app\ValueObjects\CourierOwner;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('courier_provider_settings', 'business_module')) {
            return;
        }

        Schema::table('courier_provider_settings', function (Blueprint $table) {
            $table->string('business_module', 20)->default(CourierOwner::BUSINESS_MODULE_RETAIL)->after('owner_id');
        });

        Schema::table('courier_provider_settings', function (Blueprint $table) {
            $table->dropUnique('courier_provider_settings_owner_provider_unique');
            $table->dropIndex('courier_provider_settings_owner_index');
            $table->unique(['owner_type', 'owner_id', 'business_module', 'provider'], 'courier_provider_settings_owner_provider_unique');
            $table->index(['owner_type', 'owner_id', 'business_module'], 'courier_provider_settings_owner_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('courier_provider_settings', 'business_module')) {
            return;
        }

        DB::table('courier_provider_settings')->where('business_module', '!=', CourierOwner::BUSINESS_MODULE_RETAIL)->delete();

        Schema::table('courier_provider_settings', function (Blueprint $table) {
            $table->dropUnique('courier_provider_settings_owner_provider_unique');
            $table->dropIndex('courier_provider_settings_owner_index');
            $table->unique(['owner_type', 'owner_id', 'provider'], 'courier_provider_settings_owner_provider_unique');
            $table->index(['owner_type', 'owner_id'], 'courier_provider_settings_owner_index');
        });

        Schema::table('courier_provider_settings', function (Blueprint $table) {
            $table->dropColumn('business_module');
        });
    }
};
