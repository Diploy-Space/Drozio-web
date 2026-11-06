<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->removeDuplicateWallets();

        Schema::table('deliveryman_wallets', function (Blueprint $table): void {
            if (! $this->hasIndex('deliveryman_wallets', 'uniq_deliveryman_wallets_delivery_man_id')) {
                $table->unique('delivery_man_id', 'uniq_deliveryman_wallets_delivery_man_id');
            }
        });

        Schema::table('delivery_man_transactions', function (Blueprint $table): void {
            if (! $this->hasIndex('delivery_man_transactions', 'idx_dm_transactions_owner_type_created')) {
                $table->index(['delivery_man_id', 'transaction_type', 'created_at'], 'idx_dm_transactions_owner_type_created');
            }
        });

        Schema::table('withdraw_requests', function (Blueprint $table): void {
            if (! $this->hasIndex('withdraw_requests', 'idx_withdraw_requests_dm_approved_created')) {
                $table->index(['delivery_man_id', 'approved', 'created_at'], 'idx_withdraw_requests_dm_approved_created');
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (! $this->hasIndex('orders', 'idx_orders_delivery_man_payment_created')) {
                $table->index(['delivery_man_id', 'payment_status', 'created_at'], 'idx_orders_delivery_man_payment_created');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deliveryman_wallets', function (Blueprint $table): void {
            $table->dropUnique('uniq_deliveryman_wallets_delivery_man_id');
        });

        Schema::table('delivery_man_transactions', function (Blueprint $table): void {
            $table->dropIndex('idx_dm_transactions_owner_type_created');
        });

        Schema::table('withdraw_requests', function (Blueprint $table): void {
            $table->dropIndex('idx_withdraw_requests_dm_approved_created');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('idx_orders_delivery_man_payment_created');
        });
    }

    /**
     * The unique index cannot be added while duplicates exist. Keep the row holding the money —
     * highest current_balance, then the oldest — and fold nothing, since a second row is a bug
     * rather than a partial balance.
     */
    private function removeDuplicateWallets(): void
    {
        $duplicates = DB::table('deliveryman_wallets')
            ->select('delivery_man_id')
            ->groupBy('delivery_man_id')
            ->havingRaw('count(*) > 1')
            ->pluck('delivery_man_id');

        foreach ($duplicates as $deliveryManId) {
            $keepId = DB::table('deliveryman_wallets')
                ->where('delivery_man_id', $deliveryManId)
                ->orderByDesc('current_balance')
                ->orderBy('id')
                ->value('id');

            DB::table('deliveryman_wallets')
                ->where('delivery_man_id', $deliveryManId)
                ->where('id', '!=', $keepId)
                ->delete();
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $existing): bool => $existing['name'] === $index);
    }
};
