<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a Retail/Wholesale discriminator to the shared shipping tables so admin
 * Retail and admin Wholesale data can coexist in the same tables without
 * colliding. Existing rows default to 'retail', preserving current behaviour.
 */
return new class extends Migration {
    private array $tables = ['shipping_methods', 'shipping_types', 'category_shipping_costs'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'context')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('context', 20)->default('retail')->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'context')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('context');
                });
            }
        }
    }
};
