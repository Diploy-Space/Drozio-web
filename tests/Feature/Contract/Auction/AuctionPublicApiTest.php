<?php

namespace Tests\Feature\Contract\Auction;

use Modules\Auction\app\Enums\AuctionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\Auction\Concerns\RequiresAuctionAddon;
use Tests\Feature\Contract\Auction\Concerns\SeedsAuctions;
use Tests\Feature\Contract\ContractTestCase;

class AuctionPublicApiTest extends ContractTestCase
{
    use RequiresAuctionAddon;
    use SeedsAuctions;

    protected string $apiPrefix = 'api/v6';

    public static function publicEndpoints(): array
    {
        return [
            'categories' => ['auction/categories'],
            'brands' => ['auction/brands'],
            'constants' => ['auction/constants'],
            'tags' => ['auction/tags'],
            'product list' => ['auction/products'],
            'suggestions' => ['auction/products/suggestions?search=a'],
            'filter ranges' => ['auction/products/filter-ranges'],
        ];
    }

    #[Test]
    #[DataProvider('publicEndpoints')]
    public function endpoint_answers_in_the_v6_envelope_without_authentication(string $uri): void
    {
        $response = $this->apiGet($uri);

        $this->assertJsonContract($response, [
            'identical_code',
            'message',
            'content',
            'errors',
        ], [
            'identical_code' => 'string',
            'message' => 'string',
            'errors' => 'array',
        ]);

        $this->assertSame('data_fetched_200', $response->json('identical_code'), "[{$uri}] did not answer with the data_fetched code");
    }

    #[Test]
    public function the_browse_list_carries_data_params_and_pagination(): void
    {
        $response = $this->apiGet('auction/products?limit=2');

        $this->assertJsonContract($response, [
            'content' => ['data', 'params', 'pagination'],
        ], [
            'content.data' => 'array',
            'content.pagination' => 'array|null',
        ]);
    }

    #[Test]
    public function categories_list_only_those_holding_a_live_or_upcoming_auction(): void
    {
        $live = $this->seedAuction();
        $upcoming = $this->seedAuction([
            'start_time' => now()->addHour(),
            'end_time' => now()->addDay(),
            'auction_current_status' => AuctionStatus::UPCOMING,
        ]);
        $ended = $this->seedAuction([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDay(),
            'auction_current_status' => AuctionStatus::UNSOLD,
        ]);

        $categories = collect($this->apiGet('auction/categories?limit=all')->json('content.data'))->keyBy('id');

        $this->assertTrue($categories->has($live->category_id), 'a category with a live auction is missing');
        $this->assertTrue($categories->has($upcoming->category_id), 'a category with an upcoming auction is missing');
        $this->assertFalse($categories->has($ended->category_id), 'a category with only an ended auction was listed');
        $this->assertSame(1, $categories[$live->category_id]['product_count']);
    }

    #[Test]
    public function the_browse_list_honours_its_limit(): void
    {
        $this->seedAuction();
        $this->seedAuction();
        $this->seedAuction();

        $response = $this->apiGet('auction/products?limit=2');

        $this->assertCount(2, $response->json('content.data'), 'the browse list ignored its limit');
        $this->assertSame(2, $response->json('content.pagination.per_page'));
        $this->assertGreaterThanOrEqual(3, $response->json('content.pagination.total'));
    }

    #[Test]
    public function the_browse_list_hides_an_auction_that_is_not_approved(): void
    {
        $approved = $this->seedAuction();
        $pending = $this->seedAuction(['approval_status' => 'pending']);

        $listed = collect($this->apiGet('auction/products?limit=50')->json('content.data'))->pluck('id');

        $this->assertContains($approved->id, $listed, 'an approved auction is missing from the browse list');
        $this->assertNotContains($pending->id, $listed, 'an unapproved auction leaked into the browse list');
    }

    #[Test]
    public function an_unknown_slug_is_a_not_found_envelope(): void
    {
        $response = $this->apiGet('auction/products/no-such-auction-slug');

        $response->assertStatus(404);
        $this->assertSame('auction_not_found_404', $response->json('identical_code'));
        $this->assertNull($response->json('content'));
    }

    #[Test]
    public function a_known_slug_returns_that_auction(): void
    {
        $auction = $this->seedAuction();

        $response = $this->apiGet('auction/products/'.$auction->slug);

        $response->assertOk();
        $this->assertSame($auction->id, $response->json('content.data.id'));
        $this->assertSame($auction->slug, $response->json('content.data.slug'));
    }

    #[Test]
    public function suggestions_match_on_name_and_on_tag(): void
    {
        $auction = $this->seedAuction(['name' => 'Contract Telescope', 'slug' => 'contract-telescope']);
        $this->seedAuctionTag('stargazing', 40, $auction->id);

        $byName = collect($this->apiGet('auction/products/suggestions?search=Telescope')->json('content.data'))->pluck('id');
        $byTag = collect($this->apiGet('auction/products/suggestions?search=stargaz')->json('content.data'))->pluck('id');

        $this->assertContains($auction->id, $byName, 'a name match is missing from the suggestions');
        $this->assertContains($auction->id, $byTag, 'a tag match is missing from the suggestions');
    }

    #[Test]
    public function tags_come_back_most_visited_first(): void
    {
        $auction = $this->seedAuction();
        $this->seedAuctionTag('contract-rare', 5, $auction->id);
        $this->seedAuctionTag('contract-popular', 9999, $auction->id);

        $tags = collect($this->apiGet('auction/tags?limit=50')->json('content.data'));
        $popular = $tags->firstWhere('tag', 'contract-popular');
        $rare = $tags->firstWhere('tag', 'contract-rare');

        $this->assertNotNull($popular, 'the popular tag is missing');
        $this->assertNotNull($rare, 'the rare tag is missing');
        $this->assertLessThan(
            $tags->search(fn (array $t): bool => $t['tag'] === 'contract-rare'),
            $tags->search(fn (array $t): bool => $t['tag'] === 'contract-popular'),
            'tags are not ordered by visit count',
        );
    }

    #[Test]
    public function suggestions_are_empty_without_a_search_term(): void
    {
        $response = $this->apiGet('auction/products/suggestions');

        $response->assertOk();
        $this->assertSame([], $response->json('content.data'));
    }

    #[Test]
    public function the_filter_ranges_expose_every_bound_a_filter_ui_needs(): void
    {
        $response = $this->apiGet('auction/products/filter-ranges');

        $this->assertJsonContract($response, [
            'content' => ['data' => ['price', 'entry_fee' => ['min', 'max'], 'ending_time']],
        ], [
            'content.data.entry_fee.min' => 'number',
            'content.data.entry_fee.max' => 'number',
        ]);
    }

    #[Test]
    public function the_constants_cover_every_status_vocabulary_the_endpoints_use(): void
    {
        $response = $this->apiGet('auction/constants');

        $this->assertJsonContract($response, [
            'content' => ['data' => [
                'auction_status',
                'delivery_status',
                'approval_status',
                'payment_status',
                'item_condition',
                'owner_type',
                'product_status',
                'transaction_type',
            ]],
        ], []);
    }
}
