<?php

namespace Tests\Feature\Contract\Auction;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Auction\app\Enums\ApprovalStatus;
use Modules\Auction\app\Enums\AuctionStatus;
use Modules\Auction\app\Enums\OwnerType;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\Auction\Concerns\RequiresAuctionAddon;
use Tests\Feature\Contract\Auction\Concerns\SeedsAuctions;
use Tests\Feature\Contract\ContractTestCase;

class AuctionProductDetailApiTest extends ContractTestCase
{
    use RequiresAuctionAddon;
    use SeedsAuctions;

    protected string $apiPrefix = 'api/v6';

    private const PARTICIPATION_KEYS = [
        'owner_type', 'owner_id', 'seller', 'customer', 'highest_bid_amount', 'current_highest_bid_amount',
        'total_views', 'total_bids_count', 'is_all_bid_withdrawn', 'total_tax_amount', 'auction_status',
        'my_auction_status', 'my_bid', 'my_participation', 'shipping_address_info', 'billing_address_info',
        'billing_same_as_shipping', 'tracking_url', 'auction_insights', 'is_auction_save',
        'custom_video_url_full_url', 'second_highest_bid', 'similar_products', 'same_author_products',
        'payment_info', 'billing_info',
    ];

    private const CREATOR_KEYS = [
        'delivered_payout', 'auction_withdraw', 'transactions', 'admin_commission_given', 'auction_status',
        'delivery_status', 'rejected_note', 'category', 'brand', 'total_bids_count', 'highest_bid_amount',
        'total_views', 'shipping_address_info', 'billing_address_info', 'billing_same_as_shipping',
        'tracking_url', 'tax_vats', 'tags', 'translations', 'seo_info', 'meta_image_full_url',
        'custom_video_url_full_url', 'created_at', 'updated_at',
    ];

    private static int $customerBatch = 0;

    #[Test]
    public function a_guest_gets_the_participation_view_with_no_viewer_data(): void
    {
        $auction = $this->seedAuction();

        $response = $this->apiGet('auction/products/'.$auction->slug)->assertOk();

        $this->assertHasKeys($response->json('content.data'), self::PARTICIPATION_KEYS);
        $this->assertNull($response->json('content.data.my_bid'));
        $this->assertNull($response->json('content.data.my_participation'));
        $this->assertFalse($response->json('content.data.is_auction_save'));
        $this->assertSame(['view' => 'participation', 'is_creator' => false, 'is_participant' => false], $response->json('content.viewer'));
    }

    #[Test]
    public function a_bearer_token_resolves_the_participants_own_bid_and_entry_fee_on_the_public_route(): void
    {
        $auction = $this->seedAuction();
        $participant = $this->seedCustomer();
        $rival = $this->seedCustomer();
        $this->seedParticipation($auction->id, $participant['id']);
        $this->seedParticipation($auction->id, $rival['id']);
        $this->seedBid($auction->id, $rival['id'], 120);
        $this->seedBid($auction->id, $participant['id'], 150);

        $response = $this->apiGet('auction/products/'.$auction->slug, $this->bearer($participant))->assertOk();

        $this->assertSame(150.0, (float) $response->json('content.data.my_bid.bid_amount'));
        $this->assertTrue($response->json('content.data.my_bid.is_lead_bid'));
        $this->assertSame('paid', $response->json('content.data.my_participation.entry_fee_paid_status'));
        $this->assertSame('live', $response->json('content.data.my_auction_status'));
        $this->assertSame(120.0, (float) $response->json('content.data.second_highest_bid.bid_amount'));
        $this->assertSame(2, $response->json('content.data.total_bids_count'));
        $this->assertSame(['view' => 'participation', 'is_creator' => false, 'is_participant' => true], $response->json('content.viewer'));
    }

    #[Test]
    public function the_participation_view_names_only_the_owner_its_owner_type_points_at(): void
    {
        $owner = $this->seedCustomer();
        $auction = $this->seedAuction(['owner_type' => OwnerType::CUSTOMER, 'owner_id' => $owner['id']]);

        $data = $this->apiGet('auction/products/'.$auction->slug)->assertOk()->json('content.data');

        $this->assertSame(OwnerType::CUSTOMER, $data['owner_type']);
        $this->assertSame('Contract', $data['customer']['f_name']);
        $this->assertNull($data['seller']);
    }

