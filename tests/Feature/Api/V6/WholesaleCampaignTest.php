<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Models\Category;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Modules\Wholesale\app\Services\Campaign\CampaignParticipationService;
use Modules\Wholesale\app\Services\Campaign\CampaignService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleCampaignTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

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

    public function test_list_answers_the_v6_envelope_with_running_campaigns_only(): void
    {
        $running = $this->createListedCampaign(['title' => ['Running fixture campaign']]);
        $expired = $this->createListedCampaign([
            'title' => ['Expired fixture campaign'],
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);
        $inactive = $this->createListedCampaign(['title' => ['Inactive fixture campaign']]);
        $this->campaignService()->updateStatus(id: $inactive->id, status: false);

        $response = $this->getJson('api/v6/wholesale/campaigns?limit=100');

        $response->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->assertJsonStructure([
                'identical_code',
                'message',
                'content' => [
                    'data' => ['*' => [
                        'id', 'title', 'slug', 'description', 'image', 'image_full_url',
                        'min_discount', 'max_discount', 'start_date', 'end_date',
                        'daily_start_time', 'daily_end_time', 'schedule_status',
                    ]],
                    'params',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'],
                ],
                'errors',
            ]);

        $ids = collect($response->json('content.data'))->pluck('id');

        $this->assertTrue($ids->contains($running->id));
        $this->assertFalse($ids->contains($expired->id));
        $this->assertFalse($ids->contains($inactive->id));

        $this->assertArrayNotHasKey('manufacturer_count', $response->json('content.data.0'));
    }

    public function test_list_narrows_to_the_searched_campaign(): void
    {
        $matching = $this->createListedCampaign(['title' => ['Monsoon clearance fixture']]);
        $other = $this->createListedCampaign(['title' => ['Winter stock fixture']]);

        $response = $this->getJson('api/v6/wholesale/campaigns?search=Monsoon+clearance+fixture&limit=100');

        $ids = collect($response->assertOk()->json('content.data'))->pluck('id');

        $this->assertTrue($ids->contains($matching->id));
        $this->assertFalse($ids->contains($other->id));
        $this->assertSame(['search' => 'Monsoon clearance fixture'], $response->json('content.params'));
    }

    public function test_details_report_the_approved_manufacturer_and_product_totals(): void
    {
        $campaign = $this->createCampaign(['title' => ['Details fixture campaign']]);
        $seller = $this->seller();
        $active = $this->createProduct();
        $inactive = $this->createProduct(['status' => 0]);

        $this->enrol($campaign, $seller, [$active->id => 10.0, $inactive->id => 10.0]);

        $this->getJson('api/v6/wholesale/campaigns/'.$campaign->slug)
            ->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->assertJsonPath('content.data.id', $campaign->id)
            ->assertJsonPath('content.data.title', 'Details fixture campaign')
            ->assertJsonPath('content.data.schedule_status', 'running')
            ->assertJsonPath('content.data.manufacturer_count', 1)
            ->assertJsonPath('content.data.product_count', 1);
    }

    public function test_products_list_the_approved_items_with_their_price_range(): void
    {
        $campaign = $this->createCampaign(['title' => ['Product list fixture campaign']]);
        $enrolled = $this->createProduct();
        $unenrolled = $this->createProduct();

        $this->enrol($campaign, $this->seller(), [$enrolled->id => 10.0]);

        $response = $this->getJson('api/v6/wholesale/campaigns/'.$campaign->slug.'/products?limit=10&offset=1');

        $response->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->assertJsonCount(1, 'content.data')
            ->assertJsonStructure(['content' => [
                'data' => ['*' => ['id', 'name', 'slug', 'thumbnail', 'thumbnail_full_url', 'price_range' => ['min', 'max'], 'minimum_order_qty', 'current_stock', 'discount', 'discount_type', 'unit']],
                'pagination',
            ]])
            ->assertJsonPath('content.data.0.id', $enrolled->id)
            ->assertJsonPath('content.data.0.price_range', ['min' => 100, 'max' => 100])
            ->assertJsonPath('content.pagination.total', 1);

        $this->assertFalse(collect($response->json('content.data'))->pluck('id')->contains($unenrolled->id));
    }

    public function test_a_campaign_without_approved_products_is_hidden_from_the_storefront(): void
    {
        $campaign = $this->createCampaign(['title' => ['Empty fixture campaign']]);
        $this->createProduct();

        $this->assertCampaignIsNotRunning($campaign);
    }

    public function test_a_campaign_whose_only_enrolled_product_is_inactive_is_hidden(): void
    {
        $campaign = $this->createCampaign(['title' => ['Inactive product fixture campaign']]);
        $this->enrol($campaign, $this->seller(), [$this->createProduct(['status' => 0])->id => 10.0]);

        $this->assertCampaignIsNotRunning($campaign);
    }

    public function test_an_unusable_limit_or_offset_still_returns_the_first_paginated_page(): void
    {
        $campaign = $this->createCampaign(['title' => ['Pagination fixture campaign']]);
        $product = $this->createProduct();

        $this->enrol($campaign, $this->seller(), [$product->id => 10.0]);

        foreach (['limit=all', 'limit=0', 'offset=0'] as $query) {
            $this->getJson('api/v6/wholesale/campaigns?'.$query)
                ->assertOk()
                ->assertJsonPath('content.pagination.current_page', 1)
                ->assertJsonPath('content.pagination.per_page', DEFAULT_DATA_LIMIT);

            $this->getJson('api/v6/wholesale/campaigns/'.$campaign->slug.'/products?'.$query)
                ->assertOk()
                ->assertJsonCount(1, 'content.data')
                ->assertJsonPath('content.pagination.current_page', 1)
                ->assertJsonPath('content.pagination.per_page', DEFAULT_DATA_LIMIT);
        }
    }

    public function test_unknown_and_inactive_campaign_slugs_are_not_found(): void
    {
        $inactive = $this->createListedCampaign(['title' => ['Hidden fixture campaign']]);
        $this->campaignService()->updateStatus(id: $inactive->id, status: false);

        foreach (['no-such-wholesale-campaign-slug', $inactive->slug] as $slug) {
            foreach (['', '/products'] as $suffix) {
                $this->getJson('api/v6/wholesale/campaigns/'.$slug.$suffix)
                    ->assertNotFound()
                    ->assertJsonPath('identical_code', 'data_not_found_404');
            }
        }
    }

    public function test_a_campaign_is_listed_and_found_only_inside_its_daily_window(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $campaign = $this->createListedCampaign([
            'title' => ['Daily window fixture campaign'],
            'daily_start_time' => '10:00',
            'daily_end_time' => '18:00',
        ]);

        $this->assertCampaignIsRunning($campaign);

        $this->travelTo(now()->setTime(19, 0));

        $this->assertCampaignIsNotRunning($campaign);
    }

    public function test_a_campaign_without_a_daily_window_runs_all_day(): void
    {
        $this->travelTo(now()->setTime(3, 0));
        $campaign = $this->createListedCampaign(['title' => ['All day fixture campaign']]);

        $this->assertCampaignIsRunning($campaign);
    }

    public function test_an_overnight_daily_window_runs_across_midnight(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $campaign = $this->createListedCampaign([
            'title' => ['Overnight window fixture campaign'],
            'daily_start_time' => '22:00',
            'daily_end_time' => '02:00',
        ]);

        $this->assertCampaignIsNotRunning($campaign);

        foreach ([[23, 30], [1, 0]] as [$hour, $minute]) {
            $this->travelTo(now()->setTime($hour, $minute));

            $this->assertCampaignIsRunning($campaign);
        }

        $this->travelTo(now()->setTime(2, 30));

        $this->assertCampaignIsNotRunning($campaign);
    }

    private function assertCampaignIsRunning(Model $campaign): void
    {
        $this->assertTrue($this->listedCampaignIds()->contains($campaign->id));

        $this->getJson('api/v6/wholesale/campaigns/'.$campaign->slug)
            ->assertOk()
            ->assertJsonPath('content.data.schedule_status', 'running');
    }

    private function assertCampaignIsNotRunning(Model $campaign): void
    {
        $this->assertFalse($this->listedCampaignIds()->contains($campaign->id));

        foreach (['', '/products'] as $suffix) {
            $this->getJson('api/v6/wholesale/campaigns/'.$campaign->slug.$suffix)
                ->assertNotFound()
                ->assertJsonPath('identical_code', 'data_not_found_404');
        }
    }

    private function listedCampaignIds(): Collection
    {
        return collect($this->getJson('api/v6/wholesale/campaigns?limit=1000')->assertOk()->json('content.data'))->pluck('id');
    }

    private function campaignService(): CampaignService
    {
        return app(CampaignService::class);
    }

    private function createCampaign(array $overrides): Model
    {
        return $this->campaignService()->create(data: [
            'lang' => ['en'],
            'description' => ['Wholesale campaign fixture'],
            'min_discount' => 5,
            'max_discount' => 50,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            ...$overrides,
        ]);
    }

    private function createListedCampaign(array $overrides): Model
    {
        $campaign = $this->createCampaign($overrides);
        $this->enrol($campaign, $this->seller(), [$this->createProduct()->id => 10.0]);

        return $campaign;
    }

    private function enrol(Model $campaign, Seller $seller, array $discountsByProductId): void
    {
        app(CampaignParticipationService::class)->enrolShop(
            campaignId: $campaign->id,
            sellerId: $seller->id,
            discountsByProductId: $discountsByProductId,
        );
    }

    private function createProduct(array $state = []): Model
    {
        $product = app(ProductWholesaleService::class)->create(data: [
            'lang' => ['en'],
            'name' => ['Wholesale campaign product fixture'],
            'description' => ['Wholesale campaign product fixture'],
            'code' => 'WSCMP'.random_int(10000, 99999),
            'category_id' => $this->categoryId(),
            'unit' => 'pc',
            'shipping_cost' => 0,
            'minimum_order_qty' => 1,
            'current_stock' => 100,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
            'quantity_based_price' => 1,
            'tier_min_qty' => [1],
            'tier_max_qty' => [''],
            'tier_unit_price' => [100],
        ]);

        $product->forceFill(['status' => 1, 'request_status' => 1, ...$state])->save();

        return $product;
    }

    private function seller(): Seller
    {
        static $seller = null;

        return $seller ??= $this->requireFixture(Seller::query()->orderBy('id')->first(), 'seller');
    }

    private function categoryId(): int
    {
        static $categoryId = null;

        return $categoryId ??= (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }
}
