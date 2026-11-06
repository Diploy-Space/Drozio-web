<?php

namespace App\Traits;

use App\Models\NotificationMessage;
use App\Models\Order;
use App\Models\ReferralCustomer;
use App\Models\User;
use App\Services\New\PushNotificationService as CurrentPushNotificationService;
use App\Services\PushNotificationService;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

trait PushNotificationTrait
{
    use CommonTrait;

    protected function sendOrderNotification(string $key, string $type, object|array $order, object|array $data = []): void
    {
        try {
            $lang = getDefaultLanguage();
            $businessModule = $order['business_module'] ?? PushNotificationService::MODULE_RETAIL;
            if ($type == 'customer') {
                $fcmToken = $order->customer?->cm_firebase_token;
                $lang = $order->customer?->app_language ?? $lang;
                $value = $this->pushNotificationMessage(key: $key, userType: 'customer', lang: $lang, businessModule: $businessModule);
                if ($fcmToken && $value) {
                    $value = $this->textVariableDataFormat(value: $value, key: $key, userName: "{$order->customer?->f_name} {$order->customer?->l_name}", shopName: $order->seller?->shop?->name, deliveryManName: "{$order->deliveryMan?->f_name} {$order->deliveryMan?->l_name}", time: now()->diffForHumans(), orderId: $order->id);
                    $postData = [
                        'title' => translate('Order'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'order_details_id' => $data['order_details_id'] ?? '',
                        'image' => '',
                        'type' => 'order',
                        'message_key' => $key,
                        ...$this->businessModulePayload(businessModule: $businessModule),
                    ];
                    $this->sendPushNotificationToDevice($fcmToken, $postData);
                }
            }

            if ($type == 'promoter') {
                $getCustomer = ReferralCustomer::where('user_id', $order->customer->id)->first();
                $getPromoter = User::where('id', $getCustomer->refer_by)->first();
                $fcmToken = $getPromoter?->cm_firebase_token;
                $lang = $getPromoter?->app_language ?? $lang;
                $value = $this->pushNotificationMessage(key: $key, userType: 'customer', lang: $lang, businessModule: $businessModule);
                if ($fcmToken && $value) {
                    $value = $this->textVariableDataFormat(value: $value, key: $key, userName: "{$getPromoter?->f_name} {$getPromoter?->l_name}", shopName: $order->seller?->shop?->name, deliveryManName: "{$order->deliveryMan?->f_name} {$order->deliveryMan?->l_name}", time: now()->diffForHumans(), orderId: $order->id);
                    $postData = [
                        'title' => translate('Referral Reward Updates'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'order_details_id' => $data['order_details_id'] ?? '',
                        'image' => '',
                        'type' => 'referral_code_used',
                        'message_key' => $key,
                        ...$this->businessModulePayload(businessModule: $businessModule),
                        ...$this->commonBusinessModulePayload(key: $key, userType: 'customer'),
                    ];
                    $this->sendPushNotificationToDevice($fcmToken, $postData);
                }
            }

            if ($type == 'seller') {
                $sellerFcmToken = $order->seller?->cm_firebase_token;
                if ($sellerFcmToken) {
                    $lang = $order->seller?->app_language ?? $lang;
                    $value_seller = $this->pushNotificationMessage(key: $key, userType: 'seller', lang: $lang, businessModule: $businessModule);

                    if ($value_seller) {
                        $value_seller = $this->textVariableDataFormat(value: $value_seller, key: $key, userName: "{$order->customer?->f_name} {$order->customer?->l_name}", shopName: $order->seller?->shop?->name, deliveryManName: "{$order->deliveryMan?->f_name} {$order->deliveryMan?->l_name}", time: now()->diffForHumans(), orderId: $order->id);
                        $postData = [
                            'title' => translate('Order'),
                            'description' => $value_seller,
                            'order_id' => $order['id'],
                            'order_details_id' => $data['order_details_id'] ?? '',
                            'image' => '',
                            'type' => 'order',
                            'message_key' => $key,
                            'business_module' => $businessModule,
                        ];
                        if (isset($data['refund'])) {
                            $postData['type'] = 'refund';
                            $postData['refund_id'] = $data['refund']['id'];
                        }

                        $this->sendPushNotificationToDevice($sellerFcmToken, $postData);
                    }
                }
            }

            if ($type == 'delivery_man') {
                $fcmTokenDeliveryMan = $order->deliveryMan?->fcm_token;
                $lang = $order->deliveryMan?->app_language ?? $lang;
                $value_delivery_man = $this->pushNotificationMessage(key: $key, userType: 'delivery_man', lang: $lang, businessModule: $businessModule);

                if ($value_delivery_man) {
                    $value_delivery_man = $this->textVariableDataFormat(value: $value_delivery_man, key: $key, userName: "{$order->customer?->f_name} {$order->customer?->l_name}", shopName: $order->seller?->shop?->name, deliveryManName: "{$order->deliveryMan?->f_name} {$order->deliveryMan?->l_name}", time: now()->diffForHumans(), orderId: $order->id);
                    $postData = [
                        'title' => translate('Order'),
                        'description' => $value_delivery_man,
                        'order_id' => $order['id'],
                        'deliveryman_charge' => usdToDefaultCurrency(amount: $order['deliveryman_charge']) ?? 0,
                        'expected_delivery_date' => $order['expected_delivery_date'] ?? '',
                        'image' => '',
                        'type' => 'order',
                        'message_key' => $key,
                        'business_module' => $businessModule,
                        'order_status' => $order['order_status'],
                    ];
                    if ($order->delivery_man_id) {
                        self::add_deliveryman_push_notification($postData, $order->delivery_man_id);
                    }
                    if ($fcmTokenDeliveryMan) {
                        $this->sendPushNotificationToDevice($fcmTokenDeliveryMan, $postData);
                    }
                }
            }
        } catch (Exception $e) {
        }
    }

    protected function chattingNotification(string $key, string $type, object $userData, object $messageForm): void
    {
        try {
            $fcm_token = $type == 'delivery_man' ? $userData?->fcm_token : $userData?->cm_firebase_token;
            if ($fcm_token) {
                $lang = $userData?->app_language ?? getDefaultLanguage();
                $value = $this->pushNotificationMessage($key, $type, $lang);
                if ($value) {
                    $value = $this->textVariableDataFormat(
                        value: $value,
                        key: $key,
                        userName: "{$messageForm?->f_name} ",
                        shopName: "{$messageForm?->shop?->name}",
                        deliveryManName: "{$messageForm?->f_name}",
                        time: now()->diffForHumans()
                    );
                    if ($key == 'message_from_admin') {
                        $messageFromType = 'admin';
                    } elseif ($key == 'message_from_customer') {
                        $messageFromType = 'customer';
                    } elseif ($key == 'message_from_seller') {
                        $messageFromType = 'seller';
                    } elseif ($key == 'message_from_delivery_man') {
                        $messageFromType = 'delivery_man';
                    } else {
                        $messageFromType = '';
                    }
                    $data = [
                        'title' => translate('Message'),
                        'description' => $value,
                        'order_id' => '',
                        'image' => '',
                        'type' => 'chatting',
                        'message_key' => $key,
                        'notification_key' => $key,
                        'notification_from' => $messageFromType,
                        ...$this->commonBusinessModulePayload(key: $key, userType: $type),
                    ];
                    $this->sendChattingPushNotificationToDevice($fcm_token, $data);
                }
            }
        } catch (Exception $exception) {

        }

    }

    protected function withdrawStatusUpdateNotification(string $key, string $type, string $lang, int $status, string $fcmToken): void
    {
        $value = $this->pushNotificationMessage($key, $type, $lang);
        if ($value) {
            $data = [
                'title' => ($status == 1 ? translate('Withdraw request approved') : translate('Withdraw request denied')),
                'description' => $value,
                'image' => '',
                'type' => 'wallet_withdraw',
                'message_key' => $key,
                ...$this->commonBusinessModulePayload(key: $key, userType: $type),
            ];
            $this->sendPushNotificationToDevice($fcmToken, $data);
        }
    }

    protected function customerStatusUpdateNotification(string $key, string $type, string $lang, string $status, string $fcmToken): void
    {
        $value = $this->pushNotificationMessage($key, $type, $lang);
        if ($value) {
            $data = [
                'title' => translate('Your account has been :status', ['status' => $status]),
                'description' => $value,
                'image' => '',
                'type' => 'block',
                'message_key' => $key,
            ];
            $this->sendPushNotificationToDevice($fcmToken, $data);
        }
    }

    protected function productRequestStatusUpdateNotification(
        string $key,
        string $type,
        string $lang,
        string $fcmToken,
        string $businessModule = PushNotificationService::MODULE_RETAIL,
        ?int $productId = null,
    ): void {
        $value = $this->pushNotificationMessage(key: $key, userType: $type, lang: $lang, businessModule: $businessModule);
        if ($value) {
            $data = [
                'title' => translate($key),
                'description' => $value,
                'image' => '',
                'type' => 'product_request_approved_message',
                'message_key' => $key,
                'product_id' => $productId ?? '',
                'business_module' => $businessModule,
            ];
            $this->sendPushNotificationToDevice($fcmToken, $data);
        }
    }

    protected function cashCollectNotification(string $key, string $type, string $lang, float $amount, string $fcmToken): void
    {
        $value = $this->pushNotificationMessage($key, $type, $lang);
        if ($value) {
            $formattedAmount = setCurrencySymbol(amount: usdToDefaultCurrency(amount: $amount), currencyCode: getCurrencyCode());
            $data = [
                'title' => $formattedAmount.' '.translate('Cash deposit'),
                'description' => $value,
                'image' => '',
                'type' => 'wallet',
                'message_key' => $key,
                ...$this->commonBusinessModulePayload(key: $key, userType: $type),
            ];
            $this->sendPushNotificationToDevice($fcmToken, $data);
        }
    }

    /** @return array{business_module?: string} */
    protected function businessModulePayload(string $businessModule): array
    {
        return $businessModule === PushNotificationService::MODULE_WHOLESALE ? ['business_module' => $businessModule] : [];
    }

    /** @return array{business_module?: string} */
    protected function commonBusinessModulePayload(string $key, string $userType): array
    {
        return $this->isCommonNotificationKey(key: $key, userType: $userType)
            ? ['business_module' => CurrentPushNotificationService::MODULE_COMMON]
            : [];
    }

    protected function isCommonNotificationKey(string $key, string $userType): bool
    {
        return in_array($key, CurrentPushNotificationService::COMMON_KEYS[$userType] ?? [], true);
    }

    protected function textVariableDataFormat($value, $key = null, $userName = null, $shopName = null, $deliveryManName = null, $time = null, $orderId = null)
    {
        $data = $value;
        if ($data) {
            $order = $orderId ? Order::find($orderId) : null;
            $data = $userName ? str_replace('{userName}', $userName, $data) : $data;
            $data = $shopName ? str_replace('{shopName}', $shopName, $data) : $data;
            $data = $deliveryManName ? str_replace('{deliveryManName}', $deliveryManName, $data) : $data;
            $data = $key == 'expected_delivery_date' ? ($order ? str_replace('{time}', $order->expected_delivery_date, $data) : $data) : ($time ? str_replace('{time}', $time, $data) : $data);
            $data = $orderId ? str_replace('{orderId}', $orderId, $data) : $data;
        }

        return $data;
    }

    protected function pushNotificationMessage(string $key, string $userType, string $lang, string $businessModule = PushNotificationService::MODULE_RETAIL)
    {
        try {
            $notificationKey = [
                'pending' => 'order_pending_message',
                'confirmed' => 'order_confirmation_message',
                'processing' => 'order_processing_message',
                'out_for_delivery' => 'out_for_delivery_message',
                'delivered' => 'order_delivered_message',
                'returned' => 'order_returned_message',
                'failed' => 'order_failed_message',
                'canceled' => 'order_canceled',
                'order_refunded_message' => 'order_refunded_message',
                'refund_request_canceled_message' => 'refund_request_canceled_message',
                'new_order_message' => 'new_order_message',
                'order_edit_message' => 'order_edit_message',
                'order_edit_return_amount_message' => 'order_edit_return_amount_message',
                'new_order_assigned_message' => 'new_order_assigned_message',
                'delivery_man_assign_by_admin_message' => 'delivery_man_assign_by_admin_message',
                'order_rescheduled_message' => 'order_rescheduled_message',
                'expected_delivery_date' => 'expected_delivery_date',
                'message_from_admin' => 'message_from_admin',
                'message_from_seller' => 'message_from_seller',
                'message_from_delivery_man' => 'message_from_delivery_man',
                'message_from_customer' => 'message_from_customer',
                'refund_request_status_changed_by_admin' => 'refund_request_status_changed_by_admin',
                'withdraw_request_status_message' => 'withdraw_request_status_message',
                'cash_collect_by_seller_message' => 'cash_collect_by_seller_message',
                'cash_collect_by_admin_message' => 'cash_collect_by_admin_message',
                'fund_added_by_admin_message' => 'fund_added_by_admin_message',
                'delivery_man_charge' => 'delivery_man_charge',
                'product_request_approved_message' => 'product_request_approved_message',
                'product_request_rejected_message' => 'product_request_rejected_message',
                'customer_block_message' => 'customer_block_message',
                'customer_unblock_message' => 'customer_unblock_message',
                'your_referred_customer_has_been_place_order' => 'your_referred_customer_has_been_place_order',
                'your_referred_customer_order_has_been_delivered' => 'your_referred_customer_order_has_been_delivered',
            ];
            $notificationKeyValue = $notificationKey[$key] ?? $key;
            if ($this->isCommonNotificationKey(key: $notificationKeyValue, userType: $userType)) {
                $businessModule = CurrentPushNotificationService::MODULE_COMMON;
            }
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where(['business_module' => $businessModule, 'key' => $notificationKeyValue, 'user_type' => $userType])->first();

            if ($data === null && $businessModule !== PushNotificationService::MODULE_RETAIL) {
                $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                    $query->where('locale', $lang);
                }])->where(['business_module' => PushNotificationService::MODULE_RETAIL, 'key' => $notificationKeyValue, 'user_type' => $userType])->first();
            }

            $data = $data ?? ['status' => 0, 'message' => '', 'translations' => []];
            if ($data) {
                if ($data['status'] == 0) {
                    return false;
                }

                return count($data->translations) > 0 ? $data->translations[0]->value : $data['message'];
            } else {
                return false;
            }
        } catch (Exception $exception) {

        }
    }

    protected function demoResetNotification(): void
    {
        try {
            $data = [
                'title' => translate('Demo reset alert'),
                'description' => translate('Demo data is being reset to default').'.',
                'image' => '',
                'order_id' => '',
                'type' => 'demo_reset',
            ];
            $this->sendPushNotificationToTopic(data: $data, topic: $data['type']);
        } catch (Throwable $th) {
            info('Failed_to_sent_demo_reset_notification');
        }
    }

    protected function sendPushNotificationToMultipleDevices(array $tokens, array $data): void
    {
        foreach (array_unique(array_filter($tokens)) as $token) {
            $this->sendPushNotificationToDevice($token, $data);
        }
    }

    protected function sendPushNotificationToDevice(string $fcmToken, array $data): bool|string
    {
        $postData = [
            'message' => [
                'token' => $fcmToken,
                'data' => [
                    'title' => (string) $data['title'],
                    'body' => (string) $data['description'],
                    'image' => $data['image'],
                    'order_id' => (string) ($data['order_id'] ?? ''),
                    'order_details_id' => (string) ($data['order_details_id'] ?? ''),
                    'refund_id' => (string) ($data['refund_id'] ?? ''),
                    'deliveryman_charge' => (string) ($data['deliveryman_charge'] ?? ''),
                    'expected_delivery_date' => (string) ($data['expected_delivery_date'] ?? ''),
                    'type' => (string) $data['type'],
                    'is_read' => '0',
                    'message_key' => (string) ($data['message_key'] ?? ''),
                    'notification_key' => (string) ($data['notification_key'] ?? ''),
                    'notification_from' => (string) ($data['notification_from'] ?? ''),
                    'auction_id' => (string) ($data['auction_id'] ?? ''),
                    'auction_slug' => (string) ($data['auction_slug'] ?? ''),
                    'auction_product_name' => (string) ($data['auction_product_name'] ?? ''),
                    'auction_product_thumbnail_full_url' => (string) ($data['auction_product_thumbnail_full_url'] ?? ''),
                    'excluded_user_id' => (string) ($data['excluded_user_id'] ?? ''),
                    'recipient_user_id' => (string) ($data['recipient_user_id'] ?? ''),
                    'recipient_type' => (string) ($data['recipient_type'] ?? ''),
                    'business_module' => (string) ($data['business_module'] ?? ''),
                    'order_status' => (string) ($data['order_status'] ?? ''),
                    'product_id' => (string) ($data['product_id'] ?? ''),
                    'campaign_id' => (string) ($data['campaign_id'] ?? ''),
                    'review_id' => (string) ($data['review_id'] ?? ''),
                ],
                'notification' => [
                    'title' => (string) $data['title'],
                    'body' => (string) $data['description'],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ];

        return $this->sendNotificationToHttp($postData);
    }

    protected function sendChattingPushNotificationToDevice(string $fcmToken, array $data): bool|string
    {
        $postData = [
            'message' => [
                'token' => $fcmToken,
                'data' => [
                    'title' => (string) $data['title'],
                    'body' => (string) $data['description'],
                    'image' => $data['image'],
                    'order_id' => (string) ($data['order_id'] ?? ''),
                    'refund_id' => (string) ($data['refund_id'] ?? ''),
                    'deliveryman_charge' => (string) ($data['deliveryman_charge'] ?? ''),
                    'expected_delivery_date' => (string) ($data['expected_delivery_date'] ?? ''),
                    'is_read' => '0',
                    'type' => (string) $data['type'],
                    'message_key' => (string) ($data['message_key'] ?? ''),
                    'notification_key' => (string) ($data['notification_key'] ?? ''),
                    'notification_from' => (string) ($data['notification_from'] ?? ''),
                    'business_module' => (string) ($data['business_module'] ?? ''),
                ],
                'notification' => [
                    'title' => (string) $data['title'],
                    'body' => (string) $data['description'],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ];

        return $this->sendNotificationToHttp($postData);
    }

    protected function sendPushNotificationToTopic(array|object $data, string $topic = 'sixvalley'): bool|string
    {
        $postData = [
            'message' => [
                'topic' => $topic,
                'data' => [
                    'title' => (string) ($data['title'] ?? ''),
                    'body' => (string) ($data['description'] ?? ''),
                    'image' => $data['image'] ?? '',
                    'order_id' => (string) ($data['order_id'] ?? ''),
                    'type' => (string) ($data['type'] ?? ''),
                    'message_key' => (string) ($data['message_key'] ?? ''),
                    'excluded_user_id' => (string) ($data['excluded_user_id'] ?? ''),
                    'auction_id' => (string) ($data['auction_id'] ?? ''),
                    'auction_slug' => (string) ($data['auction_slug'] ?? ''),
                    'auction_product_name' => (string) ($data['auction_product_name'] ?? ''),
                    'auction_product_thumbnail_full_url' => (string) ($data['auction_product_thumbnail_full_url'] ?? ''),
                    'business_module' => (string) ($data['business_module'] ?? ''),
                    'is_read' => '0',
                ],
                'notification' => [
                    'title' => (string) ($data['title'] ?? ''),
                    'body' => (string) ($data['description'] ?? ''),
                ],
                'android' => [
                    'priority' => 'high',
                ],
                'webpush' => [
                    'headers' => [
                        'Urgency' => 'high',
                    ],
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ];

        return $this->sendNotificationToHttp($postData);
    }

    protected function hasPushCredentials(): bool
    {
        $key = (array) getWebConfig('push_notification_key');

        return ! empty($key['project_id']) && ! empty($key['client_email']) && ! empty($key['private_key']);
    }

    protected function isPushDelivered(mixed $response): bool
    {
        $body = $response instanceof Response ? $response->body() : $response;
        $decoded = is_string($body) ? json_decode($body, true) : null;

        if (is_array($decoded) && isset($decoded['name']) && ! isset($decoded['error'])) {
            return true;
        }

        Log::warning('FCM push was not delivered', ['response' => $body]);

        return false;
    }

    protected function sendNotificationToHttp(?array $data): bool|string|null
    {
        try {
            if (! $this->hasPushCredentials()) {
                return false;
            }

            $key = (array) getWebConfig('push_notification_key');

            return Http::withHeaders([
                'Authorization' => 'Bearer '.$this->getAccessToken($key),
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/v1/projects/'.$key['project_id'].'/messages:send', $data);
        } catch (Exception $exception) {
            return false;
        }
    }

    protected function getAccessToken($key): ?string
    {
        $cacheKey = 'fcm_access_token_'.md5((string) ($key['client_email'] ?? ''));

        return Cache::remember($cacheKey, 3300, function () use ($key) {
            $jwtToken = [
                'iss' => $key['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => time() + 3600,
                'iat' => time(),
            ];
            $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $jwtPayload = base64_encode(json_encode($jwtToken));
            $unsignedJwt = $jwtHeader.'.'.$jwtPayload;
            openssl_sign($unsignedJwt, $signature, $key['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = $unsignedJwt.'.'.base64_encode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $response->json('access_token') ?? null;
        });
    }
}