    #[Test]
    public function the_winners_address_is_hidden_from_every_other_viewer(): void
    {
        $winner = $this->seedCustomer();
        $auction = $this->seedAuction(['shipping_address_info' => json_encode(['address' => 'Winner Street']), 'tracking_url' => 'https://track.example.test/1']);
        DB::table('auction_products')->where('id', $auction->id)->update(['winner_user_id' => $winner['id']]);

        $this->assertNull($this->apiGet('auction/products/'.$auction->slug)->json('content.data.shipping_address_info'));
        $this->assertNull($this->apiGet('auction/products/'.$auction->slug)->json('content.data.tracking_url'));

        $asWinner = $this->apiGet('auction/products/'.$auction->slug, $this->bearer($winner))->json('content.data');
        $this->assertNotNull($asWinner['shipping_address_info']);
        $this->assertSame('https://track.example.test/1', $asWinner['tracking_url']);
    }

    #[Test]
    public function the_winner_sees_their_address_and_payment_after_claiming(): void
    {
        $winner = $this->seedCustomer();
        DB::table('users')->where('id', $winner['id'])->update(['wallet_balance' => 10000]);
        $auction = $this->seedAuction([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subMinute(),
            'auction_current_status' => AuctionStatus::READY_TO_CLAIM,
        ]);
        $this->seedParticipation($auction->id, $winner['id']);
        $this->seedBid($auction->id, $winner['id'], 150);
        $address = ['contact_person_name' => 'Winner', 'phone' => '+15550000000', 'country' => 'Bangladesh', 'city' => 'Dhaka', 'zip' => '1216', 'address' => 'Winner Street'];

        $this->apiPost('auction/claims', [
            'auction_product_id' => $auction->id,
            'payment_method' => 'wallet',
            'shipping_address_info' => $address,
            'billing_same_as_shipping' => 1,
        ], $this->bearer($winner))->assertOk();

        $data = $this->apiGet('auction/products/'.$auction->slug, $this->bearer($winner))->assertOk()->json('content.data');

        $this->assertSame('Winner Street', $data['shipping_address_info']['address']);
        $this->assertSame('paid', $data['payment_info']['payment_status']);
        $this->assertSame('wallet', $data['payment_info']['payment_method']);
        $this->assertNotNull($data['payment_info']['paid_amount']);
    }

    #[Test]
    public function a_claim_accepts_the_checkout_address_id_key_as_the_shipping_address(): void
    {
        $winner = $this->seedCustomer();
        DB::table('users')->where('id', $winner['id'])->update(['wallet_balance' => 10000]);
        $auction = $this->seedAuction([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subMinute(),
            'auction_current_status' => AuctionStatus::READY_TO_CLAIM,
        ]);
        $this->seedParticipation($auction->id, $winner['id']);
        $this->seedBid($auction->id, $winner['id'], 150);
        $addressId = $this->seedShippingAddress($winner['id']);

        $this->apiPost('auction/claims', [
            'auction_product_id' => $auction->id,
            'payment_method' => 'wallet',
            'address_id' => $addressId,
            'billing_same_as_shipping' => 1,
        ], $this->bearer($winner))->assertOk();

        $claimed = DB::table('auction_products')->where('id', $auction->id)->first();

        $this->assertSame($addressId, (int) $claimed->shipping_address_id);
        $this->assertSame('Claim Street', json_decode($claimed->shipping_address_info, true)['address']);
    }

    #[Test]
    public function a_claim_rejects_an_address_id_the_customer_does_not_own(): void
    {
        $winner = $this->seedCustomer();
        $stranger = $this->seedCustomer();
        $auction = $this->seedAuction([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subMinute(),
            'auction_current_status' => AuctionStatus::READY_TO_CLAIM,
        ]);

        $this->apiPost('auction/claims', [
            'auction_product_id' => $auction->id,
            'payment_method' => 'wallet',
            'address_id' => $this->seedShippingAddress($stranger['id']),
        ], $this->bearer($winner))->assertStatus(422);
    }

