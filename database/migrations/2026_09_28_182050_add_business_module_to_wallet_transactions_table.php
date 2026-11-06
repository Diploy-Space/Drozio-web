<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MATCH_WINDOW_SECONDS = 10;

    public function up(): void
    {
        if (! Schema::hasColumn('wallet_transactions', 'business_module')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->string('business_module', 20)->nullable()->after('transaction_type')->index();
            });
        }

        $this->tagWholesaleOrderPayments();
        $this->tagWholesaleOrderRefunds();
    }

    public function down(): void
    {
        if (Schema::hasColumn('wallet_transactions', 'business_module')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropIndex(['business_module']);
                $table->dropColumn('business_module');
            });
        }
    }

    private function tagWholesaleOrderPayments(): void
    {
        $transactionIds = DB::table('wallet_transactions as wallet')
            ->join('orders', function ($join) {
                $join->on('orders.customer_id', '=', 'wallet.user_id')
                    ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, orders.created_at, wallet.created_at)) <= ?', [self::MATCH_WINDOW_SECONDS]);
            })
            ->where('wallet.transaction_type', 'order_place')
            ->whereNull('wallet.business_module')
            ->where('orders.payment_method', 'pay_by_wallet')
            ->where('orders.business_module', BusinessModule::WHOLESALE)
            ->pluck('wallet.id');

        DB::table('wallet_transactions')->whereIn('id', $transactionIds)->update(['business_module' => BusinessModule::WHOLESALE]);
    }

    private function tagWholesaleOrderRefunds(): void
    {
        $transactionIds = DB::table('wallet_transactions as wallet')
            ->join('refund_requests', function ($join) {
                $join->on('refund_requests.customer_id', '=', 'wallet.user_id')
                    ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, refund_requests.updated_at, wallet.created_at)) <= ?', [self::MATCH_WINDOW_SECONDS]);
            })
            ->join('orders', 'orders.id', '=', 'refund_requests.order_id')
            ->where('wallet.transaction_type', 'order_refund')
            ->whereNull('wallet.business_module')
            ->where('orders.business_module', BusinessModule::WHOLESALE)
            ->pluck('wallet.id');

        DB::table('wallet_transactions')->whereIn('id', $transactionIds)->update(['business_module' => BusinessModule::WHOLESALE]);
    }
};
