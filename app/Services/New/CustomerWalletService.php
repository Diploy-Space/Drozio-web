<?php

namespace App\Services\New;

use App\Traits\PushNotificationTrait;
use Illuminate\Database\Eloquent\Model;

class CustomerWalletService
{
    use PushNotificationTrait;

    private const NOTIFICATION_KEY = 'fund_added_by_admin_message';

    private const NOTIFICATION_USER_TYPE = 'customer';

    public function sendFundAddedNotification(?Model $customer, float $amount): bool
    {
        $customerFCMToken = $customer?->cm_firebase_token;

        if (empty($customerFCMToken)) {
            return true;
        }

        $message = $this->pushNotificationMessage(
            self::NOTIFICATION_KEY,
            self::NOTIFICATION_USER_TYPE,
            $customer?->app_language ?? getDefaultLanguage(),
        );

        if (! $message) {
            return true;
        }

        $this->sendPushNotificationToDevice($customerFCMToken, [
            'title' => setCurrencySymbol(amount: currencyConverter(amount: $amount), currencyCode: getCurrencyCode(type: 'default')).' '.translate('Fund added'),
            'description' => $message,
            'image' => '',
            'type' => 'wallet',
            'message_key' => self::NOTIFICATION_KEY,
            ...$this->commonBusinessModulePayload(key: self::NOTIFICATION_KEY, userType: self::NOTIFICATION_USER_TYPE),
        ]);

        return true;
    }
}
