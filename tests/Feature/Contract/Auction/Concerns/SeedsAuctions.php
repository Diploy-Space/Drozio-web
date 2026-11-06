<?php

namespace Tests\Feature\Contract\Auction\Concerns;

use Illuminate\Support\Facades\DB;
use Modules\Auction\app\Enums\ApprovalStatus;
use Modules\Auction\app\Enums\AuctionStatus;
use Modules\Auction\app\Enums\ItemCondition;
use Modules\Auction\app\Enums\OwnerType;

trait SeedsAuctions
{
    private static int $auctionBatch = 0;

    protected function seedAuction(array $overrides = []): object
    {
        $batch = ++self::$auctionBatch;

        $categoryId = $overrides['category_id'] ?? DB::table('categories')->insertGetId([
            'name' => "Contract Auction Category {$batch}",
            'slug' => "contract-auction-category-{$batch}",
            'position' => 0,
            'business_module' => 'auction',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $id = DB::table('auction_products')->insertGetId(array_merge([
            'owner_type' => OwnerType::ADMIN,
            'owner_id' => 1,
            'name' => "Contract Auction {$batch}",
            'slug' => "contract-auction-{$batch}",
            'category_id' => $categoryId,
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
            'images' => json_encode(['contract.webp']),
            'created_at' => now(), 'updated_at' => now(),
        ], $overrides));

        return (object) ['id' => $id, 'slug' => "contract-auction-{$batch}", 'category_id' => $categoryId];
    }

    /**
     * The v6 seller middleware only accepts an alphanumeric bearer token of at
     * least 30 characters, which the shared v1/v3 seed token is not.
     *
     * @return array{id: int, token: string, shop_id: int}
     */
    protected function seedV6Seller(): array
    {
        $seller = $this->seedApiSeller(['auth_token' => 'contractv6seller'.str_pad((string) ++self::$auctionBatch, 20, '0', STR_PAD_LEFT)]);

        $seller['token'] = DB::table('sellers')->where('id', $seller['id'])->value('auth_token');

        return $seller;
    }

    protected function seedAuctionTag(string $tag, int $visitCount, ?int $auctionProductId = null): int
    {
        $tagId = DB::table('auction_tags')->insertGetId([
            'tag' => $tag,
            'visit_count' => $visitCount,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        if ($auctionProductId !== null) {
            DB::table('auction_product_tag')->insert([
                'tag_id' => $tagId,
                'auction_product_id' => $auctionProductId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $tagId;
    }
}
