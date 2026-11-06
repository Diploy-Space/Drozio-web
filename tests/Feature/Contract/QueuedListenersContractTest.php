<?php

namespace Tests\Feature\Contract;

use Illuminate\Contracts\Queue\ShouldQueue;
use PHPUnit\Framework\Attributes\Test;

/**
 * Guards that notification listeners run on named queues with retry/backoff.
 *
 * These listeners only send fire-and-forget email/FCM push (no response depends
 * on them), so they must be queued off the request thread. High-priority queue
 * for order + OTP/auth notifications; default for the rest.
 */
class QueuedListenersContractTest extends ContractTestCase
{
    private const HIGH = [
        'OrderPlacedListener', 'OrderStatusListener', 'EmailVerificationListener',
        'PasswordResetListener', 'DigitalProductOtpVerificationListener',
        'DeliverymanPasswordResetListener',
    ];

    private const DEFAULT = [
        'AddFundToWalletListener', 'CashCollectListener', 'ChattingListener',
        'CustomerRegistrationListener', 'CustomerStatusUpdateListener',
        'DigitalProductDownloadListener', 'MaintenanceModeNotificationListener',
        'OrderEditDuePaymentListener', 'OrderEditListener', 'OrderEditReturnPaymentListener',
        'ProductRequestStatusUpdateListener', 'RefundListener', 'RequestProductRestockListener',
        'RestockProductNotificationListener', 'SendReferralRegistrationNotificationListener',
        'VendorRegistrationListener', 'WithdrawStatusUpdateListener',
    ];

    #[Test]
    public function notification_listeners_are_queued_on_named_queues_with_backoff(): void
    {
        foreach (self::HIGH as $name) {
            $this->assertListenerQueued($name, 'high');
        }
        foreach (self::DEFAULT as $name) {
            $this->assertListenerQueued($name, 'default');
        }
    }

    private function assertListenerQueued(string $name, string $expectedQueue): void
    {
        $class = "App\\Listeners\\{$name}";
        $listener = new $class();

        $this->assertInstanceOf(ShouldQueue::class, $listener, "{$name} must implement ShouldQueue.");
        $this->assertSame($expectedQueue, $listener->queue, "{$name} must be on the '{$expectedQueue}' queue.");
        $this->assertSame(3, $listener->tries, "{$name} must set tries for external-service retries.");
        $this->assertSame([10, 30, 60], $listener->backoff, "{$name} must set a backoff schedule.");
    }
}