    #[Test]
    public function the_participation_view_records_a_recent_view_for_a_signed_in_caller(): void
    {
        $auction = $this->seedAuction();
        $viewer = $this->seedCustomer();

        $this->apiGet('auction/products/'.$auction->slug.'?view=participation', $this->bearer($viewer))->assertOk();
        $this->apiGet('auction/products/'.$auction->slug.'?view=participation', $this->bearer($viewer))->assertOk();

        $this->assertSame(1, DB::table('product_recent_views')->where([
            'product_id' => $auction->id,
            'user_id' => $viewer['id'],
            'business_module' => 'auction',
        ])->count());
    }

    #[Test]
    public function the_participation_view_records_nothing_for_a_guest(): void
    {
        $auction = $this->seedAuction();

        $this->apiGet('auction/products/'.$auction->slug)->assertOk();

        $this->assertSame(0, DB::table('product_recent_views')->where('product_id', $auction->id)->where('business_module', 'auction')->count());
    }

    #[Test]
    public function an_entry_fee_paid_from_the_wallet_shows_its_transaction_on_the_detail(): void
    {
        $participant = $this->seedCustomer();
        DB::table('users')->where('id', $participant['id'])->update(['wallet_balance' => 10000]);
        $auction = $this->seedAuction();

        $this->apiPost('auction/entry-fees', [
            'auction_product_id' => $auction->id,
            'payment_method' => 'wallet',
        ], $this->bearer($participant))->assertOk();

        $participation = $this->apiGet('auction/products/'.$auction->slug, $this->bearer($participant))
            ->assertOk()
            ->json('content.data.my_participation');

        $this->assertSame('paid', $participation['entry_fee_paid_status']);
        $this->assertNotNull($participation['auction_transaction']);
        $this->assertSame('wallet', $participation['auction_transaction']['payment_method']);
        $this->assertSame('paid', $participation['auction_transaction']['payment_status']);
    }

    #[Test]
    public function the_creator_view_needs_a_bearer_token(): void
    {
        $auction = $this->seedAuction();

        $response = $this->apiGet('auction/products/'.$auction->slug.'?view=creator');

        $response->assertStatus(401);
        $this->assertSame('unauthorized_401', $response->json('identical_code'));
    }

    #[Test]
    public function the_creator_view_of_someone_elses_auction_is_not_found(): void
    {
        $owner = $this->seedCustomer();
        $stranger = $this->seedCustomer();
        $auction = $this->seedAuction(['owner_type' => OwnerType::CUSTOMER, 'owner_id' => $owner['id']]);

        $response = $this->apiGet('auction/products/'.$auction->slug.'?view=creator', $this->bearer($stranger));

        $response->assertStatus(404);
        $this->assertSame('auction_not_found_404', $response->json('identical_code'));
    }

    #[Test]
    public function the_creator_view_returns_the_owners_detail(): void
    {
        $owner = $this->seedCustomer();
        $auction = $this->seedAuction(['owner_type' => OwnerType::CUSTOMER, 'owner_id' => $owner['id']]);

        $response = $this->apiGet('auction/products/'.$auction->slug.'?view=creator', $this->bearer($owner))->assertOk();

        $this->assertHasKeys($response->json('content.data'), self::CREATOR_KEYS);
        $this->assertSame('live', $response->json('content.data.auction_status'));
        $this->assertSame(['view' => 'creator', 'is_creator' => true, 'is_participant' => false], $response->json('content.viewer'));
    }

    #[Test]
    public function the_creator_view_reaches_an_own_auction_still_waiting_on_approval(): void
    {
        $owner = $this->seedCustomer();
        $auction = $this->seedAuction([
            'owner_type' => OwnerType::CUSTOMER,
            'owner_id' => $owner['id'],
            'approval_status' => ApprovalStatus::REJECTED,
            'rejected_note' => 'Blurry photos',
        ]);

        $this->apiGet('auction/products/'.$auction->slug, $this->bearer($owner))->assertStatus(404);

        $response = $this->apiGet('auction/products/'.$auction->slug.'?view=creator', $this->bearer($owner))->assertOk();
        $this->assertSame('Blurry photos', $response->json('content.data.rejected_note'));
    }

