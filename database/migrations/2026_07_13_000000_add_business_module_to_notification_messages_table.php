<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_messages') && ! Schema::hasColumn('notification_messages', 'business_module')) {
            Schema::table('notification_messages', function (Blueprint $table) {
                $table->string('business_module', 20)->default('retail')->after('id');
                $table->index(['business_module', 'user_type', 'key'], 'notification_messages_scope_index');
            });

            DB::table('notification_messages')
                ->where('key', 'like', 'auction\_%')
                ->update(['business_module' => 'auction']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_messages') && Schema::hasColumn('notification_messages', 'business_module')) {
            Schema::table('notification_messages', function (Blueprint $table) {
                $table->dropIndex('notification_messages_scope_index');
                $table->dropColumn('business_module');
            });
        }
    }
};
