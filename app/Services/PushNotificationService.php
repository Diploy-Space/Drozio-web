<?php

namespace App\Services;

use App\Enums\NotificationUserType;
use Exception;

class PushNotificationService
{
    /** Business-module discriminators — mirror config/business_modules.php keys. */
    public const MODULE_RETAIL = 'retail';

    public const MODULE_WHOLESALE = 'wholesale';

    public const MODULE_AUCTION = 'auction';

    /** Referral-only keys that exist for Retail but not Wholesale. */
    private const RETAIL_ONLY_KEYS = [
        'your_referred_customer_has_been_place_order',
        'your_referred_customer_order_has_been_delivered',
    ];

    /**
     * Canonical notification keys for a given business module and user type.
     * Returns an empty array when the pair defines no messages (drives which
     * user types the admin UI offers for a module).
     *
     * @return array<int, string>
     */
    public function getMessageKeyData(string $businessModule, string $userType): array
    {
        $map = match ($businessModule) {
            self::MODULE_WHOLESALE => $this->wholesaleKeyMap(),
            self::MODULE_AUCTION => $this->auctionKeyMap(),
            default => $this->retailKeyMap(),
        };

        return $map[$userType] ?? [];
    }

    /**
     * User types that actually define messages for the given business module.
     *
     * @return array<int, string>
     */
    public function getAvailableUserTypes(string $businessModule): array
    {
        return array_values(array_filter(
            NotificationUserType::values(),
            fn (string $userType) => ! empty($this->getMessageKeyData($businessModule, $userType))
        ));
    }

    /**
     * Retail order-lifecycle keys per user type (Auction keys now live under the
     * Auction module, not merged in here).
     *
     * @return array<string, array<int, string>>
     */
    private function retailKeyMap(): array
    {
        return [
            NotificationUserType::CUSTOMER => [
                'order_pending_message',
                'order_confirmation_message',
                'order_processing_message',
                'out_for_delivery_message',
                'order_delivered_message',
                'order_returned_message',
                'order_failed_message',
                'order_canceled',
                'order_refunded_message',
                'refund_request_canceled_message',
                'message_from_delivery_man',
                'message_from_admin',
                'message_from_seller',
                'fund_added_by_admin_message',
                'your_referred_customer_has_been_place_order',
                'your_referred_customer_order_has_been_delivered',
                'order_edit_message',
                'order_edit_return_amount_message',
            ],
            NotificationUserType::SELLER => [
                'new_order_message',
                'refund_request_message',
                'order_edit_message',
                'order_edit_due_payment_message',
                'withdraw_request_status_message',
                'message_from_customer',
                'message_from_delivery_man',
                'delivery_man_assign_by_admin_message',
                'order_delivered_message',
                'order_canceled',
                'order_refunded_message',
                'refund_request_canceled_message',
                'refund_request_status_changed_by_admin',
                'product_request_approved_message',
                'product_request_rejected_message',
            ],
            NotificationUserType::DELIVERY_MAN => [
                'new_order_assigned_message',
                'expected_delivery_date',
                'delivery_man_charge',
                'order_canceled',
                'order_rescheduled_message',
                'order_edit_message',
                'message_from_seller',
                'message_from_admin',
                'message_from_customer',
                'cash_collect_by_admin_message',
                'cash_collect_by_seller_message',
                'withdraw_request_status_message',
            ],
        ];
    }

    /**
     * Wholesale mirrors the Retail order lifecycle, minus Retail-only referral keys.
     *
     * @return array<string, array<int, string>>
     */
    private function wholesaleKeyMap(): array
    {
        $map = $this->retailKeyMap();

        foreach ($map as $userType => $keys) {
            $map[$userType] = array_values(array_diff($keys, self::RETAIL_ONLY_KEYS));
        }

        return $map;
    }

