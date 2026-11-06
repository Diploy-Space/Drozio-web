<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'business_module')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('business_module', 20)
                ->default(BusinessModule::RETAIL)
                ->after('id');

            $table->index(
                ['business_module', 'order_status', 'created_at'],
                'orders_scope_status_index'
            );

            $table->index(
                ['business_module', 'seller_id'],
                'orders_scope_seller_index'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'business_module')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_scope_status_index');
            $table->dropIndex('orders_scope_seller_index');
            $table->dropColumn('business_module');
        });
    }
};
