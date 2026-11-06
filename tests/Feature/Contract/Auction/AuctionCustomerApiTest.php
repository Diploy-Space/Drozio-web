<?php

namespace Tests\Feature\Contract\Auction;

use App\Enums\BusinessModule;
use Illuminate\Support\Facades\DB;
use Modules\Auction\app\Enums\AuctionStatus;
use Modules\Auction\app\Enums\OwnerType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\Auction\Concerns\RequiresAuctionAddon;
use Tests\Feature\Contract\Auction\Concerns\SeedsAuctions;
use Tests\Feature\Contract\Concerns\SeedsMobileAuth;
use Tests\Feature\Contract\ContractTestCase;

class AuctionCustomerApiTest extends ContractTestCase
{
    use RequiresAuctionAddon;
    use SeedsAuctions;
    use SeedsMobileAuth;

    protected string $apiPrefix = 'api/v6';

    public static function authenticatedEndpoints(): array
    {
        return [
            'my bids' => ['auction/products/my-bids'],
            'my auctions' => ['auction/products/my-auctions'],
            'pending approval' => ['auction/products/pending-approval'],
            'saved products' => ['auction/saved-products'],
            'recent views' => ['auction/recent-views'],
            'withdraws' => ['auction/withdraws'],
            'transactions' => ['auction/transactions'],
            'notifications' => ['auction/notifications'],
            'withdraw methods' => ['auction/withdraw-methods'],
        ];
    }

    #[Test]
    #[DataProvider('authenticatedEndpoints')]
    public function endpoint_rejects_an_unauthenticated_caller(string $uri): void
    {
        $this->apiGet($uri)->assertStatus(401);
    }

    #[Test]
    #[DataProvider('authenticatedEndpoints')]
    public function endpoint_answers_in_the_v6_envelope_for_a_customer(string $uri): void
    {
        $this->actingAsApiCustomer();

        $response = $this->apiGet($uri);

        $this->assertJsonContract($response, ['identical_code', 'message', 'content', 'errors'], [
            'identical_code' => 'string',
            'errors' => 'array',
        ]);
    }

    #[Test]
    public function my_auctions_lists_only_what_the_caller_owns(): void
    {
        $customerId = $this->actingAsApiCustomer();
        $mine = $this->seedAuction(['owner_type' => OwnerType::CUSTOMER, 'owner_id' => $customerId]);
        $someoneElse = $this->seedAuction(['owner_type' => OwnerType::CUSTOMER, 'owner_id' => $customerId + 9999]);

        $listed = collect($this->apiGet('auction/products/my-auctions?status=live&limit=50')->json('content.data'))->pluck('id');

        $this->assertContains($mine->id, $listed, 'the caller\'s own auction is missing');
        $this->assertNotContains($someoneElse->id, $listed, 'another customer\'s auction leaked into the list');
    }

    #[Test]
    public function my_auctions_reports_a_count_per_status_tab(): void
    {
        $customerId = $this->actingAsApiCustomer();
        $this->seedAuction(['owner_type' => OwnerType::CUSTOMER, 'owner_id' => $customerId]);

        $counts = $this->apiGet('auction/products/my-auctions?status=live')->json('content.counts');

        $this->assertIsArray($counts);
        $this->assertArrayHasKey('live', $counts);
        $this->assertGreaterThanOrEqual(1, $counts['live']);
    }

    #[Test]
    public function my_bids_participated_tab_lists_only_joined_auctions_that_have_not_started(): void
    {
        $customerId = $this->actingAsApiCustomer();
        $upcoming = $this->seedAuction([
            'start_time' => now()->addHour(),
            'end_time' => now()->addDay(),
            'auction_current_status' => AuctionStatus::UPCOMING,
        ]);
        $live = $this->seedAuction();

        DB::table('auction_participants')->insert(array_map(static fn (int $auctionId): array => [
            'auction_product_id' => $auctionId,
            'user_id' => $customerId,
            'entry_fee_paid_status' => 'paid',
            'entry_fee_paid_amount' => 0,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], [$upcoming->id, $live->id]));

        $response = $this->apiGet('auction/products/my-bids?status=participated&limit=10&offset=1');
        $listed = collect($response->json('content.data'))->pluck('id');

        $this->assertSame([$upcoming->id], $listed->all(), 'the participated tab listed more than the joined upcoming auction');
        $this->assertSame(1, $response->json('content.counts.participated'));
        $this->assertSame(2, $response->json('content.counts.all'));
    }

    #[Test]
    public function saved_products_lists_only_the_callers_own_saves(): void
    {
        $customerId = $this->actingAsApiCustomer();
        $mine = $this->seedAuction();
        $theirs = $this->seedAuction();

        DB::table('auction_saved_products')->insert([
            ['auction_product_id' => $mine->id, 'user_id' => $customerId, 'created_at' => now(), 'updated_at' => now()],
            ['auction_product_id' => $theirs->id, 'user_id' => $customerId + 9999, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $listed = collect($this->apiGet('auction/saved-products?limit=50')->json('content.data'))->pluck('id');

        $this->assertContains($mine->id, $listed);
        $this->assertNotContains($theirs->id, $listed, 'another customer\'s saved auction leaked into the list');
    }

    #[Test]
    public function recent_views_lists_only_the_callers_own_views(): void
    {
        $customerId = $this->actingAsApiCustomer();
        $mine = $this->seedAuction();
        $theirs = $this->seedAuction();

        DB::table('product_recent_views')->insert([
            ['product_id' => $mine->id, 'user_id' => $customerId, 'business_module' => BusinessModule::AUCTION, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $theirs->id, 'user_id' => $customerId + 9999, 'business_module' => BusinessModule::AUCTION, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $listed = collect($this->apiGet('auction/recent-views?limit=50')->json('content.data'))->pluck('auction_product_id');

        $this->assertContains($mine->id, $listed);
        $this->assertNotContains($theirs->id, $listed, 'another customer\'s recent view leaked into the list');
    }

    #[Test]
    public function commission_status_on_an_auction_the_caller_does_not_own_is_not_found(): void
    {
        $this->actingAsApiCustomer();
        $someoneElses = $this->seedAuction();

        $response = $this->apiGet("auction/products/{$someoneElses->id}/commission");

        $response->assertStatus(404);
        $this->assertSame('auction_not_found_404', $response->json('identical_code'));
    }

    #[Test]
    public function commission_status_reports_the_amount_and_why_it_is_blocked(): void
    {
        $customerId = $this->actingAsApiCustomer();
        $auction = $this->seedAuction(['owner_type' => OwnerType::CUSTOMER, 'owner_id' => $customerId]);

        $response = $this->apiGet("auction/products/{$auction->id}/commission");

        $this->assertJsonContract($response, [
            'content' => ['data' => ['auction_product_id', 'commission_amount', 'commission_given', 'payable', 'blocking_reason']],
        ], [
            'content.data.auction_product_id' => 'integer',
            'content.data.commission_amount' => 'number',
            'content.data.commission_given' => 'boolean',
            'content.data.payable' => 'boolean',
        ]);
    }

    #[Test]
    public function withdraw_preview_on_an_auction_the_caller_does_not_own_is_not_found(): void
    {
        $this->actingAsApiCustomer();
        $someoneElses = $this->seedAuction();

        $this->apiGet("auction/withdraws/preview/{$someoneElses->id}")->assertStatus(404);
    }
}
