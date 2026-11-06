<?php

namespace Tests\Feature\Api\V6;

use App\Models\Seller;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Auction\app\Enums\ApprovalStatus;
use Modules\Auction\app\Enums\AuctionStatus;
use Modules\Auction\app\Enums\ItemCondition;
use Modules\Auction\app\Enums\OwnerType;
use Tests\Feature\Api\Support\ApiTestData;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Contract\Auction\Concerns\RequiresAuctionAddon;
use Tests\TestCase;

class AuctionSellerWithdrawTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresAuctionAddon;

    private const WITHDRAWS = 'api/v6/auction/seller/withdraws';

    private const NOT_CLAIMED = 'auction_withdraw_not_claimed_422';

    private const BANK_INFO_MISSING = 'auction_withdraw_bank_info_missing_422';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
    }

    public function test_payout_with_the_method_details_does_not_need_profile_bank_information(): void
    {
        $seller = $this->sellerWithBankInformation(bankName: null, accountNo: null);

        $this->postJson(self::WITHDRAWS, [
            'auction_product_id' => $this->createUnclaimedAuction(seller: $seller),
            'withdraw_method_id' => $this->createVendorWithdrawalMethod(fieldNames: ['account_number']),
            'method_info' => ['account_number' => '1234567890'],
        ], $this->sellerHeaders($seller))
            ->assertStatus(422)
            ->assertJsonPath('identical_code', self::NOT_CLAIMED);
    }

    public function test_payout_with_blank_method_details_and_no_profile_bank_information_is_refused(): void
    {
        $seller = $this->sellerWithBankInformation(bankName: null, accountNo: null);

        $this->postJson(self::WITHDRAWS, [
            'auction_product_id' => $this->createUnclaimedAuction(seller: $seller),
            'withdraw_method_id' => $this->createVendorWithdrawalMethod(fieldNames: ['account_number']),
            'method_info' => ['account_number' => ''],
        ], $this->sellerHeaders($seller))
            ->assertStatus(422)
            ->assertJsonPath('identical_code', self::BANK_INFO_MISSING);
    }

    public function test_payout_on_a_method_without_fields_and_no_profile_bank_information_is_refused(): void
    {
        $seller = $this->sellerWithBankInformation(bankName: null, accountNo: null);

        $this->postJson(self::WITHDRAWS, [
            'auction_product_id' => $this->createUnclaimedAuction(seller: $seller),
            'withdraw_method_id' => $this->createVendorWithdrawalMethod(fieldNames: []),
        ], $this->sellerHeaders($seller))
            ->assertStatus(422)
            ->assertJsonPath('identical_code', self::BANK_INFO_MISSING);
    }

    public function test_payout_on_a_method_without_fields_falls_back_to_profile_bank_information(): void
    {
        $seller = $this->sellerWithBankInformation(bankName: 'Contract Bank', accountNo: '1234567890');

        $this->postJson(self::WITHDRAWS, [
            'auction_product_id' => $this->createUnclaimedAuction(seller: $seller),
            'withdraw_method_id' => $this->createVendorWithdrawalMethod(fieldNames: []),
        ], $this->sellerHeaders($seller))
            ->assertStatus(422)
            ->assertJsonPath('identical_code', self::NOT_CLAIMED);
    }

    private function sellerWithBankInformation(?string $bankName, ?string $accountNo): Seller
    {
        $seller = $this->requireFixture(ApiTestData::sellerWithApiToken(), 'approved seller with an API token');

        $seller->forceFill(['bank_name' => $bankName, 'account_no' => $accountNo])->save();

        return $seller;
    }

    private function createUnclaimedAuction(Seller $seller): int
    {
        $slug = 'withdraw-rule-auction-'.uniqid();

        return DB::table('auction_products')->insertGetId([
            'owner_type' => OwnerType::SELLER,
            'owner_id' => $seller->id,
            'name' => $slug,
            'slug' => $slug,
            'category_id' => DB::table('categories')->where('business_module', 'auction')->value('id') ?? 0,
            'item_condition' => ItemCondition::GOOD,
            'product_type' => 'physical',
            'starting_price' => 100,
            'minimum_increment_amount' => 10,
            'maximum_decrement_amount' => 5,
            'shipping_fee' => 0,
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
            'status' => 1,
            'is_canceled' => 0,
            'owner_is_active' => 1,
            'approval_status' => ApprovalStatus::APPROVED,
            'auction_current_status' => AuctionStatus::LIVE,
            'images' => json_encode(['withdraw-rule.webp']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createVendorWithdrawalMethod(array $fieldNames): int
    {
        return DB::table('withdrawal_methods')->insertGetId([
            'method_name' => 'Withdraw rule method',
            'method_fields' => json_encode(array_map(
                fn (string $fieldName): array => ['input_type' => 'string', 'input_name' => $fieldName, 'placeholder' => '', 'is_required' => 1],
                $fieldNames,
            )),
            'is_default' => 0,
            'is_active' => 1,
            'vendor_status' => 1,
            'customer_status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
