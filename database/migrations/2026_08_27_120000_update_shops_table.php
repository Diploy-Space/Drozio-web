<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            if (! Schema::hasColumn('shops', 'wholesale_temporary_close')) {
                $table->tinyInteger('wholesale_temporary_close')->default(0)->after('temporary_close');
            }

            if (! Schema::hasColumn('shops', 'auction_temporary_close')) {
                $table->tinyInteger('auction_temporary_close')->default(0)->after('wholesale_temporary_close');
            }

            if (! Schema::hasColumn('shops', 'vacation_modules')) {
                $table->json('vacation_modules')->nullable()->after('vacation_status');
            }
        });
    }

    public function down(): void
    {
        foreach (['vacation_modules', 'auction_temporary_close', 'wholesale_temporary_close'] as $column) {
            if (Schema::hasColumn('shops', $column)) {
                Schema::table('shops', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
