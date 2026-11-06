<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table): void {
            if (! Schema::hasColumn('sellers', 'wholesale_free_delivery_status')) {
                $table->integer('wholesale_free_delivery_status')->after('free_delivery_over_amount')->default(0);
            }

            if (! Schema::hasColumn('sellers', 'wholesale_free_delivery_over_amount')) {
                $table->float('wholesale_free_delivery_over_amount')->after('wholesale_free_delivery_status')->default(0);
            }

            if (! Schema::hasColumn('sellers', 'wholesale_minimum_order_amount')) {
                $table->decimal('wholesale_minimum_order_amount', 40, 20)->default(0)->after('wholesale_free_delivery_over_amount');
            }

            if (! Schema::hasColumn('sellers', 'wholesale_commission_percentage')) {
                $table->decimal('wholesale_commission_percentage', 40, 20)->nullable()->after('wholesale_minimum_order_amount');
            }

            if (! Schema::hasColumn('sellers', 'is_verified')) {
                $table->boolean('is_verified')->default(0)->index()->after('status');
            }

            if (! Schema::hasColumn('sellers', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('is_verified');
            }

            if (! Schema::hasColumn('sellers', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('sellers', 'is_verified')) {
            Schema::table('sellers', function (Blueprint $table): void {
                $table->dropIndex(['is_verified']);
            });
        }

        foreach ([
            'verified_by',
            'verified_at',
            'is_verified',
            'wholesale_commission_percentage',
            'wholesale_minimum_order_amount',
            'wholesale_free_delivery_over_amount',
            'wholesale_free_delivery_status',
        ] as $column) {
            if (Schema::hasColumn('sellers', $column)) {
                Schema::table('sellers', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
