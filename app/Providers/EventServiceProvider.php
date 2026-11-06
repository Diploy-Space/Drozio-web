<?php

namespace App\Providers;

use App\Events\AddFundToWalletEvent;
use App\Events\AdminPasswordResetEvent;
use App\Events\CashCollectEvent;
use App\Events\ChattingEvent;
use App\Events\CustomerRegisteredViaReferralEvent;
use App\Events\CustomerRegistrationEvent;
use App\Events\CustomerStatusUpdateEvent;
use App\Events\DeliverymanPasswordResetEvent;
use App\Events\DigitalProductDownloadEvent;
use App\Events\DigitalProductOtpVerificationEvent;
use App\Events\EmailVerificationEvent;
use App\Events\MaintenanceModeNotificationEvent;
use App\Events\OrderEditDuePaymentEvent;
use App\Events\OrderEditEvent;
use App\Events\OrderEditReturnPaymentEvent;
use App\Events\OrderPaymentReceivedEvent;
use App\Events\OrderPlacedEvent;
use App\Events\OrderStatusEvent;
use App\Events\OtpSmsEvent;
use App\Events\PasswordResetEvent;
use App\Events\ProductRequestStatusUpdateEvent;
use App\Events\RefundEvent;
use App\Events\RequestProductRestockEvent;
use App\Events\RestockProductNotificationEvent;
use App\Events\VendorRegistrationEvent;
use App\Events\WithdrawStatusUpdateEvent;
use App\Listeners\AddFundToWalletListener;
use App\Listeners\AdminPasswordResetListener;
use App\Listeners\CashCollectListener;
use App\Listeners\ChattingListener;
use App\Listeners\CourierShipmentDispatchedListener;
use App\Listeners\CourierShipmentStatusListener;
use App\Listeners\CustomerRegistrationListener;
use App\Listeners\CustomerStatusUpdateListener;
use App\Listeners\DeliverymanPasswordResetListener;
use App\Listeners\DigitalProductDownloadListener;
use App\Listeners\DigitalProductOtpVerificationListener;
use App\Listeners\EmailVerificationListener;
use App\Listeners\MaintenanceModeNotificationListener;
use App\Listeners\OrderEditDuePaymentListener;
use App\Listeners\OrderEditListener;
use App\Listeners\OrderEditReturnPaymentListener;
use App\Listeners\OrderPaymentReceivedListener;
use App\Listeners\OrderPlacedListener;
use App\Listeners\OrderStatusListener;
use App\Listeners\OtpSmsListener;
use App\Listeners\PasswordResetListener;
use App\Listeners\ProductRequestStatusUpdateListener;
use App\Listeners\RefundListener;
use App\Listeners\RequestProductRestockListener;
use App\Listeners\RestockProductNotificationListener;
use App\Listeners\SendReferralRegistrationNotificationListener;
use App\Listeners\VendorRegistrationListener;
use App\Listeners\WithdrawStatusUpdateListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Courier\app\Events\ShipmentDispatched;
use Modules\Courier\app\Events\ShipmentStatusUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AddFundToWalletEvent::class => [
            AddFundToWalletListener::class,
        ],
        DigitalProductOtpVerificationEvent::class => [
            DigitalProductOtpVerificationListener::class,
        ],
        DeliverymanPasswordResetEvent::class => [
            DeliverymanPasswordResetListener::class,
        ],
        EmailVerificationEvent::class => [
            EmailVerificationListener::class,
        ],
        OtpSmsEvent::class => [
            OtpSmsListener::class,
        ],
        AdminPasswordResetEvent::class => [
            AdminPasswordResetListener::class,
        ],
        PasswordResetEvent::class => [
            PasswordResetListener::class,
        ],
        OrderPlacedEvent::class => [
            OrderPlacedListener::class,
        ],
        OrderPaymentReceivedEvent::class => [
            OrderPaymentReceivedListener::class,
        ],
        OrderEditEvent::class => [
            OrderEditListener::class,
        ],
        OrderEditDuePaymentEvent::class => [
            OrderEditDuePaymentListener::class,
        ],
        OrderEditReturnPaymentEvent::class => [
            OrderEditReturnPaymentListener::class,
        ],
        OrderStatusEvent::class => [
            OrderStatusListener::class,
        ],
        ChattingEvent::class => [
            ChattingListener::class,
        ],
        RefundEvent::class => [
            RefundListener::class,
        ],
        VendorRegistrationEvent::class => [
            VendorRegistrationListener::class,
        ],
        CustomerRegistrationEvent::class => [
            CustomerRegistrationListener::class,
        ],
        CustomerRegisteredViaReferralEvent::class => [
            SendReferralRegistrationNotificationListener::class,
        ],
        CustomerStatusUpdateEvent::class => [
            CustomerStatusUpdateListener::class,
        ],
        WithdrawStatusUpdateEvent::class => [
            WithdrawStatusUpdateListener::class,
        ],
        CashCollectEvent::class => [
            CashCollectListener::class,
        ],
        ProductRequestStatusUpdateEvent::class => [
            ProductRequestStatusUpdateListener::class,
        ],
        DigitalProductDownloadEvent::class => [
            DigitalProductDownloadListener::class,
        ],
        MaintenanceModeNotificationEvent::class => [
            MaintenanceModeNotificationListener::class,
        ],
        RequestProductRestockEvent::class => [
            RequestProductRestockListener::class,
        ],
        RestockProductNotificationEvent::class => [
            RestockProductNotificationListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        if (addon_published_status('Courier') && class_exists(ShipmentStatusUpdated::class)) {
            Event::listen(
                ShipmentStatusUpdated::class,
                CourierShipmentStatusListener::class,
            );
        }

        if (addon_published_status('Courier') && class_exists(ShipmentDispatched::class)) {
            Event::listen(
                ShipmentDispatched::class,
                CourierShipmentDispatchedListener::class,
            );
        }
    }
}
