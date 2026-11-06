<?php

namespace Tests\Feature\Wholesale\PushNotification;

use App\Enums\BusinessModule;
use App\Enums\NotificationUserType;
use App\Models\Order;
use App\Models\User;
use App\Services\New\CustomerWalletService;
use App\Services\New\PushNotificationService;
use App\Traits\PushNotificationTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class CommonModulePayloadTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const CHAT_KEYS = [
        'message_from_delivery_man',
        'message_from_admin',
        'message_from_seller',
        'message_from_customer',
    ];

    private const CASH_COLLECT_KEYS = [
        'cash_collect_by_admin_message',
        'cash_collect_by_seller_message',
    ];

    private const REFERRAL_KEYS = [
        'your_referred_customer_has_been_place_order',
        'your_referred_customer_order_has_been_delivered',
    ];

    private const WITHDRAW_KEY = 'withdraw_request_status_message';

    private const WALLET_FUND_KEY = 'fund_added_by_admin_message';

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

    public static function commonKeyProvider(): array
    {
        $cases = [];

        foreach (PushNotificationService::COMMON_KEYS as $userType => $keys) {
            foreach ($keys as $key) {
                $cases[$userType.' '.$key] = [$userType, $key];
            }
        }

        return $cases;
    }

    #[DataProvider('commonKeyProvider')]
    public function test_every_common_key_sends_the_common_business_module(string $userType, string $key): void
    {
        $this->insertCommonMessage(userType: $userType, key: $key);

        $sentData = $this->sendCommonNotification(userType: $userType, key: $key);

        $this->assertSame(PushNotificationService::MODULE_COMMON, $sentData['business_module']);
        $this->assertSame($key, $sentData['message_key']);
    }

    public function test_referral_push_stays_common_on_a_wholesale_order(): void
    {
        $key = self::REFERRAL_KEYS[0];
        $this->insertCommonMessage(userType: NotificationUserType::CUSTOMER, key: $key);

        $sentData = $this->sendReferralNotification(key: $key, businessModule: BusinessModule::WHOLESALE);

        $this->assertSame(PushNotificationService::MODULE_COMMON, $sentData['business_module']);
    }

    public function test_key_outside_the_common_list_adds_no_business_module(): void
    {
        $sender = $this->capturingSender();

        $this->assertSame([], $sender->commonPayload(key: 'order_pending_message', userType: NotificationUserType::CUSTOMER));
        $this->assertSame([], $sender->commonPayload(key: self::CASH_COLLECT_KEYS[0], userType: NotificationUserType::CUSTOMER));
    }

    /** @return array<string, string> */
    private function sendCommonNotification(string $userType, string $key): array
    {
        if (in_array($key, self::REFERRAL_KEYS, true)) {
            return $this->sendReferralNotification(key: $key, businessModule: BusinessModule::RETAIL);
        }

        if ($key === self::WALLET_FUND_KEY) {
            return $this->sendWalletFundNotification();
        }

        $sender = $this->capturingSender();

        if (in_array($key, self::CHAT_KEYS, true)) {
            $sender->sendChat(key: $key, userType: $userType);
        }

        if (in_array($key, self::CASH_COLLECT_KEYS, true)) {
            $sender->sendCashCollect(key: $key, userType: $userType);
        }

        if ($key === self::WITHDRAW_KEY) {
            $sender->sendWithdrawStatus(key: $key, userType: $userType);
        }

        $this->assertCount(1, $sender->posted);

        return $sender->posted[0]['message']['data'];
    }

    /** @return array<string, string> */
    private function sendReferralNotification(string $key, string $businessModule): array
    {
        $customer = $this->requireFixture(User::query()->first(), 'customer');
        $customer->forceFill(['cm_firebase_token' => 'promoter-token'])->save();
        DB::table('referral_customers')->where('user_id', $customer->id)->delete();
        DB::table('referral_customers')->insert([
            'user_id' => $customer->id,
            'refer_by' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = (new Order)->forceFill(['id' => 0, 'business_module' => $businessModule]);
        $order->setRelation('customer', $customer);
        $order->setRelation('seller', null);
        $order->setRelation('deliveryMan', null);

        $sender = $this->capturingSender();
        $sender->sendOrder(key: $key, order: $order);

        $this->assertCount(1, $sender->posted);

        return $sender->posted[0]['message']['data'];
    }

    /** @return array<string, string> */
    private function sendWalletFundNotification(): array
    {
        $customer = $this->requireFixture(User::query()->first(), 'customer');
        $customer->cm_firebase_token = 'customer-token';

        $walletService = new class extends CustomerWalletService
        {
            public array $posted = [];

            protected function sendNotificationToHttp(?array $data): bool|string|null
            {
                $this->posted[] = $data;

                return true;
            }
        };
        $walletService->sendFundAddedNotification(customer: $customer, amount: 10);

        $this->assertCount(1, $walletService->posted);

        return $walletService->posted[0]['message']['data'];
    }

    private function insertCommonMessage(string $userType, string $key): void
    {
        DB::table('notification_messages')->where(['user_type' => $userType, 'key' => $key])->delete();
        DB::table('notification_messages')->insert([
            'business_module' => PushNotificationService::MODULE_COMMON,
            'user_type' => $userType,
            'key' => $key,
            'message' => 'Common message',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function capturingSender(): object
    {
        return new class
        {
            use PushNotificationTrait;

            public array $posted = [];

            public function sendChat(string $key, string $userType): void
            {
                $this->chattingNotification(
                    key: $key,
                    type: $userType,
                    userData: (object) ['fcm_token' => 'device-token', 'cm_firebase_token' => 'device-token', 'app_language' => 'en'],
                    messageForm: (object) ['f_name' => 'Sender', 'shop' => null],
                );
            }

            public function sendCashCollect(string $key, string $userType): void
            {
                $this->cashCollectNotification(key: $key, type: $userType, lang: 'en', amount: 10, fcmToken: 'device-token');
            }

            public function sendWithdrawStatus(string $key, string $userType): void
            {
                $this->withdrawStatusUpdateNotification(key: $key, type: $userType, lang: 'en', status: 1, fcmToken: 'device-token');
            }

            public function sendOrder(string $key, Order $order): void
            {
                $this->sendOrderNotification(key: $key, type: 'promoter', order: $order);
            }

            /** @return array{business_module?: string} */
            public function commonPayload(string $key, string $userType): array
            {
                return $this->commonBusinessModulePayload(key: $key, userType: $userType);
            }

            protected function sendNotificationToHttp(?array $data): bool|string|null
            {
                $this->posted[] = $data;

                return true;
            }
        };
    }
}
