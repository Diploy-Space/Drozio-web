<?php

use App\Enums\BusinessModule;
use App\Services\New\LoyaltyPointTransactionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'loyalty_point_transactions_user_scope_index';

    private const REPLACED_INDEX_NAME = 'loyalty_point_transactions_scope_user_index';

    private const ORDER_REFERENCE_TYPES = [
        LoyaltyPointTransactionService::TRANSACTION_TYPE_ORDER_PLACE,
        LoyaltyPointTransactionService::TRANSACTION_TYPE_REFUND_ORDER,
    ];

    public function up(): void
    {
        if (Schema::hasColumn('loyalty_point_transactions', 'business_module')) {
            return;
        }

        Schema::table('loyalty_point_transactions', function (Blueprint $table) {
            $table->string('business_module', 20)
                ->default(BusinessModule::RETAIL)
                ->after('user_id');

            $table->index(['user_id', 'business_module'], self::INDEX_NAME);
        });

        $this->backfillOrderTransactions();
        $this->backfillWalletConversions();
    }

    public function down(): void
    {
        if (! Schema::hasColumn('loyalty_point_transactions', 'business_module')) {
            return;
        }

        Schema::table('loyalty_point_transactions', function (Blueprint $table) {
            foreach ([self::INDEX_NAME, self::REPLACED_INDEX_NAME] as $index) {
                if (Schema::hasIndex('loyalty_point_transactions', $index)) {
                    $table->dropIndex($index);
                }
            }

            $table->dropColumn('business_module');
        });
    }

    private function backfillOrderTransactions(): void
    {
        $modules = array_diff(BusinessModule::values(), [BusinessModule::RETAIL]);

        foreach ($modules as $module) {
            DB::table('loyalty_point_transactions')
                ->whereIn('transaction_type', self::ORDER_REFERENCE_TYPES)
                ->whereIn('reference', fn (Builder $query) => $query->select('id')
                    ->from('orders')
                    ->where('business_module', $module))
                ->update(['business_module' => $module]);
        }
    }

    private function backfillWalletConversions(): void
    {
        DB::table('loyalty_point_transactions')
            ->where('transaction_type', LoyaltyPointTransactionService::TRANSACTION_TYPE_POINT_TO_WALLET)
            ->update(['business_module' => BusinessModule::COMMON]);
    }
};
