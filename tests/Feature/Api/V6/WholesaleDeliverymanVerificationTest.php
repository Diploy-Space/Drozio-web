<?php

namespace Tests\Feature\Api\V6;

use App\Models\DeliveryHistory;
use App\Models\Order;
use App\Models\OrderDeliveryVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Api\Support\InteractsWithWholesaleDeliveryman;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleDeliverymanVerificationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleDeliveryman;
    use RequiresWholesaleAddon;

    private const BASE = 'api/v6/wholesale/delivery-man';

    private const ORDER_ENDPOINT_PATHS = [
        'verification-code',
        'verification-code/verify',
        'delivery-proofs',
    ];

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

    public function test_send_verification_code_stores_new_code_and_pushes_to_customer(): void
    {
        $this->fakePushTransport();
        $deliveryMan = $this->createDeliveryMan();
        $customer = $this->requireFixture(User::query()->first(), 'customer');
        $customer->forceFill(['cm_firebase_token' => 'wholesale-deliveryman-token'])->save();

        $order = $this->createWholesaleOrder($deliveryMan, ['customer_id' => $customer->id, 'is_guest' => 0]);

        $response = $this->postJson(
            self::BASE.'/orders/'.$order->id.'/verification-code',
            [],
            $this->deliveryManHeaders($deliveryMan),
        );

        $response->assertOk()->assertJson(['identical_code' => 'delivery_verification_code_sent_200']);

        $storedCode = (string) Order::query()->whereKey($order->id)->value('verification_code');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $storedCode);
        Http::assertSent(static fn ($request): bool => str_contains($request->url(), 'fcm.googleapis.com'));
    }

    public function test_send_verification_code_fails_for_a_guest_order(): void
    {
        $this->fakePushTransport();
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan, ['customer_id' => null, 'is_guest' => 1]);

        $response = $this->postJson(
            self::BASE.'/orders/'.$order->id.'/verification-code',
            [],
            $this->deliveryManHeaders($deliveryMan),
        );

        $response->assertStatus(403)->assertJson(['identical_code' => 'delivery_verification_code_send_failed_403']);
    }

    public function test_verify_code_accepts_the_right_code(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);
        $order->forceFill(['verification_code' => '482913'])->save();

        $response = $this->postJson(
            self::BASE.'/orders/'.$order->id.'/verification-code/verify',
            ['verification_code' => '482913'],
            $this->deliveryManHeaders($deliveryMan),
        );

        $response->assertOk()->assertJson(['identical_code' => 'delivery_verification_code_verified_200']);
        $this->assertSame(1, (int) Order::query()->whereKey($order->id)->value('verification_status'));
    }

    public function test_verify_code_rejects_the_wrong_code(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);
        $order->forceFill(['verification_code' => '482913'])->save();

        $response = $this->postJson(
            self::BASE.'/orders/'.$order->id.'/verification-code/verify',
            ['verification_code' => '111111'],
            $this->deliveryManHeaders($deliveryMan),
        );

        $response->assertStatus(403)->assertJson(['identical_code' => 'delivery_verification_code_invalid_403']);
    }

    public function test_verify_code_is_throttled_after_the_limiters_allowance(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);
        $order->forceFill(['verification_code' => '482913'])->save();

        for ($attempt = 1; $attempt <= 8; $attempt++) {
            $this->postJson(
                self::BASE.'/orders/'.$order->id.'/verification-code/verify',
                ['verification_code' => '111111'],
                $this->deliveryManHeaders($deliveryMan),
            )->assertStatus(403);
        }

        $this->postJson(
            self::BASE.'/orders/'.$order->id.'/verification-code/verify',
            ['verification_code' => '111111'],
            $this->deliveryManHeaders($deliveryMan),
        )->assertStatus(429);
    }

    public function test_store_delivery_proofs_creates_a_row_per_image(): void
    {
        Storage::fake('public');
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);

        $response = $this->postJson(
            self::BASE.'/orders/'.$order->id.'/delivery-proofs',
            ['image' => [
                UploadedFile::fake()->image('proof-1.jpg', 10, 10),
                UploadedFile::fake()->image('proof-2.jpg', 10, 10),
            ]],
            $this->deliveryManHeaders($deliveryMan),
        );

        $response->assertOk()->assertJson(['identical_code' => 'delivery_proofs_uploaded_200']);
        $this->assertSame(2, OrderDeliveryVerification::query()->where('order_id', $order->id)->count());
    }

    public function test_record_location_writes_a_row_for_an_assigned_wholesale_order(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);

        $response = $this->postJson(self::BASE.'/locations', [
            'order_id' => $order->id,
            'latitude' => '23.780636',
            'longitude' => '90.419992',
            'location' => 'Wholesale delivery-man test location',
        ], $this->deliveryManHeaders($deliveryMan));

        $response->assertCreated()->assertJson(['identical_code' => 'location_recorded_201']);
        $this->assertSame(1, DeliveryHistory::query()->where([
            'order_id' => $order->id,
            'deliveryman_id' => $deliveryMan->id,
        ])->count());
    }

    public function test_record_location_refuses_a_retail_order(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createRetailOrder($deliveryMan);

        $response = $this->postJson(self::BASE.'/locations', [
            'order_id' => $order->id,
            'latitude' => '23.780636',
            'longitude' => '90.419992',
            'location' => 'Wholesale delivery-man test location',
        ], $this->deliveryManHeaders($deliveryMan));

        $response->assertStatus(404)->assertJson(['identical_code' => 'order_not_assigned_404']);
    }

    public function test_record_location_refuses_another_deliverymans_order(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $otherDeliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($otherDeliveryMan);

        $response = $this->postJson(self::BASE.'/locations', [
            'order_id' => $order->id,
            'latitude' => '23.780636',
            'longitude' => '90.419992',
            'location' => 'Wholesale delivery-man test location',
        ], $this->deliveryManHeaders($deliveryMan));

        $response->assertStatus(404)->assertJson(['identical_code' => 'order_not_assigned_404']);
    }

    public function test_verification_endpoints_refuse_a_retail_order_id(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createRetailOrder($deliveryMan);

        foreach (self::ORDER_ENDPOINT_PATHS as $path) {
            $this->postJson(
                self::BASE.'/orders/'.$order->id.'/'.$path,
                $path === 'verification-code/verify' ? ['verification_code' => '111111'] : [],
                $this->deliveryManHeaders($deliveryMan),
            )->assertStatus(404)->assertJson(['identical_code' => 'order_not_found_404']);
        }
    }

    public function test_endpoints_refuse_access_when_wholesale_is_unavailable(): void
    {
        $this->disableWholesaleModule();
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);

        foreach (self::ORDER_ENDPOINT_PATHS as $path) {
            $this->postJson(
                self::BASE.'/orders/'.$order->id.'/'.$path,
                $path === 'verification-code/verify' ? ['verification_code' => '111111'] : [],
                $this->deliveryManHeaders($deliveryMan),
            )->assertStatus(403)->assertJson(['identical_code' => 'wholesale_feature_disabled_403']);
        }

        $this->postJson(self::BASE.'/locations', [
            'order_id' => $order->id,
            'latitude' => '23.780636',
            'longitude' => '90.419992',
            'location' => 'Wholesale delivery-man test location',
        ], $this->deliveryManHeaders($deliveryMan))->assertStatus(403)->assertJson(['identical_code' => 'wholesale_feature_disabled_403']);
    }

    private function fakePushTransport(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/*' => Http::response(['access_token' => 'wholesale-deliveryman-test-token'], 200),
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'wholesale-deliveryman-test-message'], 200),
        ]);
    }
}
