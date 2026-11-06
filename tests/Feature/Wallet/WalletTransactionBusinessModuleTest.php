<?php

namespace Tests\Feature\Wallet;

use App\Enums\BusinessModule;
use App\Models\WalletTransaction;
use App\Services\New\WalletTransactionService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WalletTransactionBusinessModuleTest extends TestCase
{
    public static function transactionProvider(): array
    {
        return [
            'stored wholesale order payment' => [WalletTransactionService::TRANSACTION_TYPE_ORDER_PLACE, BusinessModule::WHOLESALE, BusinessModule::WHOLESALE],
            'stored wholesale refund' => [WalletTransactionService::TRANSACTION_TYPE_ORDER_REFUND, BusinessModule::WHOLESALE, BusinessModule::WHOLESALE],
            'untagged order payment' => [WalletTransactionService::TRANSACTION_TYPE_ORDER_PLACE, null, BusinessModule::RETAIL],
            'untagged refund' => [WalletTransactionService::TRANSACTION_TYPE_ORDER_REFUND, null, BusinessModule::RETAIL],
            'auction entry fee' => [WalletTransactionService::TRANSACTION_TYPE_AUCTION_ENTRY_FEE, null, BusinessModule::AUCTION],
            'auction claim payment' => [WalletTransactionService::TRANSACTION_TYPE_AUCTION_CLAIM_PAYMENT, null, BusinessModule::AUCTION],
            'auction commission payment' => [WalletTransactionService::TRANSACTION_TYPE_AUCTION_COMMISSION_PAYMENT, null, BusinessModule::AUCTION],
            'loyalty conversion' => [WalletTransactionService::TRANSACTION_TYPE_LOYALTY_POINT, null, BusinessModule::RETAIL],
            'fund added by admin' => [WalletTransactionService::TRANSACTION_TYPE_ADD_FUND_BY_ADMIN, null, BusinessModule::RETAIL],
        ];
    }

    #[DataProvider('transactionProvider')]
    public function test_business_module_is_resolved_for_every_transaction(string $transactionType, ?string $storedModule, string $expectedModule): void
    {
        $transaction = new WalletTransaction(['transaction_type' => $transactionType, 'business_module' => $storedModule]);

        $this->assertSame($expectedModule, app(WalletTransactionService::class)->getBusinessModule(transaction: $transaction));
    }
}
