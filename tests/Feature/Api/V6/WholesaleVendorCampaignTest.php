<?php

namespace Tests\Feature\Api\V6;

use App\Models\Seller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Enums\CampaignShopStatus;
use Modules\Wholesale\app\Services\Campaign\CampaignParticipationService;
use Modules\Wholesale\app\Services\Campaign\CampaignService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleVendorCampaignTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const BASE = 'api/v6/wholesale/seller/campaigns';

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

    public function test_list_narrows_to_the_seller_participation_status(): void
    {
        $seller = $this->seller();
        $pending = $this->createCampaign(title: 'Pending participation fixture');
        $approved = $this->createCampaign(title: 'Approved participation fixture');
        $denied = $this->createCampaign(title: 'Denied participation fixture');
        $notJoined = $this->createCampaign(title: 'Not joined fixture');

        $this->participate(campaign: $pending, seller: $seller, status: CampaignShopStatus::PENDING);
        $this->participate(campaign: $approved, seller: $seller, status: CampaignShopStatus::APPROVED);
        $this->participate(campaign: $denied, seller: $seller, status: CampaignShopStatus::DENIED);

        $fixtureIds = [$pending->id, $approved->id, $denied->id, $notJoined->id];

        $all = $this->listedIds(seller: $seller, query: '');
        $this->assertEqualsCanonicalizing($fixtureIds, array_values(array_intersect($all, $fixtureIds)));

        foreach (['pending' => $pending, 'approved' => $approved, 'denied' => $denied] as $status => $campaign) {
            $response = $this->getJson(self::BASE.'?limit=1000&status='.$status, $this->sellerHeaders($seller))
                ->assertOk()
                ->assertJsonPath('content.params.status', $status);

            $ids = array_column($response->json('content.data'), 'id');

            $this->assertSame([$campaign->id], array_values(array_intersect($ids, $fixtureIds)));
            $this->assertSame(
                [$status],
                array_values(array_unique(array_column($response->json('content.data'), 'participation_status'))),
            );
        }
    }

    public function test_list_rejects_an_unknown_participation_status(): void
    {
        $this->getJson(self::BASE.'?status=joined', $this->sellerHeaders($this->seller()))
            ->assertStatus(422);
    }

    public function test_details_return_the_campaign_creation_date(): void
    {
        $campaign = $this->createCampaign(title: 'Created at fixture');

        $this->getJson(self::BASE.'/'.$campaign->id, $this->sellerHeaders($this->seller()))
            ->assertOk()
            ->assertJsonPath('data.id', $campaign->id)
            ->assertJsonPath('data.created_at', $campaign->fresh()->created_at->toJSON());
    }

    public function test_an_approved_seller_can_leave_a_running_campaign(): void
    {
        $seller = $this->seller();
        $campaign = $this->createCampaign(title: 'Running leave fixture');
        $this->participate(campaign: $campaign, seller: $seller, status: CampaignShopStatus::APPROVED);

        $this->assertTrue($campaign->fresh()->hasStarted());

        $this->deleteJson(self::BASE.'/'.$campaign->id.'/leave', [], $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('message', 'You have left the campaign');

        $this->assertNull($this->participationService()->findBy(
            filters: ['campaign_id' => $campaign->id, 'seller_id' => $seller->id],
            relations: [],
        ));
    }

    public function test_an_approved_seller_can_edit_items_of_a_running_campaign(): void
    {
        $seller = $this->seller();
        $campaign = $this->createCampaign(title: 'Running edit fixture');
        $this->participate(campaign: $campaign, seller: $seller, status: CampaignShopStatus::APPROVED);

        $this->assertTrue($campaign->fresh()->hasStarted());

        $this->getJson(self::BASE.'/'.$campaign->id, $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('data.accepts_item_changes', true);
    }

    public function test_an_approved_seller_can_leave_an_ended_campaign(): void
    {
        $seller = $this->seller();
        $campaign = $this->createCampaign(
            title: 'Ended leave fixture',
            startDate: now()->subMonth()->toDateString(),
            endDate: now()->subDay()->toDateString(),
        );
        $this->participate(campaign: $campaign, seller: $seller, status: CampaignShopStatus::APPROVED);

        $this->deleteJson(self::BASE.'/'.$campaign->id.'/leave', [], $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('message', 'You have left the campaign');
    }

    public function test_leaving_answers_with_the_participation_status_message(): void
    {
        $seller = $this->seller();
        $messages = [
            CampaignShopStatus::PENDING->value => 'Your campaign join request has been cancelled',
            CampaignShopStatus::DENIED->value => 'Your campaign join request has been deleted',
        ];

        foreach ($messages as $status => $message) {
            $campaign = $this->createCampaign(title: 'Leave '.$status.' fixture');
            $this->participate(campaign: $campaign, seller: $seller, status: CampaignShopStatus::from($status));

            $this->deleteJson(self::BASE.'/'.$campaign->id.'/leave', [], $this->sellerHeaders($seller))
                ->assertOk()
                ->assertJsonPath('message', $message);
        }
    }

    public function test_leaving_a_campaign_not_joined_is_not_found(): void
    {
        $campaign = $this->createCampaign(title: 'Not joined leave fixture');

        $this->deleteJson(self::BASE.'/'.$campaign->id.'/leave', [], $this->sellerHeaders($this->seller()))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'data_not_found_404')
            ->assertJsonPath('message', 'You have not joined this campaign');
    }

    private function listedIds(Seller $seller, string $query): array
    {
        return array_column(
            $this->getJson(self::BASE.'?limit=1000'.$query, $this->sellerHeaders($seller))->assertOk()->json('content.data'),
            'id',
        );
    }

    private function createCampaign(string $title, ?string $startDate = null, ?string $endDate = null): Model
    {
        return app(CampaignService::class)->create(data: [
            'lang' => ['en'],
            'title' => [$title],
            'description' => ['Wholesale seller campaign fixture'],
            'min_discount' => 5,
            'max_discount' => 50,
            'start_date' => $startDate ?? now()->subDay()->toDateString(),
            'end_date' => $endDate ?? now()->addMonth()->toDateString(),
        ]);
    }

    private function participate(Model $campaign, Seller $seller, CampaignShopStatus $status): void
    {
        $participation = $this->participationService()->enrolShop(
            campaignId: $campaign->id,
            sellerId: $seller->id,
            discountsByProductId: [],
        );

        $this->participationService()->updateApprovalStatus(id: $participation->id, status: $status);
    }

    private function participationService(): CampaignParticipationService
    {
        return app(CampaignParticipationService::class);
    }

    private function seller(): Seller
    {
        $seller = $this->requireFixture(
            Seller::query()
                ->where('status', 'approved')
                ->whereHas('shops', fn ($shops) => $shops->where('author_type', 'vendor'))
                ->orderBy('id')
                ->first(),
            'approved seller with a vendor shop',
        );

        if ($seller->auth_token === null || $seller->auth_token === '') {
            $seller->forceFill(['auth_token' => Str::random(50)])->saveQuietly();
        }

        return $seller;
    }
}
