<?php

namespace Tests\Feature\Api\V6;

use App\Enums\WebConfigKey;
use App\Models\BusinessSetting;
use App\Models\Seller;
use App\Utils\VendorVerificationCriteria;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class SellerShopVerificationCriteriaTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const ENDPOINT = 'api/v6/seller/shop/verification-criteria';

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

    public function test_an_unverified_seller_receives_every_criterion_with_its_progress(): void
    {
        $this->setVerifiedBadge(enabled: true);
        $seller = $this->approvedSeller(isVerified: false);

        $response = $this->getJson(self::ENDPOINT, $this->sellerHeaders($seller));

        $response->assertOk()
            ->assertJsonPath('content.data.is_verified', false)
            ->assertJsonPath('content.data.is_badge_enabled', true)
            ->assertJsonPath('content.data.show_prompt', true)
            ->assertJsonStructure(['content' => ['data' => [
                'is_qualified',
                'criteria' => [['key', 'label', 'leading', 'highlight', 'trailing', 'current', 'met']],
            ]]]);

        $this->assertSame(
            VendorVerificationCriteria::keys(),
            array_column($response->json('content.data.criteria'), 'key'),
        );
    }

    public function test_the_prompt_is_hidden_for_a_verified_seller(): void
    {
        $this->setVerifiedBadge(enabled: true);
        $seller = $this->approvedSeller(isVerified: true);

        $this->getJson(self::ENDPOINT, $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('content.data.is_verified', true)
            ->assertJsonPath('content.data.show_prompt', false);
    }

    public function test_the_prompt_is_hidden_while_the_verified_badge_is_switched_off(): void
    {
        $this->setVerifiedBadge(enabled: false);
        $seller = $this->approvedSeller(isVerified: false);

        $this->getJson(self::ENDPOINT, $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('content.data.is_badge_enabled', false)
            ->assertJsonPath('content.data.show_prompt', false);
    }

    public function test_a_request_without_a_token_is_rejected(): void
    {
        $this->getJson(self::ENDPOINT)->assertUnauthorized();
    }

    private function setVerifiedBadge(bool $enabled): void
    {
        BusinessSetting::query()->updateOrInsert(
            ['type' => WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS],
            ['value' => $enabled ? 1 : 0],
        );
        Cache::flush();
    }

    private function approvedSeller(bool $isVerified): Seller
    {
        $seller = $this->requireFixture(
            Seller::query()->where('status', 'approved')->whereHas('shop')->first(),
            'an approved seller with a shop',
        );

        $seller->forceFill(['auth_token' => Str::random(50), 'is_verified' => $isVerified])->save();

        return $seller->fresh();
    }
}
