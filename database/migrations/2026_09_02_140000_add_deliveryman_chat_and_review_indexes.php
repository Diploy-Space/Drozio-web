<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chattings', function (Blueprint $table): void {
            foreach ($this->deliverymanThreadIndexes() as $name => $columns) {
                if (! $this->hasIndex('chattings', $name)) {
                    $table->index($columns, $name);
                }
            }
        });

        Schema::table('deliveryman_notifications', function (Blueprint $table): void {
            if (! $this->hasIndex('deliveryman_notifications', 'idx_deliveryman_notifications_owner')) {
                $table->index(['delivery_man_id', 'id'], 'idx_deliveryman_notifications_owner');
            }
        });

        Schema::table('reviews', function (Blueprint $table): void {
            if (! $this->hasIndex('reviews', 'idx_reviews_delivery_man_saved_updated')) {
                $table->index(['delivery_man_id', 'is_saved', 'updated_at'], 'idx_reviews_delivery_man_saved_updated');
            }
        });

        Schema::table('delivery_histories', function (Blueprint $table): void {
            if (! $this->hasIndex('delivery_histories', 'idx_delivery_histories_order_deliveryman')) {
                $table->index(['order_id', 'deliveryman_id'], 'idx_delivery_histories_order_deliveryman');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chattings', function (Blueprint $table): void {
            foreach (array_keys($this->deliverymanThreadIndexes()) as $name) {
                $table->dropIndex($name);
            }
        });

        Schema::table('deliveryman_notifications', function (Blueprint $table): void {
            $table->dropIndex('idx_deliveryman_notifications_owner');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex('idx_reviews_delivery_man_saved_updated');
        });

        Schema::table('delivery_histories', function (Blueprint $table): void {
            $table->dropIndex('idx_delivery_histories_order_deliveryman');
        });
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function deliverymanThreadIndexes(): array
    {
        return [
            'idx_chattings_dm_user_seen' => ['delivery_man_id', 'user_id', 'seen_by_delivery_man'],
            'idx_chattings_dm_seller_seen' => ['delivery_man_id', 'seller_id', 'seen_by_delivery_man'],
            'idx_chattings_dm_admin_seen' => ['delivery_man_id', 'admin_id', 'seen_by_delivery_man'],
        ];
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $existing): bool => $existing['name'] === $index);
    }
};
