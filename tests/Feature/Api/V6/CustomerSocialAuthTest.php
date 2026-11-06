<?php

namespace Tests\Feature\Api\V6;

use App\Models\User;
use App\Services\New\LoginSetupService;
use App\Services\New\SocialProfileService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class CustomerSocialAuthTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const EMAIL = 'social-customer@example.test';

    private const SOCIAL_ID = 'google-account-7781';

    private const PHONE = '+8801900000077';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->mock(SocialProfileService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchProfile')->andReturn([
                'social_id' => self::SOCIAL_ID,
                'email' => self::EMAIL,
                'f_name' => 'Social',
                'l_name' => 'Customer',
            ]);
        });

        Passport::$validateKeyPermissions = false;

        $this->partialMock(LoginSetupService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isLoginOptionEnabled')->andReturn(true);
            $mock->shouldReceive('getValueByKey')->andReturn([SocialProfileService::MEDIUM_GOOGLE => 1]);
            $mock->shouldReceive('requiredVerificationChannel')->andReturn(null);
        });
    }

    protected function tearDown(): void
    {
        Passport::$validateKeyPermissions = true;
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_registering_after_a_social_login_finishes_the_account_that_login_started(): void
    {
        $this->socialLogin()
            ->assertJsonPath('content.phone_required', true);

        $this->registerWithSocialMedia()
            ->assertCreated()
            ->assertJsonPath('identical_code', 'registration_success_201')
            ->assertJsonStructure(['content' => ['token']]);

        $this->assertSame(1, User::query()->where('email', self::EMAIL)->count());
        $this->assertSame(self::PHONE, User::query()->where('email', self::EMAIL)->value('phone'));
    }

    public function test_registering_first_creates_the_account(): void
    {
        $this->registerWithSocialMedia()
            ->assertCreated()
            ->assertJsonPath('identical_code', 'registration_success_201');

        $customer = User::query()->where('email', self::EMAIL)->first();

        $this->assertSame(self::PHONE, $customer->phone);
        $this->assertSame(self::SOCIAL_ID, $customer->social_id);
    }

    public function test_registering_twice_reports_the_email_as_already_registered(): void
    {
        $this->registerWithSocialMedia()->assertCreated();

        $this->registerWithSocialMedia(phone: '+8801900000078')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.error_code', 'account_email_reserved');
    }

    public function test_an_email_owned_by_an_unlinked_account_is_already_registered(): void
    {
        User::query()->forceCreate([
            'name' => 'Manual Customer',
            'f_name' => 'Manual',
            'l_name' => 'Customer',
            'email' => self::EMAIL,
            'phone' => '+8801900000079',
            'password' => Hash::make('manual-password-1'),
            'is_active' => 1,
        ]);

        $this->registerWithSocialMedia()
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.error_code', 'account_email_reserved');
    }

    public function test_a_blocked_account_waiting_for_its_phone_cannot_finish_registering(): void
    {
        $this->socialLogin();

        User::query()->where('email', self::EMAIL)->update(['is_active' => 0]);

        $this->registerWithSocialMedia()
            ->assertJsonPath('errors.0.error_code', 'account_inactive')
            ->assertJsonMissingPath('content.token');

        $this->assertSame('', (string) User::query()->where('email', self::EMAIL)->value('phone'));
    }

    private function socialLogin(): TestResponse
    {
        return $this->postJson('api/v6/auth/social/login', [
            'token' => 'provider-token',
            'unique_id' => self::SOCIAL_ID,
            'medium' => SocialProfileService::MEDIUM_GOOGLE,
        ]);
    }

    private function registerWithSocialMedia(string $phone = self::PHONE): TestResponse
    {
        return $this->postJson('api/v6/auth/register/social', [
            'token' => 'provider-token',
            'unique_id' => self::SOCIAL_ID,
            'medium' => SocialProfileService::MEDIUM_GOOGLE,
            'phone' => $phone,
        ]);
    }
}