    #[Test]
    public function a_creator_who_also_participates_gets_both_views_right(): void
    {
        $owner = $this->seedCustomer();
        $auction = $this->seedAuction(['owner_type' => OwnerType::CUSTOMER, 'owner_id' => $owner['id']]);
        $this->seedParticipation($auction->id, $owner['id']);
        $this->seedBid($auction->id, $owner['id'], 130);

        $participation = $this->apiGet('auction/products/'.$auction->slug, $this->bearer($owner))->assertOk();
        $this->assertSame(130.0, (float) $participation->json('content.data.my_bid.bid_amount'));
        $this->assertNotNull($participation->json('content.data.my_participation'));
        $this->assertArrayNotHasKey('delivered_payout', $participation->json('content.data'));
        $this->assertSame(['view' => 'participation', 'is_creator' => true, 'is_participant' => true], $participation->json('content.viewer'));

        $creator = $this->apiGet('auction/products/'.$auction->slug.'?view=creator', $this->bearer($owner))->assertOk();
        $this->assertHasKeys($creator->json('content.data'), self::CREATOR_KEYS);
        $this->assertArrayNotHasKey('my_bid', $creator->json('content.data'));
        $this->assertSame(['view' => 'creator', 'is_creator' => true, 'is_participant' => true], $creator->json('content.viewer'));
    }

    #[Test]
    public function an_unknown_view_is_rejected(): void
    {
        $auction = $this->seedAuction();

        $this->apiGet('auction/products/'.$auction->slug.'?view=owner')->assertStatus(422);
    }

    #[Test]
    public function the_rails_are_filled_only_when_asked_for(): void
    {
        $auction = $this->seedAuction();
        $this->seedAuction(['category_id' => $auction->category_id]);

        $this->assertSame([], $this->apiGet('auction/products/'.$auction->slug)->json('content.data.similar_products'));

        $similar = $this->apiGet('auction/products/'.$auction->slug.'?similar_products=1')->json('content.data.similar_products');
        $this->assertCount(1, $similar);
        $this->assertNotSame($auction->id, $similar[0]['id']);
    }

    private function assertHasKeys(array $data, array $keys): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $data, "content.data is missing {$key}");
        }
    }

    private function seedCustomer(): array
    {
        $batch = ++self::$customerBatch;

        $id = DB::table('users')->insertGetId([
            'name' => 'Contract Customer',
            'f_name' => 'Contract',
            'l_name' => 'Customer',
            'email' => "contract.detail.{$batch}.".uniqid().'@example.test',
            'phone' => '+1600'.random_int(1000000, 9999999),
            'password' => bcrypt('password'),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'token' => User::find($id)->createToken('contract')->accessToken];
    }

    private function bearer(array $customer): array
    {
        return ['Authorization' => 'Bearer '.$customer['token']];
    }

    private function seedShippingAddress(int $customerId): int
    {
        return DB::table('shipping_addresses')->insertGetId([
            'customer_id' => $customerId,
            'is_guest' => 0,
            'contact_person_name' => 'Claim Winner',
            'address_type' => 'home',
            'address' => 'Claim Street',
            'city' => 'Dhaka',
            'zip' => '1216',
            'country' => 'Bangladesh',
            'phone' => '+15550000001',
            'is_billing' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedParticipation(int $auctionId, int $userId): void
    {
        DB::table('auction_participants')->insert([
            'auction_product_id' => $auctionId,
            'user_id' => $userId,
            'entry_fee_paid_status' => 'paid',
            'entry_fee_paid_amount' => 0,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedBid(int $auctionId, int $userId, float $amount): void
    {
        DB::table('auction_bids')->insert([
            'auction_product_id' => $auctionId,
            'user_id' => $userId,
            'bid_amount' => $amount,
            'is_auto_bid' => 0,
            'is_withdraw_bid' => 0,
            'is_rollback_bid' => 0,
            'bid_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('auction_products')->where('id', $auctionId)->update(['current_highest_bid_amount' => DB::raw("GREATEST(current_highest_bid_amount, {$amount})")]);
    }
}
