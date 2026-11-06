<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            if (! $this->hasIndex('support_tickets', 'idx_support_tickets_customer_id_created_at')) {
                $table->index(['customer_id', 'created_at'], 'idx_support_tickets_customer_id_created_at');
            }
        });

        Schema::table('support_ticket_convs', function (Blueprint $table): void {
            if (! $this->hasIndex('support_ticket_convs', 'idx_support_ticket_convs_ticket_id_created_at')) {
                $table->index(['support_ticket_id', 'created_at'], 'idx_support_ticket_convs_ticket_id_created_at');
            }
        });

        Schema::table('chattings', function (Blueprint $table): void {
            if (! $this->hasIndex('chattings', 'idx_chattings_user_seller_seen')) {
                $table->index(['user_id', 'seller_id', 'seen_by_customer'], 'idx_chattings_user_seller_seen');
            }
            if (! $this->hasIndex('chattings', 'idx_chattings_user_delivery_man_seen')) {
                $table->index(['user_id', 'delivery_man_id', 'seen_by_customer'], 'idx_chattings_user_delivery_man_seen');
            }
            if (! $this->hasIndex('chattings', 'idx_chattings_user_admin_seen')) {
                $table->index(['user_id', 'admin_id', 'seen_by_customer'], 'idx_chattings_user_admin_seen');
            }
        });

        if (Schema::hasTable('auction_notifications')) {
            Schema::table('auction_notifications', function (Blueprint $table): void {
                if (! $this->hasIndex('auction_notifications', 'idx_auction_notifications_receiver_is_read')) {
                    $table->index(['receiver_type', 'receiver_id', 'is_read'], 'idx_auction_notifications_receiver_is_read');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->dropIndex('idx_support_tickets_customer_id_created_at');
        });

        Schema::table('support_ticket_convs', function (Blueprint $table): void {
            $table->dropIndex('idx_support_ticket_convs_ticket_id_created_at');
        });

        Schema::table('chattings', function (Blueprint $table): void {
            $table->dropIndex('idx_chattings_user_seller_seen');
            $table->dropIndex('idx_chattings_user_delivery_man_seen');
            $table->dropIndex('idx_chattings_user_admin_seen');
        });

        if (Schema::hasTable('auction_notifications')) {
            Schema::table('auction_notifications', function (Blueprint $table): void {
                $table->dropIndex('idx_auction_notifications_receiver_is_read');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $existing): bool => $existing['name'] === $index);
    }
};
