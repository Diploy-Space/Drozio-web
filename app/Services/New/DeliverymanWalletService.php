<?php

namespace App\Services\New;

use App\Models\DeliverymanWallet;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeliverymanWalletService extends BaseService
{
    public function __construct(DeliverymanWallet $deliverymanWallet)
    {
        parent::__construct($deliverymanWallet);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'delivery_man_id' => $data['delivery_man_id'],
            'current_balance' => $data['current_balance'] ?? 0,
            'cash_in_hand' => $data['cash_in_hand'] ?? 0,
            'pending_withdraw' => $data['pending_withdraw'] ?? 0,
            'total_withdraw' => $data['total_withdraw'] ?? 0,
        ]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function incrementBalances(int $deliveryManId, float $currentBalance, float $cashInHand): int
    {
        return $this->query()
            ->where('delivery_man_id', $deliveryManId)
            ->increment('current_balance', $currentBalance, [
                'cash_in_hand' => DB::raw('cash_in_hand + '.$cashInHand),
            ]);
    }

    public function creditDeliveryEarning(int $deliverymanId, float $deliverymanCharge, float $cashInHand): void
    {
        $this->query()->insertOrIgnore([
            'delivery_man_id' => $deliverymanId,
            'current_balance' => 0,
            'cash_in_hand' => 0,
            'pending_withdraw' => 0,
            'total_withdraw' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->incrementBalances(deliveryManId: $deliverymanId, currentBalance: $deliverymanCharge, cashInHand: $cashInHand);
    }

    /**
     * @return array{current_balance: float, cash_in_hand: float, pending_withdraw: float, total_withdraw: float, withdrawable_balance: float, total_earn: float}
     */
    public function getBalanceSummary(int $deliverymanId): array
    {
        $wallet = $this->findBy(params: ['delivery_man_id' => $deliverymanId]);

        $currentBalance = (float) ($wallet->current_balance ?? 0);
        $cashInHand = (float) ($wallet->cash_in_hand ?? 0);
        $pendingWithdraw = (float) ($wallet->pending_withdraw ?? 0);
        $totalWithdraw = (float) ($wallet->total_withdraw ?? 0);

        return [
            'current_balance' => $currentBalance,
            'cash_in_hand' => $cashInHand,
            'pending_withdraw' => $pendingWithdraw,
            'total_withdraw' => $totalWithdraw,
            'withdrawable_balance' => max(0, $currentBalance - ($cashInHand + $pendingWithdraw)),
            'total_earn' => $currentBalance + $totalWithdraw,
        ];
    }

    public function settleWithdraw(int $deliverymanId, float $amount, bool $isApproved): bool
    {
        $query = $this->query()
            ->where('delivery_man_id', $deliverymanId)
            ->where('pending_withdraw', '>=', $amount);

        if (! $isApproved) {
            return $query->decrement('pending_withdraw', $amount) > 0;
        }

        return $query->where('current_balance', '>=', $amount)->update([
            'pending_withdraw' => DB::raw('pending_withdraw - '.$amount),
            'current_balance' => DB::raw('current_balance - '.$amount),
            'total_withdraw' => DB::raw('total_withdraw + '.$amount),
        ]) > 0;
    }

    public function deductCashInHand(int $deliverymanId, float $amount): bool
    {
        return $this->query()
            ->where('delivery_man_id', $deliverymanId)
            ->where('cash_in_hand', '>=', $amount)
            ->decrement('cash_in_hand', $amount) > 0;
    }

    public function reservePendingWithdraw(int $deliverymanId, float $amount): bool
    {
        return $this->query()
            ->where('delivery_man_id', $deliverymanId)
            ->whereRaw('(current_balance - cash_in_hand - pending_withdraw) >= ?', [$amount])
            ->increment('pending_withdraw', $amount) > 0;
    }
}
