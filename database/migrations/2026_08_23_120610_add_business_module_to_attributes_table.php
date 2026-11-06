<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->string('business_module', 20)->default(BusinessModule::RETAIL)->after('id');
            $table->index(['business_module', 'name'], 'attributes_scope_index');
        });
    }

    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropIndex('attributes_scope_index');
            $table->dropColumn('business_module');
        });
    }
};
