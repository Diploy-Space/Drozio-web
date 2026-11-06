<?php

namespace App\Services\New;

use App\Enums\NotificationUserType;
use Exception;

class PushNotificationService
{
    public const MODULE_RETAIL = 'retail';

    public const MODULE_WHOLESALE = 'wholesale';

    public const MODULE_AUCTION = 'auction';

    public const MODULE_COMMON = 'common';

    public const COMMON_KEYS = [
        NotificationUserType::CUSTOMER => [
            'message_from_delivery_man',
            'message_from_admin',
            'message_from_seller',
            'fund_added_by_admin_message',
            'your_referred_customer_has_been_place_order',
            'your_referred_customer_order_has_been_delivered',
        ],
        NotificationUserType::SELLER => [
            'withdraw_request_status_message',
            'message_from_customer',
            'message_from_delivery_man',
        ],
        NotificationUserType::DELIVERY_MAN => [
            'message_from_seller',
            'message_from_admin',
            'message_from_customer',
            'cash_collect_by_admin_message',
            'cash_collect_by_seller_message',
            'withdraw_request_status_message',
        ],
    ];

    private const WHOLESALE_ONLY_CUSTOMER_KEYS = [
        'refund_request_message',
        'refund_request_approved_message',
        'order_payment_confirmed_message',
    ];

    private const WHOLESALE_ONLY_SELLER_KEYS = [
        'order_confirmation_message',
        'order_processing_message',
        'out_for_delivery_message',
        'order_returned_message',
        'order_failed_message',
        'order_payment_confirmed_message',
        'campaign_join_request_approved_message',
        'campaign_join_request_denied_message',
        'new_product_review_message',
    ];

    private const WHOLESALE_ONLY_DELIVERY_MAN_KEYS = [
        'order_processing_message',
        'out_for_delivery_message',
        'order_delivered_message',
        'order_paused_message',
        'order_resumed_message',
        'order_payment_confirmed_message',
    ];

    private const FIREBASE_CONFIG_FILES = [
        'firebase-messaging-sw-config.js',
        'public/firebase-messaging-sw-config.js',
    ];

    private const FIREBASE_CONFIG_KEYS = ['apiKey', 'authDomain', 'projectId', 'storageBucket', 'messagingSenderId', 'appId', 'measurementId'];

    /** @return array<int, string> */
    public function getMessageKeyData(string $businessModule, string $userType): array
    {
        $map = match ($businessModule) {
            self::MODULE_WHOLESALE => $this->wholesaleKeyMap(),
            self::MODULE_AUCTION => $this->auctionKeyMap(),
            self::MODULE_COMMON => self::COMMON_KEYS,
            default => $this->retailKeyMap(),
        };

        return $map[$userType] ?? [];
    }

    /** @return array<int, string> */
    public function getAvailableUserTypes(string $businessModule): array
    {
        return array_values(array_filter(
            NotificationUserType::values(),
            fn (string $userType) => ! empty($this->getMessageKeyData($businessModule, $userType))
        ));
    }

    /** @return array<string, array{token: string, suffix: ?string}> */
    public function getMessageLabels(string $businessModule, string $userType): array
    {
        if ($businessModule === self::MODULE_AUCTION) {
            return $this->auctionLabelOverrides($userType);
        }

        if ($userType === NotificationUserType::CUSTOMER) {
            return [
                'out_for_delivery_message' => ['token' => 'order_out_for_delivery_message', 'suffix' => null],
                'order_canceled' => ['token' => 'order_canceled_message', 'suffix' => null],
            ];
        }

        return [];
    }

    public function getDefaultMessage(string $key): string
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

    /** @param  array<string, mixed>  $config */
    public function firebaseConfigFileGenerate(array $config): void
    {
        $webConfig = [];

        foreach (self::FIREBASE_CONFIG_KEYS as $key) {
            $webConfig[$key] = (string) ($config[$key] ?? '');
        }

        $fileContent = 'self.firebaseMessagingConfig = '.json_encode($webConfig, JSON_UNESCAPED_SLASHES).';'.PHP_EOL;

        foreach (self::FIREBASE_CONFIG_FILES as $file) {
            $this->writeToFile(base_path($file), $fileContent);
        }
    }

    /** @return array<string, array<int, string>> */
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
                'order_edit_message',
                'order_edit_return_amount_message',
            ],
            NotificationUserType::SELLER => [
                'new_order_message',
                'refund_request_message',
                'order_edit_message',
                'order_edit_due_payment_message',
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
            ],
        ];
    }

    /** @return array<string, array<int, string>> */
    private function wholesaleKeyMap(): array
    {
        $wholesaleOnlyKeys = [
            NotificationUserType::CUSTOMER => self::WHOLESALE_ONLY_CUSTOMER_KEYS,
            NotificationUserType::SELLER => self::WHOLESALE_ONLY_SELLER_KEYS,
            NotificationUserType::DELIVERY_MAN => self::WHOLESALE_ONLY_DELIVERY_MAN_KEYS,
        ];
        $map = $this->retailKeyMap();

        foreach ($map as $userType => $keys) {
            $map[$userType] = array_values(array_unique(array_merge(
                $keys,
                $wholesaleOnlyKeys[$userType] ?? [],
            )));
        }

        return $map;
    }

    /** @return array<string, array<int, string>> */
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

    /** @return array<string, array{token: string, suffix: ?string}> */
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

    private function writeToFile(string $filePath, string $fileContent): void
    {
        if (! file_exists($filePath) && file_put_contents($filePath, '') === false) {
            throw new Exception("Failed to create file: $filePath");
        }

        if (! is_writable($filePath)) {
            throw new Exception("File exists but is not writable: $filePath");
        }

        if (file_put_contents($filePath, $fileContent, LOCK_EX) === false) {
            throw new Exception("Failed to write to file: $filePath");
        }
    }
}
