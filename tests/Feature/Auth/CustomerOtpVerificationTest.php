<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\BusinessSetting;
use App\Models\PasswordReset;
use App\Models\PhoneOrEmailVerification;
use App\Models\User;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\Auth\VerificationService;
use App\Utils\Helpers;
use App\View\Composers\WebConfigComposer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class CustomerOtpVerificationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const PHONE = '+8801900000098';

    private const CODE = '445566';

    private const SESSION_INFO = 'firebase-session-info';

    private const RESEND_SECONDS = 60;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
        $this->bootStorefrontViewData();
        $this->bootActiveTheme();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);

        BusinessSetting::query()->updateOrInsert(['type' => 'otp_resend_time'], ['value' => self::RESEND_SECONDS]);
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_the_firebase_code_is_accepted_for_a_password_reset_and_the_issued_token_resets_the_password(): void
    {
        $this->enableFirebaseOtp();
        Http::fake(['identitytoolkit.googleapis.com/*' => Http::response(['phoneNumber' => self::PHONE])]);

        $customer = $this->customerWithPhone();
        app(PasswordResetService::class)->issueOtp(identity: self::PHONE, userType: UserType::CUSTOMER, token: self::SESSION_INFO);

        $response = $this->post(route('customer.auth.verify-recover-password'), [
            'identity' => base64_encode(self::PHONE),
            'token' => self::CODE,
            'g-recaptcha-response' => 'recaptcha-token',
        ]);

        $response->assertOk();

        $resetToken = $response->viewData('token');

        $this->assertNotSame(self::CODE, $resetToken);
        $this->assertSame($resetToken, PasswordReset::query()->where('identity', self::PHONE)->value('token'));

        $this->post(route('customer.auth.password-recovery'), [
            'identity' => base64_encode(self::PHONE),
            'reset_token' => $resetToken,
            'password' => 'new-password-1',
            'confirm_password' => 'new-password-1',
        ])->assertRedirect('/');

        $this->assertTrue(Hash::check('new-password-1', $customer->refresh()->password));
    }

    public function test_a_code_firebase_rejects_is_refused_for_a_password_reset(): void
    {
        $this->enableFirebaseOtp();
        Http::fake(['identitytoolkit.googleapis.com/*' => Http::response(['error' => ['message' => 'INVALID_CODE']], 400)]);

        $this->customerWithPhone();
        app(PasswordResetService::class)->issueOtp(identity: self::PHONE, userType: UserType::CUSTOMER, token: self::SESSION_INFO);

        $this->from(route('customer.auth.recover-password'))
            ->post(route('customer.auth.verify-recover-password'), [
                'identity' => base64_encode(self::PHONE),
                'token' => self::CODE,
                'g-recaptcha-response' => 'recaptcha-token',
            ])
            ->assertRedirect(route('customer.auth.recover-password'));

        $this->assertSame(self::SESSION_INFO, PasswordReset::query()->where('identity', self::PHONE)->value('token'));
        $this->assertSame(1, (int) PasswordReset::query()->where('identity', self::PHONE)->value('otp_hit_count'));
    }

    public function test_the_verify_page_counts_down_from_the_password_reset_code(): void
    {
        app(PasswordResetService::class)->issueOtp(identity: self::PHONE, userType: UserType::CUSTOMER);

        $response = $this->get(route('customer.auth.login.verify-account', [
            'identity' => base64_encode(self::PHONE),
            'action' => base64_encode('password-reset'),
        ]));

        $response->assertOk();

        $this->assertGreaterThan(0, $response->viewData('getTimeInSecond'));
        $this->assertSame(route('customer.auth.verify-recover-password'), $response->viewData('otpVerifyUrl'));
        $this->assertSame(route('customer.auth.resend-otp-reset-password'), $response->viewData('otpResendUrl'));
    }

    public function test_the_verify_page_resends_a_login_code_through_the_login_route(): void
    {
        app(VerificationService::class)->updateOrCreateToken(identity: self::PHONE, token: self::CODE);

        $response = $this->get(route('customer.auth.login.verify-account', ['identity' => base64_encode(self::PHONE)]));

        $response->assertOk()
            ->assertDontSee(route('customer.auth.resend-otp-reset-password'))
            ->assertSee(route('customer.auth.resend-otp'));

        $this->assertGreaterThan(0, $response->viewData('getTimeInSecond'));
    }

    public function test_a_new_login_code_restarts_the_resend_clock_and_the_attempt_count(): void
    {
        PhoneOrEmailVerification::query()->forceCreate([
            'phone_or_email' => self::PHONE,
            'token' => 'stale-code',
            'otp_hit_count' => 4,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $verificationService = app(VerificationService::class);
        $verificationService->updateOrCreateToken(identity: self::PHONE, token: self::CODE);

        $this->assertGreaterThan(0, $verificationService->secondsUntilResendAllowed(identity: self::PHONE, resendSeconds: self::RESEND_SECONDS));
        $this->assertSame(0, (int) PhoneOrEmailVerification::query()->where('phone_or_email', self::PHONE)->value('otp_hit_count'));
    }

    private function bootStorefrontViewData(): void
    {
        Paginator::useBootstrap();
        Helpers::currency_load();
        View::share('language', getWebConfig(name: 'language') ?? []);
        View::composer('*', WebConfigComposer::class);
    }

    private function bootActiveTheme(): void
    {
        $themePath = base_path('resources/themes/'.config('app.theme'));

        if (! defined('VIEW_FILE_NAMES')) {
            define('VIEW_FILE_NAMES', include $themePath.'/file_names.php');
        }

        $this->app['view']->addLocation($themePath);
    }

    private function enableFirebaseOtp(): void
    {
        BusinessSetting::query()->updateOrInsert(
            ['type' => 'firebase_otp_verification'],
            ['value' => json_encode(['status' => 1, 'web_api_key' => 'test-key'])],
        );

        BusinessSetting::query()->updateOrInsert(
            ['type' => 'fcm_credentials'],
            ['value' => json_encode(['apiKey' => 'test-key'])],
        );
    }

    private function customerWithPhone(): User
    {
        return User::query()->forceCreate([
            'name' => 'Otp Customer',
            'f_name' => 'Otp',
            'l_name' => 'Customer',
            'email' => 'otp-customer@example.test',
            'phone' => self::PHONE,
            'password' => Hash::make('old-password-1'),
            'is_active' => 1,
        ]);
    }
}
