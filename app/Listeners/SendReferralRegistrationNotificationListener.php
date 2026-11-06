<?php

namespace App\Listeners;

use App\Events\CustomerRegisteredViaReferralEvent;
use App\Traits\PushNotificationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendReferralRegistrationNotificationListener implements ShouldQueue
{
    // Queued: fire-and-forget notification; external SMTP/FCM calls get retries.
    public string $queue = 'default';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    use PushNotificationTrait;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CustomerRegisteredViaReferralEvent $event): void
    {
        $referralCustomer = $event->referralCustomer;
        $referrer = $event->referredBy;
        $fcmToken = $referrer?->cm_firebase_token ?? '';

        $postData = [
            'title' => translate('Someone registered using your referral code!'),
            'description' => translate('You earn a reward when someone orders with your referral code.'),
            'order_id' => '',
            'deliveryman_charge' => '',
            'expected_delivery_date' => '',
            'image' => '',
            'type' => 'referral_code_used',
        ];

        $this->sendPushNotificationToDevice(fcmToken: $fcmToken, data: $postData);
        $referralCustomer->registered_notify = 1;
        $referralCustomer->save();
    }
}
