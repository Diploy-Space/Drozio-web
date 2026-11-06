<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('business_module', 20)->default(BusinessModule::RETAIL)->after('theme');
            $table->index(['business_module', 'theme', 'banner_type'], 'banners_scope_index');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropIndex('banners_scope_index');
            $table->dropColumn('business_module');
        });
    }
};
