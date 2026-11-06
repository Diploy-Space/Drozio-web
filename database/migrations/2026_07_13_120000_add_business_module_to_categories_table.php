<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('business_module', 20)->default(BusinessModule::RETAIL)->after('id');
            $table->index(['business_module', 'position', 'parent_id'], 'categories_scope_index');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_scope_index');
            $table->dropColumn('business_module');
        });
    }
};
