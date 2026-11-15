<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Courier\app\ValueObjects\CourierOwner;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('courier_shipments', 'business_module')) {
            return;
        }

        Schema::table('courier_shipments', function (Blueprint $table) {
            $table->string('business_module', 20)->default(CourierOwner::BUSINESS_MODULE_RETAIL)->after('owner_id');
            $table->index(['business_module', 'owner_type', 'owner_id'], 'courier_shipments_business_module_owner_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('courier_shipments', 'business_module')) {
            return;
        }

        Schema::table('courier_shipments', function (Blueprint $table) {
            $table->dropIndex('courier_shipments_business_module_owner_index');
            $table->dropColumn('business_module');
        });
    }
};