    /**
     * Auction keys, split by perspective: Customer holds both bidder and
     * customer-as-owner keys; Seller holds seller-as-owner keys.
     *
     * @return array<string, array<int, string>>
     */
    private function auctionKeyMap(): array
    {
        $ownerKeys = [
            'auction_expired_result',
            'auction_new_participation',
            'auction_new_bid',
            'auction_item_claimed',
            'auction_claim_payment_verified_owner',
            'auction_withdrawal_approved',
            'auction_withdrawal_rejected',
        ];

        $vendorKeys = array_merge($ownerKeys, [
            // Seller-as-auction-owner
            'auction_approved',
            'auction_denied',
            'auction_expired_result',
            'auction_new_participation',
            'auction_new_bid',
            'auction_item_claimed',
            'auction_claim_payment_verified_owner',
            'auction_withdrawal_approved',
            'auction_withdrawal_rejected',
        ]);

        return [
            NotificationUserType::CUSTOMER => array_merge([
                // Bidder (customer who participates in an auction)
                'auction_went_live',
                'auction_outbid',
                'auction_won',
                'auction_participation_payment_verified',
                'auction_claim_payment_verified',
                'auction_commission_payment_verified',
                'auction_delivery_ready',
                'auction_delivery_on_the_way',
                'auction_delivered',
                'auction_claim_expired',
                'auction_next_bidder',
                // Customer-as-auction-owner (customer who listed the auction)
                'auction_approved',
                'auction_denied',
                'auction_expired_result',
                'auction_new_participation',
                'auction_new_bid',
                'auction_item_claimed',
                'auction_claim_payment_verified_owner',
                'auction_withdrawal_approved',
                'auction_withdrawal_rejected',
            ], $ownerKeys),
            NotificationUserType::SELLER => $vendorKeys,
        ];
    }

    /**
     * Per-message display-label overrides for a business module and user type.
     * Keys absent from this map fall back to translate($key) in the view.
     * Each entry is ['token' => <translation key>, 'suffix' => <role label|null>].
     *
     * @return array<string, array{token: string, suffix: ?string}>
     */
    public function getMessageLabels(string $businessModule, string $userType): array
    {
        // Order-message wording tweaks apply to the customer of order-based modules.
        $customerOrderOverrides = [
            'out_for_delivery_message' => ['token' => 'order_out_for_delivery_message', 'suffix' => null],
            'order_canceled' => ['token' => 'order_canceled_message', 'suffix' => null],
        ];

        if ($businessModule === self::MODULE_AUCTION) {
            return $this->auctionLabelOverrides($userType);
        }

        if ($userType === NotificationUserType::CUSTOMER) {
            return $customerOrderOverrides;
        }

        return [];
    }

    /**
     * @return array<string, array{token: string, suffix: ?string}>
     */
    private function auctionLabelOverrides(string $userType): array
    {
        $bidderKeys = [
            'auction_went_live', 'auction_outbid', 'auction_won',
            'auction_participation_payment_verified', 'auction_claim_payment_verified',
            'auction_commission_payment_verified', 'auction_delivery_ready',
            'auction_delivery_on_the_way', 'auction_delivered',
        ];

        $labels = [];
        foreach ($this->auctionKeyMap()[$userType] ?? [] as $key) {
            $labels[$key] = [
                'token' => $key,
                'suffix' => in_array($key, $bidderKeys, true) ? 'Bidder' : 'Owner',
            ];
        }

        return $labels;
    }

