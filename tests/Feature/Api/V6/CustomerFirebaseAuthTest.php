<?php

namespace Tests\Feature\Api\V6;

use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class CustomerFirebaseAuthTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const PHONE = '+8801900000000';

    private const CODE = '445566';

    private const SESSION_INFO = 'firebase-session-info';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();

        BusinessSetting::query()->updateOrInsert(
            ['type' => 'firebase_otp_verification'],
            ['value' => json_encode(['status' => 1, 'web_api_key' => 'test-key'])],
        );

        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response(['phoneNumber' => self::PHONE]),
        ]);
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_a_reset_verify_issues_the_reset_otp_instead_of_signing_in(): void
    {
        $customer = $this->customerWithPhone();

        $response = $this->verify(isResetToken: 1);

        $response->assertOk()
            ->assertJsonPath('identical_code', 'otp_verified_200')
            ->assertJsonPath('content.identity', self::PHONE)
            ->assertJsonMissingPath('content.token');

        $this->putJson('api/v6/auth/password/reset', [
            'identity' => self::PHONE,
            'otp' => self::CODE,
            'password' => 'new-password-1',
            'confirm_password' => 'new-password-1',
        ])->assertOk()->assertJsonPath('identical_code', 'password_changed_200');

        $this->assertTrue(Hash::check('new-password-1', $customer->refresh()->password));
    }

    public function test_a_reset_verify_for_an_unknown_phone_is_not_found(): void
    {
        $this->verify(isResetToken: 1)
            ->assertNotFound()
            ->assertJsonPath('errors.0.error_code', 'account_not_found');
    }

    public function test_a_login_verify_still_signs_the_customer_in(): void
    {
        $this->customerWithPhone();

        $this->verify(isResetToken: 0)
            ->assertOk()
            ->assertJsonPath('identical_code', 'login_success_200')
            ->assertJsonPath('content.token_type', 'Bearer');
    }

    public function test_an_unknown_phone_gets_a_temporary_token_that_registers_the_customer(): void
    {
        $temporaryToken = $this->verify(isResetToken: 0)
            ->assertOk()
            ->assertJsonPath('identical_code', 'registration_required_200')
            ->assertJsonPath('content.phone', self::PHONE)
            ->assertJsonPath('content.registration_required', true)
            ->json('content.temporary_token');

        $this->assertIsString($temporaryToken);
        $this->assertSame(40, strlen($temporaryToken));

        $this->registerWithOtp(temporaryToken: $temporaryToken)
            ->assertCreated()
            ->assertJsonPath('identical_code', 'registration_success_201')
            ->assertJsonPath('content.token_type', 'Bearer');

        $this->assertSame(1, (int) User::query()->where('phone', self::PHONE)->value('is_phone_verified'));
    }

    public function test_registration_rejects_a_temporary_token_firebase_never_issued(): void
    {
        $this->verify(isResetToken: 0)->assertOk();

        $this->registerWithOtp(temporaryToken: str_repeat('x', 40))
            ->assertUnprocessable()
            ->assertJsonPath('identical_code', 'phone_reverification_required_422');

        $this->assertFalse(User::query()->where('phone', self::PHONE)->exists());
    }

    public function test_a_non_boolean_reset_flag_is_rejected(): void
    {
        $this->verify(isResetToken: 'yes')->assertUnprocessable();
    }

    private function verify(int|string $isResetToken): TestResponse
    {
        return $this->postJson('api/v6/auth/firebase/verify', [
            'session_info' => self::SESSION_INFO,
            'phone_number' => self::PHONE,
            'code' => self::CODE,
            'is_reset_token' => $isResetToken,
        ]);
    }

    private function registerWithOtp(string $temporaryToken): TestResponse
    {
        return $this->postJson('api/v6/auth/register/otp', [
            'f_name' => 'Firebase',
            'l_name' => 'Customer',
            'phone' => self::PHONE,
            'temporary_token' => $temporaryToken,
        ]);
    }

    private function customerWithPhone(): User
    {
        return User::query()->forceCreate([
            'name' => 'Firebase Customer',
            'f_name' => 'Firebase',
            'l_name' => 'Customer',
            'email' => 'firebase-customer@example.test',
            'phone' => self::PHONE,
            'password' => Hash::make('old-password-1'),
            'is_active' => 1,
        ]);
    }
}
