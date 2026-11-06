<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('order_details', 'campaign_id')) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->after('seller_id');
            $table->index('campaign_id', 'order_details_campaign_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('order_details', 'campaign_id')) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndex('order_details_campaign_index');
            $table->dropColumn('campaign_id');
        });
    }
};
