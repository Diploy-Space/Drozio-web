<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('carts', 'business_module')) {
            return;
        }

        Schema::table('carts', function (Blueprint $table) {
            $table->string('business_module', 20)->default(BusinessModule::RETAIL)->after('id');
            $table->unsignedBigInteger('campaign_id')->nullable()->after('business_module');
            $table->index(['business_module', 'customer_id'], 'carts_scope_customer_index');
            $table->index('campaign_id', 'carts_campaign_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('carts', 'business_module')) {
            return;
        }

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('carts_scope_customer_index');
            $table->dropIndex('carts_campaign_index');
            $table->dropColumn(['business_module', 'campaign_id']);
        });
    }
};
