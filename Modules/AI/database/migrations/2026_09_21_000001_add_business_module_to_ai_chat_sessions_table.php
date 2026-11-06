<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ai_chat_sessions', 'business_module')) {
            return;
        }

        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->string('business_module', 20)
                ->default(BusinessModule::RETAIL)
                ->after('guest_id');

            $table->index(
                ['customer_id', 'business_module', 'last_activity_at'],
                'ai_chat_sessions_customer_scope_activity_index'
            );

            $table->index(
                ['guest_id', 'business_module', 'last_activity_at'],
                'ai_chat_sessions_guest_scope_activity_index'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ai_chat_sessions', 'business_module')) {
            return;
        }

        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->dropIndex('ai_chat_sessions_guest_scope_activity_index');
            $table->dropIndex('ai_chat_sessions_customer_scope_activity_index');
            $table->dropColumn('business_module');
        });
    }
};
