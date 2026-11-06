<?php

namespace Tests\Feature\Vendor;

use App\Enums\WebConfigKey;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\BusinessSetting;
use App\Models\Seller;
use App\Services\New\VendorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class ShopVerifiedNoticeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const TOAST_SESSION_KEY = 'laravel-toaster-magic::messages';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_a_newly_verified_vendor_sees_the_success_toast_once(): void
    {
        $this->setVerifiedBadge(enabled: true);
        $seller = $this->approvedSeller(isVerified: true, noticeSeenAt: null);

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.shop.index'))
            ->assertRedirect(route('vendor.shop.index'))
            ->assertSessionHas(self::TOAST_SESSION_KEY, fn (array $messages): bool => $messages[0]['type'] === 'success');

        $this->assertNotNull($seller->fresh()->verified_notice_seen_at);
        $this->assertFalse(app(VendorService::class)->markVerifiedNoticeSeen(id: $seller->id));
    }

    public function test_a_vendor_who_already_saw_the_toast_is_not_redirected(): void
    {
        $this->setVerifiedBadge(enabled: true);
        $seller = $this->approvedSeller(isVerified: true, noticeSeenAt: now());

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.shop.index'))
            ->assertOk()
            ->assertSessionMissing(self::TOAST_SESSION_KEY);
    }

    public function test_an_unverified_vendor_gets_no_toast(): void
    {
        $this->setVerifiedBadge(enabled: true);
        $seller = $this->approvedSeller(isVerified: false, noticeSeenAt: null);

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.shop.index'))
            ->assertOk()
            ->assertSessionMissing(self::TOAST_SESSION_KEY);

        $this->assertNull($seller->fresh()->verified_notice_seen_at);
    }

    public function test_no_toast_while_the_verified_badge_is_switched_off(): void
    {
        $this->setVerifiedBadge(enabled: false);
        $seller = $this->approvedSeller(isVerified: true, noticeSeenAt: null);

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.shop.index'))
            ->assertOk()
            ->assertSessionMissing(self::TOAST_SESSION_KEY);

        $this->assertNull($seller->fresh()->verified_notice_seen_at);
    }

    public function test_granting_the_badge_again_resets_the_toast(): void
    {
        $seller = $this->approvedSeller(isVerified: true, noticeSeenAt: now());
        $vendorService = app(VendorService::class);

        $vendorService->revokeVerifiedBadge(id: $seller->id);
        $vendorService->grantVerifiedBadge(ids: [$seller->id]);

        $this->assertNull($seller->fresh()->verified_notice_seen_at);
    }

    private function setVerifiedBadge(bool $enabled): void
    {
        BusinessSetting::query()->updateOrInsert(
            ['type' => WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS],
            ['value' => $enabled ? 1 : 0],
        );
        Cache::flush();
    }

    private function approvedSeller(bool $isVerified, mixed $noticeSeenAt): Seller
    {
        $seller = $this->requireFixture(
            Seller::query()->where('status', 'approved')->whereHas('shop')->first(),
            'an approved seller with a shop',
        );

        $seller->forceFill(['is_verified' => $isVerified, 'verified_notice_seen_at' => $noticeSeenAt])->save();

        return $seller->fresh();
    }
}
