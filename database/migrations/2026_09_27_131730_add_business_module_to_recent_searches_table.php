<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('recent_searches', 'business_module')) {
            Schema::table('recent_searches', function (Blueprint $table) {
                $table->string('business_module', 20)->nullable()->index()->after('user_type');
            });
        }

        DB::table('recent_searches')->delete();
    }

    public function down(): void
    {
        if (Schema::hasColumn('recent_searches', 'business_module')) {
            Schema::table('recent_searches', function (Blueprint $table) {
                $table->dropIndex(['business_module']);
                $table->dropColumn('business_module');
            });
        }
    }
};
