<?php

use App\Models\NotificationMessage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COMMON_MODULE = 'common';

    private const RETAIL_MODULE = 'retail';

    private const COMMON_KEYS = [
        'customer' => [
            'message_from_delivery_man',
            'message_from_admin',
            'message_from_seller',
            'fund_added_by_admin_message',
            'your_referred_customer_has_been_place_order',
            'your_referred_customer_order_has_been_delivered',
        ],
        'seller' => [
            'withdraw_request_status_message',
            'message_from_customer',
            'message_from_delivery_man',
        ],
        'delivery_man' => [
            'message_from_seller',
            'message_from_admin',
            'message_from_customer',
            'cash_collect_by_admin_message',
            'cash_collect_by_seller_message',
            'withdraw_request_status_message',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('notification_messages', 'business_module')) {
            return;
        }

        DB::transaction(function (): void {
            foreach (self::COMMON_KEYS as $userType => $keys) {
                foreach ($keys as $key) {
                    $this->moveToCommonModule(userType: $userType, key: $key);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('notification_messages', 'business_module')) {
            return;
        }

        foreach (self::COMMON_KEYS as $userType => $keys) {
            DB::table('notification_messages')
                ->where(['business_module' => self::COMMON_MODULE, 'user_type' => $userType])
                ->whereIn('key', $keys)
                ->update(['business_module' => self::RETAIL_MODULE]);
        }
    }

    private function moveToCommonModule(string $userType, string $key): void
    {
        $scope = ['user_type' => $userType, 'key' => $key];

        $commonId = DB::table('notification_messages')
            ->where($scope)
            ->where('business_module', self::COMMON_MODULE)
            ->value('id');

        $commonId ??= DB::table('notification_messages')
            ->where($scope)
            ->where('business_module', self::RETAIL_MODULE)
            ->orderBy('id')
            ->value('id');

        if ($commonId === null) {
            return;
        }

        DB::table('notification_messages')->where('id', $commonId)->update(['business_module' => self::COMMON_MODULE]);

        $duplicateIds = DB::table('notification_messages')
            ->where($scope)
            ->where('id', '!=', $commonId)
            ->pluck('id');

        DB::table('translations')
            ->where('translationable_type', (new NotificationMessage)->getMorphClass())
            ->whereIn('translationable_id', $duplicateIds)
            ->delete();

        DB::table('notification_messages')->whereIn('id', $duplicateIds)->delete();
    }
};