    public function getAddData(string $businessModule, string $userType, string $value): array
    {
        return [
            'business_module' => $businessModule,
            'user_type' => $userType,
            'key' => $value,
            'message' => $this->getDefaultMessage($value),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function getDefaultMessage(string $key): string
    {
        $auctionDefaults = [
            'auction_went_live' => 'The auction you joined is now live! Start bidding now.',
            'auction_outbid' => 'You have been outbid. Place a higher bid to stay in the lead.',
            'auction_won' => 'Congratulations! You have won the auction. Proceed to claim your item.',
            'auction_participation_payment_verified' => 'Your participation payment has been verified. You can now place bids.',
            'auction_claim_payment_verified' => 'Your claim payment has been verified. Your item will be prepared for delivery.',
            'auction_commission_payment_verified' => 'Your commission payment has been verified.',
            'auction_delivery_ready' => 'Your auction item is ready for delivery.',
            'auction_delivery_on_the_way' => 'Your auction item is on the way.',
            'auction_delivered' => 'Your auction item has been delivered.',
            'auction_claim_expired' => 'Your claim time for this auction has expired.',
            'auction_next_bidder' => 'You are now eligible to claim this auction. Claim before the time runs out.',
            'auction_approved' => 'Your auction product has been approved. It will go live on the scheduled date.',
            'auction_denied' => 'Your auction product has been denied. Please review the feedback and resubmit.',
            'auction_expired_result' => 'Your auction has ended. Check the result in your dashboard.',
            'auction_new_participation' => 'A new participant has joined your auction.',
            'auction_new_bid' => 'A new bid has been placed on your auction.',
            'auction_item_claimed' => 'The winner has claimed your auction item.',
            'auction_claim_payment_verified_owner' => 'The winner\'s claim payment for your auction item has been verified.',
            'auction_withdrawal_approved' => 'Your withdrawal request has been approved.',
            'auction_withdrawal_rejected' => 'Your withdrawal request has been rejected.',
        ];

        return $auctionDefaults[$key] ?? ('customize your '.str_replace('_', ' ', $key).' message');
    }

    public function getUpdateData(object $request, string $message, string $status, string $lang): array
    {
        $langArray = $request->$lang ?? [];
        $index = is_array($langArray) ? array_search('en', $langArray) : false;
        $messageValue = $request->$message ?? null;

        if (is_array($messageValue)) {
            $msg = ($index !== false && isset($messageValue[$index])) ? $messageValue[$index] : null;
        } elseif (is_string($messageValue)) {
            $msg = $messageValue;
        } else {
            $msg = null;
        }

        $statusVal = $request->$status ?? false;

        return [
            'message' => $msg,
            'status' => $statusVal,
            'updated_at' => now(),
        ];
    }

    public function getFCMCredentialsArray(object|array $request): array
    {
        return [
            'apiKey' => $request['apiKey'],
            'authDomain' => $request['authDomain'],
            'projectId' => $request['projectId'],
            'storageBucket' => $request['storageBucket'],
            'messagingSenderId' => $request['messagingSenderId'],
            'appId' => $request['appId'],
            'measurementId' => $request['measurementId'],
        ];
    }

    /**
     * @throws Exception
     */
    public function firebaseConfigFileGenerate(array $config): void
    {
        // JSON-encode each value so it is emitted as a safe JS string literal —
        // a value containing a quote/backslash/newline can't break out of the
        // generated service-worker and inject arbitrary code.
        $apiKey = json_encode($config['apiKey'] ?? '');
        $authDomain = json_encode($config['authDomain'] ?? '');
        $projectId = json_encode($config['projectId'] ?? '');
        $storageBucket = json_encode($config['storageBucket'] ?? '');
        $messagingSenderId = json_encode($config['messagingSenderId'] ?? '');
        $appId = json_encode($config['appId'] ?? '');
        $measurementId = json_encode($config['measurementId'] ?? '');

        $filePaths = [
            base_path('firebase-messaging-sw.js'),
            base_path('public/firebase-messaging-sw.js'),
        ];

        $fileContent = <<<JS
            importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
            importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');
            importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-auth.js');

            firebase.initializeApp({
                apiKey: {$apiKey},
                authDomain: {$authDomain},
                projectId: {$projectId},
                storageBucket: {$storageBucket},
                messagingSenderId: {$messagingSenderId},
                appId: {$appId},
                measurementId: {$measurementId}
            });

            const messaging = firebase.messaging();
            messaging.setBackgroundMessageHandler(function(payload) {
                return self.registration.showNotification(payload.data.title, {
                    body: payload.data.body || '',
                    icon: payload.data.icon || ''
                });
            });
            JS;

        foreach ($filePaths as $filePath) {
            $this->writeToFile($filePath, $fileContent);
        }
    }

    /**
     * @throws Exception
     */
    private function writeToFile(string $filePath, string $fileContent): void
    {
        try {
            if (! file_exists($filePath)) {
                if (file_put_contents($filePath, '') === false) {
                    throw new Exception("Failed to create file: $filePath");
                }
            }

            if (! is_writable($filePath)) {
                throw new Exception("File exists but is not writable: $filePath");
            }

            if (file_put_contents($filePath, $fileContent, LOCK_EX) === false) {
                throw new Exception("Failed to write to file: $filePath");
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }
}
