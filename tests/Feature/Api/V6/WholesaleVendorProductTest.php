<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Enums\CampaignScheduleStatus;
use Modules\Wholesale\app\Enums\CampaignShopStatus;
use Modules\Wholesale\app\Services\Campaign\CampaignParticipationService;
use Modules\Wholesale\app\Services\Campaign\CampaignService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleVendorProductTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const BASE = 'api/v6/wholesale/seller/products';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_details_return_the_uploaded_video_url_and_the_form_video_mode(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put(ProductWholesaleService::VIDEO_DIRECTORY.'vendor-api-clip.mp4', 'clip');

        $seller = $this->createSeller();
        $product = $this->createProduct(seller: $seller);

        Product::query()->whereKey($product->id)->update([
            'video_provider' => ProductWholesaleService::VIDEO_PROVIDER_SELF_HOSTED,
            'video_url' => null,
            'video_file' => 'vendor-api-clip.mp4',
            'video_file_storage_type' => 'public',
        ]);

        $response = $this->getJson(self::BASE.'/'.$product->id, $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('data.video_provider', ProductWholesaleService::VIDEO_MODE_FILE)
            ->assertJsonPath('data.video_file', 'vendor-api-clip.mp4');

        $videoFileFullUrl = (string) $response->json('data.video_file_full_url');

        $this->assertStringStartsWith('http', $videoFileFullUrl);
        $this->assertStringContainsString('product/video', $videoFileFullUrl);
        $this->assertStringEndsWith('/vendor-api-clip.mp4', $videoFileFullUrl);
    }

    public function test_details_report_a_youtube_link_in_the_form_video_mode_without_a_file_url(): void
    {
        $seller = $this->createSeller();
        $product = $this->createProduct(seller: $seller);

        Product::query()->whereKey($product->id)->update([
            'video_provider' => ProductWholesaleService::VIDEO_PROVIDER_YOUTUBE,
            'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'video_file' => null,
        ]);

        $this->getJson(self::BASE.'/'.$product->id, $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('data.video_provider', ProductWholesaleService::VIDEO_MODE_LINK)
            ->assertJsonPath('data.video_url', 'https://www.youtube.com/embed/dQw4w9WgXcQ')
            ->assertJsonPath('data.video_file_full_url', null);
    }

    public function test_list_and_details_name_the_campaigns_a_product_is_enrolled_in(): void
    {
        $seller = $this->createSeller();
        $enrolled = $this->createProduct(seller: $seller);
        $notEnrolled = $this->createProduct(seller: $seller);

        $approvedCampaign = $this->createCampaign(title: 'Approved product campaign fixture');
        $pendingCampaign = $this->createCampaign(
            title: 'Pending product campaign fixture',
            startDate: now()->addWeek()->toDateString(),
        );
        $deniedCampaign = $this->createCampaign(title: 'Denied product campaign fixture');

        $this->participate(campaign: $approvedCampaign, seller: $seller, status: CampaignShopStatus::APPROVED, discountsByProductId: [$enrolled->id => 12]);
        $this->participate(campaign: $pendingCampaign, seller: $seller, status: CampaignShopStatus::PENDING, discountsByProductId: [$enrolled->id => 20]);
        $this->participate(campaign: $deniedCampaign, seller: $seller, status: CampaignShopStatus::DENIED, discountsByProductId: [$enrolled->id => 15]);

        $expected = [
            [
                'id' => $approvedCampaign->id,
                'title' => 'Approved product campaign fixture',
                'participation_status' => CampaignShopStatus::APPROVED->value,
                'schedule_status' => CampaignScheduleStatus::RUNNING->value,
                'min_discount' => 5,
                'max_discount' => 50,
                'enrolled_discount' => 12,
            ],
            [
                'id' => $pendingCampaign->id,
                'title' => 'Pending product campaign fixture',
                'participation_status' => CampaignShopStatus::PENDING->value,
                'schedule_status' => CampaignScheduleStatus::UPCOMING->value,
                'min_discount' => 5,
                'max_discount' => 50,
                'enrolled_discount' => 20,
            ],
        ];

        $listed = collect(
            $this->getJson(self::BASE.'?limit=100', $this->sellerHeaders($seller))->assertOk()->json('content.data')
        )->keyBy('id');

        $this->assertEquals($expected, $listed[$enrolled->id]['campaigns']);
        $this->assertSame([], $listed[$notEnrolled->id]['campaigns']);

        $this->assertEquals(
            $expected,
            $this->getJson(self::BASE.'/'.$enrolled->id, $this->sellerHeaders($seller))->assertOk()->json('data.campaigns'),
        );
        $this->getJson(self::BASE.'/'.$notEnrolled->id, $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('data.campaigns', []);
    }

    public function test_campaigns_of_another_seller_are_not_reported(): void
    {
        $seller = $this->createSeller();
        $otherSeller = $this->createSeller();
        $product = $this->createProduct(seller: $seller);
        $campaign = $this->createCampaign(title: 'Other seller campaign fixture');

        $this->participate(campaign: $campaign, seller: $otherSeller, status: CampaignShopStatus::APPROVED, discountsByProductId: [$product->id => 10]);

        $this->getJson(self::BASE.'/'.$product->id, $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('data.campaigns', []);
    }

    private function createSeller(): Seller
    {
        $unique = Str::lower(Str::random(10));

        $seller = Seller::query()->create([
            'f_name' => 'Vendor',
            'l_name' => 'Product',
            'phone' => '+1'.random_int(1000000000, 1999999999),
            'email' => 'vendor-product-'.$unique.'@example.test',
            'password' => bcrypt('password'),
            'status' => 'approved',
        ]);
        $seller->forceFill(['auth_token' => Str::random(50)])->saveQuietly();

        Shop::query()->create([
            'seller_id' => $seller->id,
            'author_type' => 'vendor',
            'name' => 'Vendor Product Shop '.$unique,
            'slug' => 'vendor-product-shop-'.$unique,
            'address' => 'Test address',
            'contact' => '+1'.random_int(1000000000, 1999999999),
            'banner' => 'def.png',
        ]);

        return $seller;
    }

    private function createProduct(Seller $seller): Model
    {
        $unique = Str::upper(Str::random(8));

        return app(ProductWholesaleService::class)->create(
            data: [
                'name' => ['Vendor API product '.$unique],
                'lang' => ['en'],
                'description' => ['Vendor API product'],
                'code' => 'VAPI'.$unique,
                'unit' => 'pc',
                'minimum_order_qty' => 10,
                'current_stock' => 500,
                'discount' => 0,
                'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
                'quantity_based_price' => 1,
                'tier_min_qty' => [10],
                'tier_max_qty' => [null],
                'tier_unit_price' => [60],
                'category_id' => $this->wholesaleCategoryId(),
            ],
            addedBy: ProductWholesaleService::ADDED_BY_SELLER,
            userId: $seller->id,
            shopId: (int) Shop::query()->where('seller_id', $seller->id)->value('id'),
        );
    }

    private function wholesaleCategoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->orderBy('id')->value('id'),
            'wholesale top-level category',
        );
    }

    private function createCampaign(string $title, ?string $startDate = null): Model
    {
        return app(CampaignService::class)->create(data: [
            'lang' => ['en'],
            'title' => [$title],
            'description' => ['Wholesale vendor product campaign fixture'],
            'min_discount' => 5,
            'max_discount' => 50,
            'start_date' => $startDate ?? now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);
    }

    private function participate(Model $campaign, Seller $seller, CampaignShopStatus $status, array $discountsByProductId): void
    {
        $participationService = app(CampaignParticipationService::class);
        $participation = $participationService->enrolShop(
            campaignId: $campaign->id,
            sellerId: $seller->id,
            discountsByProductId: $discountsByProductId,
        );

        $participationService->updateApprovalStatus(id: $participation->id, status: $status);
    }
}
