<?php

namespace Tests\Feature\Api\V6;

use App\Services\New\HelpTopicService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class SharedConfigTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

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

    public function test_config_answers_grouped_sections_in_the_v6_envelope(): void
    {
        $response = $this->getJson('api/v6/config');

        $response->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->assertJsonStructure([
                'identical_code',
                'message',
                'content' => [
                    'company' => ['name', 'phone', 'email', 'country_code', 'timezone', 'logo_full_url', 'fav_icon_full_url', 'cover_image_full_url', 'reliability'],
                    'theme' => ['active', 'panel_version', 'colors' => ['primary', 'secondary', 'primary_light']],
                    'business' => ['mode', 'software_version', 'brand_enabled', 'digital_product_enabled', 'decimal_point', 'units', 'colors', 'popular_tags', 'product_unit_price_range' => ['min', 'max'], 'announcement', 'pixel_analytics'],
                    'currency' => ['default_id', 'model', 'symbol_position', 'list' => ['*' => ['id', 'name', 'symbol', 'code', 'exchange_rate', 'status']]],
                    'languages' => ['*' => ['code', 'name', 'direction']],
                    'vendor' => ['registration', 'pos', 'review_reply', 'can_edit_order_status', 'delivery_partner_setup'],
                    'order' => ['minimum_order_limit', 'minimum_order_amount' => ['status', 'amount', 'set_by_vendor'], 'billing_input_by_customer', 'guest_checkout', 'order_verification', 'refund_day_limit', 'upload_picture_on_delivery'],
                    'shipping' => ['method', 'in_house_shipping_type', 'country_restriction', 'zip_code_restriction', 'free_delivery' => ['status', 'responsibility', 'over_amount', 'over_amount_vendor'], 'map' => ['status', 'default_location']],
                    'payment' => ['digital_payment', 'cash_on_delivery', 'offline_payment' => ['status', 'image_url'], 'methods' => ['*' => ['key_name', 'title', 'image_url']]],
                    'wallet' => ['status', 'add_funds' => ['status', 'minimum_amount', 'maximum_amount']],
                    'loyalty_point' => ['status', 'exchange_rate', 'minimum_point'],
                    'referral' => ['status', 'signup_url'],
                    'authentication' => [
                        'customer' => ['login_options' => ['manual', 'otp', 'social'], 'social_mediums' => ['*' => ['medium', 'status', 'enabled_for_login']], 'verification' => ['required', 'email', 'phone', 'firebase'], 'forgot_password_method'],
                        'vendor' => ['forgot_password_method', 'forgot_password_sms_method'],
                        'deliveryman' => ['forgot_password_method'],
                        'otp_resend_seconds',
                    ],
                    'maintenance' => ['status', 'systems', 'message' => ['title', 'body', 'show_business_number', 'show_business_email'], 'duration' => ['type', 'start_date', 'end_date']],
                    'app_versions' => [
                        'customer' => ['android' => ['status', 'version', 'link'], 'ios' => ['status', 'version', 'link']],
                        'vendor' => ['android', 'ios'],
                        'deliveryman' => ['android', 'ios'],
                    ],
                    'in_house_shop' => ['id', 'name', 'slug', 'image_full_url', 'banner_full_url', 'temporary_close', 'vacation' => ['status', 'start_date', 'end_date', 'note']],
                    'tax' => ['type', 'is_included'],
                    'ai' => ['enabled', 'shopping_assistant'],
                    'media' => ['base_urls' => ['product', 'product_thumbnail', 'chatting'], 'upload_limits' => ['server_upload_max_filesize', 'server_post_max_size', 'image_max_size_mb', 'file_max_size_mb']],
                    'urls' => ['contact_us', 'brands', 'categories', 'customer_account', 'blog'],
                    'modules' => ['ai', 'auction', 'blog', 'builder', 'courier', 'tax_module', 'wholesale'],
                    'auction',
                ],
                'errors',
            ]);

        $content = $response->json('content');

        $this->assertIsInt($content['currency']['default_id']);
        $this->assertIsBool($content['payment']['digital_payment']);
        $this->assertIsBool($content['vendor']['registration']);
        $this->assertIsBool($content['maintenance']['status']);
        $this->assertIsInt($content['authentication']['otp_resend_seconds']);
        $this->assertContainsOnly('bool', $content['modules']);
        $this->assertSame(addon_published_status(moduleName: 'Wholesale'), $content['modules']['wholesale']);
        $this->assertSame(addon_published_status(moduleName: 'Auction'), $content['modules']['auction']);
        $this->assertLessThanOrEqual(15, count($content['business']['popular_tags']));
        $this->assertLessThanOrEqual($content['business']['product_unit_price_range']['max'], $content['business']['product_unit_price_range']['min']);
    }

    public function test_config_never_exposes_gateway_or_social_login_credentials(): void
    {
        $body = $this->getJson('api/v6/config')->assertOk()->getContent();

        foreach (['client_secret', 'client_id', 'live_values', 'test_values', 'web_api_key', 'merchant_key', 'additional_data'] as $secretKey) {
            $this->assertStringNotContainsString('"'.$secretKey.'"', $body);
        }
    }

    public function test_faq_lists_only_active_default_help_topics(): void
    {
        $helpTopicService = app(HelpTopicService::class);

        $activeTopic = $helpTopicService->create(data: [
            'type' => HelpTopicService::TYPE_DEFAULT,
            'question' => 'Api faq active question',
            'answer' => 'Api faq active answer',
            'status' => 1,
            'ranking' => 1,
        ]);
        $inactiveTopic = $helpTopicService->create(data: [
            'type' => HelpTopicService::TYPE_DEFAULT,
            'question' => 'Api faq inactive question',
            'answer' => 'Api faq inactive answer',
            'status' => 0,
            'ranking' => 2,
        ]);

        $response = $this->getJson('api/v6/faq');

        $response->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->assertJsonStructure(['content' => ['data' => ['*' => ['id', 'question', 'answer', 'ranking']]]]);

        $ids = collect($response->json('content.data'))->pluck('id');

        $this->assertTrue($ids->contains($activeTopic->id));
        $this->assertFalse($ids->contains($inactiveTopic->id));
    }
}
