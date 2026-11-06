<?php

namespace Tests\Feature\Contract\Auction;

use Modules\Auction\app\Enums\OwnerType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\Auction\Concerns\RequiresAuctionAddon;
use Tests\Feature\Contract\Auction\Concerns\SeedsAuctions;
use Tests\Feature\Contract\Concerns\SeedsMobileAuth;
use Tests\Feature\Contract\ContractTestCase;

class AuctionSellerApiTest extends ContractTestCase
{
    use RequiresAuctionAddon;
    use SeedsAuctions;
    use SeedsMobileAuth;

    protected string $apiPrefix = 'api/v6';

    public static function sellerEndpoints(): array
    {
        return [
            'product list' => ['auction/seller/products'],
            'pending approval' => ['auction/seller/products/pending-approval'],
            'withdraws' => ['auction/seller/withdraws'],
            'transactions' => ['auction/seller/transactions'],
            'notifications' => ['auction/seller/notifications'],
            'sales report' => ['auction/seller/sales-report'],
            'vat report' => ['auction/seller/vat-report'],
        ];
    }

    #[Test]
    #[DataProvider('sellerEndpoints')]
    public function endpoint_rejects_a_caller_without_a_seller_token(string $uri): void
    {
        $this->apiGet($uri)->assertStatus(401);
    }

    #[Test]
    #[DataProvider('sellerEndpoints')]
    public function endpoint_answers_in_the_v6_envelope_for_a_seller(string $uri): void
    {
        $seller = $this->seedV6Seller();

        $response = $this->apiGet($uri, $this->bearer($seller['token']));

        $this->assertJsonContract($response, ['identical_code', 'message', 'content', 'errors'], [
            'identical_code' => 'string',
            'errors' => 'array',
        ]);
    }

    #[Test]
    public function a_customer_token_cannot_reach_the_seller_surface(): void
    {
        $this->actingAsApiCustomer();

        $this->apiGet('auction/seller/products')->assertStatus(401);
    }

    #[Test]
    public function the_product_list_shows_only_what_the_seller_owns(): void
    {
        $seller = $this->seedV6Seller();
        $mine = $this->seedAuction(['owner_type' => OwnerType::SELLER, 'owner_id' => $seller['id'], 'shop_id' => $seller['shop_id']]);
        $theirs = $this->seedAuction(['owner_type' => OwnerType::SELLER, 'owner_id' => $seller['id'] + 9999]);

        $listed = collect($this->apiGet('auction/seller/products?limit=50', $this->bearer($seller['token']))->json('content.data'))->pluck('id');

        $this->assertContains($mine->id, $listed, 'the seller\'s own auction is missing');
        $this->assertNotContains($theirs->id, $listed, 'another seller\'s auction leaked into the list');
    }

    #[Test]
    public function product_details_for_another_sellers_auction_is_not_found(): void
    {
        $seller = $this->seedV6Seller();
        $theirs = $this->seedAuction(['owner_type' => OwnerType::SELLER, 'owner_id' => $seller['id'] + 9999]);

        $response = $this->apiGet("auction/seller/products/{$theirs->id}", $this->bearer($seller['token']));

        $response->assertStatus(404);
        $this->assertSame('auction_not_found_404', $response->json('identical_code'));
    }

    #[Test]
    public function product_details_returns_the_sellers_own_auction(): void
    {
        $seller = $this->seedV6Seller();
        $mine = $this->seedAuction(['owner_type' => OwnerType::SELLER, 'owner_id' => $seller['id'], 'shop_id' => $seller['shop_id']]);

        $response = $this->apiGet("auction/seller/products/{$mine->id}", $this->bearer($seller['token']));

        $response->assertOk();
        $this->assertSame($mine->id, $response->json('content.data.id'));
        $this->assertJsonContract($response, [
            'content' => ['data' => [
                'translations', 'tags', 'tax_vats', 'seo_info', 'meta_image_full_url', 'custom_video_url',
                'custom_video_url_full_url', 'category', 'brand', 'delivery_status', 'payment_status',
                'rejected_note', 'approved_at', 'approved_by_admin_id', 'created_at', 'updated_at', 'tracking_url',
                'admin_commission_given', 'current_highest_bid_amount', 'highest_bid_amount', 'total_bids',
                'total_participants', 'total_views', 'auction_details', 'auction_insights', 'winner',
                'shipping_address_id', 'shipping_address_info', 'billing_address_id', 'billing_address_info',
                'billing_same_as_shipping', 'transactions', 'auction_payment', 'delivered_payout', 'auction_withdraw',
            ]],
        ], [
            'content.data.tags' => 'array',
            'content.data.transactions' => 'array',
            'content.data.admin_commission_given' => 'boolean',
        ]);
    }

    #[Test]
    public function the_product_list_carries_counts_params_and_pagination(): void
    {
        $seller = $this->seedV6Seller();
        $this->seedAuction(['owner_type' => OwnerType::SELLER, 'owner_id' => $seller['id'], 'shop_id' => $seller['shop_id']]);

        $response = $this->apiGet('auction/seller/products?limit=5', $this->bearer($seller['token']));

        $this->assertJsonContract($response, [
            'content' => ['data', 'counts', 'params', 'pagination'],
        ], [
            'content.data' => 'array',
            'content.counts' => 'array',
        ]);
    }

    #[Test]
    public function pending_approval_lists_only_auctions_waiting_on_the_admin(): void
    {
        $seller = $this->seedV6Seller();
        $owner = ['owner_type' => OwnerType::SELLER, 'owner_id' => $seller['id'], 'shop_id' => $seller['shop_id']];
        $pending = $this->seedAuction($owner + ['approval_status' => 'pending']);
        $approved = $this->seedAuction($owner);

        $listed = collect($this->apiGet('auction/seller/products/pending-approval?limit=50', $this->bearer($seller['token']))->json('content.data'))->pluck('id');

        $this->assertContains($pending->id, $listed, 'a pending auction is missing from the pending list');
        $this->assertNotContains($approved->id, $listed, 'an approved auction leaked into the pending list');
    }

    #[Test]
    public function commission_status_reports_the_amount_and_why_it_is_blocked(): void
    {
        $seller = $this->seedV6Seller();
        $auction = $this->seedAuction(['owner_type' => OwnerType::SELLER, 'owner_id' => $seller['id'], 'shop_id' => $seller['shop_id']]);

        $response = $this->apiGet("auction/seller/products/{$auction->id}/commission", $this->bearer($seller['token']));

        $this->assertJsonContract($response, [
            'content' => ['data' => ['auction_product_id', 'commission_amount', 'commission_given', 'payable', 'blocking_reason']],
        ], [
            'content.data.auction_product_id' => 'integer',
            'content.data.payable' => 'boolean',
        ]);
    }

    #[Test]
    public function withdraw_preview_on_another_sellers_auction_is_not_found(): void
    {
        $seller = $this->seedV6Seller();
        $theirs = $this->seedAuction(['owner_type' => OwnerType::SELLER, 'owner_id' => $seller['id'] + 9999]);

        $this->apiGet("auction/seller/withdraws/preview/{$theirs->id}", $this->bearer($seller['token']))->assertStatus(404);
    }
}
