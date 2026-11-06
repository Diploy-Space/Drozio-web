<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('support_tickets', 'business_module')) {
            return;
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('business_module', 20)
                ->default(BusinessModule::RETAIL)
                ->after('id');

            $table->index(
                ['business_module', 'customer_id', 'status'],
                'support_tickets_scope_customer_status_index'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('support_tickets', 'business_module')) {
            return;
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex('support_tickets_scope_customer_status_index');
            $table->dropColumn('business_module');
        });
    }
};
