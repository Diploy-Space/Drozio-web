<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sellers', 'verified_notice_seen_at')) {
            Schema::table('sellers', function (Blueprint $table): void {
                $table->timestamp('verified_notice_seen_at')->nullable()->after('verified_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sellers', 'verified_notice_seen_at')) {
            Schema::table('sellers', function (Blueprint $table): void {
                $table->dropColumn('verified_notice_seen_at');
            });
        }
    }
};
