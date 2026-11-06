<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('chattings', 'business_module')) {
            return;
        }

        Schema::table('chattings', function (Blueprint $table) {
            $table->string('business_module', 20)
                ->default(BusinessModule::RETAIL)
                ->after('id');

            $table->index(
                ['business_module', 'user_id', 'seller_id'],
                'chattings_scope_user_seller_index'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('chattings', 'business_module')) {
            return;
        }

        Schema::table('chattings', function (Blueprint $table) {
            $table->dropIndex('chattings_scope_user_seller_index');
            $table->dropColumn('business_module');
        });
    }
};
