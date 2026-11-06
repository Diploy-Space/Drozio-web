<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('notifications', 'business_module')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('business_module', 20)->default(BusinessModule::RETAIL)->after('sent_to');
            $table->index(['business_module', 'sent_to', 'status'], 'notifications_scope_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_scope_index');
            $table->dropColumn('business_module');
        });
    }
};
