<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('coupons', 'business_module')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->string('business_module', 20)->default(BusinessModule::RETAIL)->after('added_by');
            });
        }

        $existing = $this->existingIndexes();

        Schema::table('coupons', function (Blueprint $table) use ($existing): void {
            foreach ($this->indexes() as $name => $columns) {
                if (! in_array($name, $existing, true)) {
                    $table->index($columns, $name);
                }
            }
        });
    }

    public function down(): void
    {
        $existing = $this->existingIndexes();

        Schema::table('coupons', function (Blueprint $table) use ($existing): void {
            foreach (array_reverse(array_keys($this->indexes())) as $name) {
                if (in_array($name, $existing, true)) {
                    $table->dropIndex($name);
                }
            }
        });

        if (Schema::hasColumn('coupons', 'business_module')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->dropColumn('business_module');
            });
        }
    }

    private function indexes(): array
    {
        return [
            'coupons_scope_index' => ['business_module', 'added_by'],
            'idx_coupons_customer_scope' => ['business_module', 'status', 'customer_id', 'expire_date'],
            'idx_coupons_seller_scope' => ['business_module', 'status', 'seller_id', 'expire_date'],
            'idx_coupons_code_bearer_status' => ['code', 'coupon_bearer', 'status'],
            'idx_coupons_seller_id_created_at' => ['seller_id', 'created_at'],
        ];
    }

    private function existingIndexes(): array
    {
        return collect(DB::select('SHOW INDEX FROM `coupons`'))
            ->pluck('Key_name')
            ->unique()
            ->toArray();
    }
};
