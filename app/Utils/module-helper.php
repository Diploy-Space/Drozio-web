<?php

use App\Enums\BusinessModule;
use App\Enums\DeliveryPartnerSetting;
use App\Events\AddFundToWalletEvent;
use App\Models\AdminWallet;
use App\Models\Order;
use App\Models\OrderEditHistory;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Services\Courier\HostCourierOwnerResolver;
use App\Utils\Convert;
use App\Utils\CustomerManager;
use App\Utils\OrderManager;
use Illuminate\Support\Facades\DB;
use Modules\Auction\app\Enums\CommissionPaymentStatus;
use Modules\Auction\app\Enums\PaymentStatus;
use Modules\Auction\app\Enums\TransactionType;
use Modules\Auction\app\Events\AuctionCommissionPaymentVerifiedEvent;
use Modules\Auction\app\Events\AuctionItemClaimedEvent;
use Modules\Auction\app\Events\AuctionNewParticipationEvent;
use Modules\Auction\app\Models\AuctionBid;
use Modules\Auction\app\Models\AuctionCommissionPayment;
use Modules\Auction\app\Models\AuctionParticipant;
use Modules\Auction\app\Models\AuctionProduct;
use Modules\Auction\app\Models\AuctionTransaction;
use Modules\Auction\app\Utils\AuctionCheckoutManager;
use Modules\Courier\app\Services\New\CourierService;
use Modules\TaxModule\app\Models\SystemTaxSetup;
use Modules\TaxModule\app\Models\Tax;

if (! function_exists('digital_payment_success')) {
    function digital_payment_success($paymentData): void
    {
        if (isset($paymentData) && $paymentData['is_paid'] == 1) {
            $additionalData = json_decode($paymentData['additional_data'], true);

            $addCustomer = null;
            $newCustomerInfo = $additionalData['new_customer_info'] ?? null;

            if ($newCustomerInfo) {
                $checkCustomer = User::where(['email' => $newCustomerInfo['email']])->orWhere(['phone' => $newCustomerInfo['phone']])->first();
                if (! $checkCustomer) {
                    $addCustomer = User::create([
                        'name' => $newCustomerInfo['name'],
                        'f_name' => $newCustomerInfo['name'],
                        'l_name' => $newCustomerInfo['l_name'],
                        'email' => $newCustomerInfo['email'],
                        'phone' => $newCustomerInfo['phone'],
                        'is_active' => 1,
                        'password' => bcrypt($newCustomerInfo['password']),
                        'referral_code' => $newCustomerInfo['referral_code'],
                    ]);
                } else {
                    $addCustomer = $checkCustomer;
                }
                session()->put('newRegisterCustomerInfo', $addCustomer);

                if ($additionalData['is_guest']) {
                    $addressId = $additionalData['address_id'] ?? null;
                    $billingAddressId = $additionalData['billing_address_id'] ?? null;
                    ShippingAddress::where(['customer_id' => $additionalData['customer_id'], 'is_guest' => 1, 'id' => $addressId])
                        ->update(['customer_id' => $addCustomer['id'], 'is_guest' => 0]);
                    ShippingAddress::where(['customer_id' => $additionalData['customer_id'], 'is_guest' => 1, 'id' => $billingAddressId])
                        ->update(['customer_id' => $addCustomer['id'], 'is_guest' => 0]);
                }
            }

            session()->put('payment_mode', $additionalData['payment_mode'] ?? 'web');

            if (isset($additionalData['is_guest']) && $additionalData['is_guest'] == 0) {
                $user = User::where(['id' => $additionalData['customer_id']])->first();
                request()->merge(['user' => $user]);
            }

            $requestObj = [
                'customer_id' => $additionalData['customer_id'],
                'is_guest' => $additionalData['is_guest'] ?? 0,
                'guest_id' => ($additionalData['is_guest_in_order'] ?? 0) ? $additionalData['customer_id'] : null,
                'payment_request_from' => $additionalData['payment_mode'] ?? 'web',
            ];
            request()->merge($requestObj);

            $orderIds = OrderManager::generateOrder(data: [
                'is_guest' => $additionalData['is_guest_in_order'] ?? 0,
                'guest_id' => ($additionalData['is_guest_in_order'] ?? 0) ? $additionalData['customer_id'] : null,
                'customer_id' => $additionalData['customer_id'],
                'order_status' => 'confirmed',
                'payment_method' => $paymentData['payment_method'],
                'payment_status' => 'paid',
                'transaction_ref' => $paymentData['transaction_id'],
                'new_customer_id' => $addCustomer ? $addCustomer['id'] : ($additionalData['new_customer_id'] ?? null),
                'newCustomerRegister' => $addCustomer,

                'order_note' => $additionalData['order_note'],
                'coupon_code' => $additionalData['coupon_code'] ?? null,
                'address_id' => $additionalData['address_id'] ?? null,
                'billing_address_id' => $additionalData['billing_address_id'] ?? null,
                'requestObj' => $requestObj,
            ]);

            foreach ($orderIds as $orderId) {
                OrderManager::generateReferBonusForFirstOrder(orderId: $orderId);
            }
        }
    }
}

if (! function_exists('digital_payment_fail')) {
    function digital_payment_fail($payment_data) {}
}
if (! function_exists('customer_order_edit_pay_due_amount_success')) {
    function customer_order_edit_pay_due_amount_success($payment_data): void
    {
        if (! isset($payment_data) || ($payment_data['is_paid'] ?? 0) != 1) {
            return;
        }
        $additionalData = json_decode($payment_data['additional_data'] ?? '{}', true);
        if (empty($additionalData['order_id'])) {
            return;
        }
        $order = Order::where('id', $additionalData['order_id'])->first();
        DB::transaction(function () use ($additionalData, $payment_data, $order) {
            $order->update([
                'edit_due_amount' => 0,
                'order_amount' => $additionalData['order_amount'] ?? 0,
                'payment_status' => 'paid',
            ]);
            OrderEditHistory::where('order_id', $additionalData['order_id'])
                ->latest('id')
                ->limit(1)
                ->update([
                    'order_due_payment_status' => 'paid',
                    'order_due_payment_method' => $payment_data['payment_method'] ?? null,
                    'order_due_transaction_ref' => $payment_data['transaction_id'] ?? '',
                    'order_due_payment_note' => $payment_data['order_due_payment_note'] ?? '',
                ]);
        });
        OrderManager::sendPushNotificationAfterDuePayment(order: $order);
        AdminWallet::where(['admin_id' => 1])->increment('pending_amount', $additionalData['order_amount']);
    }
}
if (! function_exists('customer_order_edit_pay_due_amount_failed')) {
    function customer_order_edit_pay_due_amount_failed($payment_data): void
    {
        if (! isset($payment_data)) {
            return;
        }
        $additionalData = json_decode($payment_data['additional_data'] ?? '', true);
        if (empty($additionalData['order_id'])) {
            return;
        }
        OrderEditHistory::where('order_id', $additionalData['order_id'])
            ->latest('id')
            ->limit(1)
            ->update([
                'order_due_payment_status' => 'unpaid',
                'order_due_transaction_ref' => $payment_data['transaction_id'] ?? '',
            ]);
    }
}

if (! function_exists('add_fund_to_wallet_success')) {
    function add_fund_to_wallet_success($payment_data): void
    {
        if (isset($payment_data) && $payment_data['is_paid'] == 1) {
            $additional_data = json_decode($payment_data['additional_data'], true);
            session()->put('payment_mode', ($additional_data['payment_mode'] ?? 'web'));

            $paymentAmount = Convert::usdPaymentModule(floatval($payment_data['payment_amount']), $payment_data['currency_code']);
            $paymentAmount = usdToDefaultCurrency(amount: $paymentAmount);
            $wallet_transaction = CustomerManager::create_wallet_transaction($payment_data['payer_id'], $paymentAmount, 'add_fund', 'add_funds_to_wallet', $payment_data);

            if ($wallet_transaction) {
                try {
                    $data = [
                        'walletTransaction' => $wallet_transaction,
                        'userName' => $wallet_transaction->user['f_name'],
                        'userType' => 'customer',
                        'templateName' => 'add-fund-to-wallet',
                        'subject' => translate('Add Fund To Wallet'),
                        'title' => translate('Add Fund To Wallet'),
                    ];
                    event(new AddFundToWalletEvent(email: $wallet_transaction->user['email'], data: $data));
                } catch (Exception $ex) {
                    info($ex);
                }
            }
        }
    }
}

if (! function_exists('add_fund_to_wallet_fail')) {
    function add_fund_to_wallet_fail($payment_data) {}
}

if (! function_exists('auction_entry_fee_success')) {
    function auction_entry_fee_success($payment_data): void
    {
        if (! getCheckAddonPublishedStatus(moduleName: 'Auction')) {
            return;
        }
        if (! isset($payment_data) || $payment_data['is_paid'] != 1) {
            return;
        }

        $additionalData = json_decode($payment_data['additional_data'], true);
        $auctionProductId = $additionalData['auction_product_id'] ?? null;
        $userId = $payment_data['payer_id'];

        if (! $auctionProductId || ! $userId) {
            return;
        }

        $alreadyPaid = AuctionParticipant::where([
            'auction_product_id' => $auctionProductId,
            'user_id' => $userId,
            'entry_fee_paid_status' => PaymentStatus::PAID,
        ])->exists();

        if ($alreadyPaid) {
            return;
        }

        $wasNew = false;

        DB::transaction(function () use ($userId, $auctionProductId, $payment_data, $additionalData, &$wasNew) {
            $alreadyExists = AuctionParticipant::where([
                'auction_product_id' => $auctionProductId,
                'user_id' => $userId,
            ])->exists();

            $entryFeeTransaction = AuctionTransaction::updateOrCreate(
                ['auction_product_id' => $auctionProductId, 'user_id' => $userId, 'type' => 'entry_fee'],
                [
                    'amount' => $additionalData['base_amount'] ?? 0,
                    'payment_status' => PaymentStatus::PAID,
                    'payment_method' => $payment_data['payment_method'] ?? null,
                    'transaction_ref' => $payment_data['transaction_id'],
                ]
            );

            AuctionParticipant::updateOrCreate(
                ['auction_product_id' => $auctionProductId, 'user_id' => $userId],
                [
                    'entry_fee_paid_status' => PaymentStatus::PAID,
                    'entry_fee_paid_amount' => $additionalData['base_amount'] ?? $payment_data['payment_amount'],
                    'entry_fee_payment_method' => $payment_data['payment_method'],
                    'entry_fee_payment_status' => PaymentStatus::VERIFIED,
                    'transaction_id' => $entryFeeTransaction->id,
                    'joined_at' => now(),
                ]
            );

            $wasNew = ! $alreadyExists;

            if ($wasNew) {
                AuctionProduct::where('id', $auctionProductId)->increment('total_participants');
            }
        });

        if ($wasNew) {
            $auctionProduct = AuctionProduct::find($auctionProductId);
            if ($auctionProduct) {
                AuctionNewParticipationEvent::dispatch(
                    $auctionProduct,
                    (int) $userId,
                    true,
                );
            }
        }
    }
}

if (! function_exists('auction_entry_fee_fail')) {
    function auction_entry_fee_fail($payment_data): void {}
}

if (! function_exists('auction_claim_payment_success')) {
    function auction_claim_payment_success($payment_data): void
    {
        if (! getCheckAddonPublishedStatus(moduleName: 'Auction')) {
            return;
        }
        if (! isset($payment_data) || $payment_data['is_paid'] != 1) {
            return;
        }

        $additionalData = json_decode($payment_data['additional_data'], true);
        $auctionProductId = $additionalData['auction_product_id'] ?? null;
        $userId = $payment_data['payer_id'];
        $winningBidId = $additionalData['winning_bid_id'] ?? null;

        if (! $auctionProductId || ! $userId || ! $winningBidId) {
            return;
        }

        $alreadyProcessed = AuctionTransaction::where([
            'auction_product_id' => $auctionProductId,
            'user_id' => $userId,
            'type' => TransactionType::AUCTION_PAYMENT,
            'payment_status' => PaymentStatus::PAID,
        ])->exists();

        if ($alreadyProcessed) {
            return;
        }

        $auctionProduct = AuctionProduct::find($auctionProductId);
        if (! $auctionProduct) {
            return;
        }

        $eligibleBid = AuctionBid::find($winningBidId);
        if (! $eligibleBid) {
            return;
        }

        $commission = AuctionCheckoutManager::calculateAuctionAdminCommission(
            product: $auctionProduct,
            eligibleBid: $eligibleBid,
        );

        $shippingAddressId = $additionalData['shipping_address_id'] ?? null;
        $shippingAddressInfo = $additionalData['shipping_address_info'] ?? null;
        $billingAddressId = $additionalData['billing_address_id'] ?? null;
        $billingAddressInfo = $additionalData['billing_address_info'] ?? null;

        DB::transaction(function () use (
            $userId, $auctionProduct, $eligibleBid, $payment_data, $additionalData, $commission,
            $shippingAddressId, $shippingAddressInfo, $billingAddressId, $billingAddressInfo,
        ) {
            $claimTransaction = AuctionTransaction::updateOrCreate([
                'auction_product_id' => $auctionProduct->id,
                'user_id' => $userId,
                'type' => TransactionType::AUCTION_PAYMENT,
            ], [
                'bid_id' => $eligibleBid->id,
                'amount' => $additionalData['base_amount'] ?? 0,
                'payment_method' => $payment_data['payment_method'] ?? null,
                'payment_status' => PaymentStatus::PAID,
                'transaction_ref' => $payment_data['transaction_id'],
            ]);

            $auctionProduct->update([
                'winner_user_id' => $userId,
                'winning_bid_id' => $eligibleBid->id,
                'admin_commission' => $commission,
                'delivery_status' => null,
                'shipping_address_id' => $shippingAddressId,
                'shipping_address_info' => $shippingAddressInfo,
                'billing_address_id' => $billingAddressId,
                'billing_address_info' => $billingAddressInfo,
            ]);

            AuctionCheckoutManager::persistAuctionClaimTaxDetails(
                auctionProduct: $auctionProduct,
                claimTransaction: $claimTransaction,
                generatedData: AuctionCheckoutManager::processAuctionClaimGenerateData(
                    auction: $auctionProduct,
                    bid: $eligibleBid,
                ),
            );
        });

        AuctionItemClaimedEvent::dispatch($auctionProduct);
        cacheRemoveByType(type: 'auction_products');

    }
}

if (! function_exists('auction_claim_payment_fail')) {
    function auction_claim_payment_fail($payment_data): void {}
}

if (! function_exists('auction_cod_commission_success')) {
    function auction_cod_commission_success($payment_data): void
    {
        if (! getCheckAddonPublishedStatus(moduleName: 'Auction')) {
            return;
        }
        if (! isset($payment_data) || $payment_data['is_paid'] != 1) {
            return;
        }

        $additionalData = json_decode($payment_data['additional_data'] ?? '', true);
        $auctionProductId = $additionalData['auction_product_id'] ?? null;
        $userId = $payment_data['payer_id'];

        if (! $auctionProductId || ! $userId) {
            return;
        }

        $auctionProduct = AuctionProduct::find($auctionProductId);
        if (! $auctionProduct) {
            return;
        }

        $isSettled = DB::transaction(function () use ($userId, $auctionProductId, $auctionProduct, $payment_data): bool {
            $isAlreadySettled = (bool) AuctionProduct::whereKey($auctionProductId)
                ->lockForUpdate()
                ->value('admin_commission_given');

            if ($isAlreadySettled) {
                return false;
            }

            OrderManager::getCheckOrCreateAdminWallet();
            $adminWallet = AdminWallet::where('admin_id', 1)->first();
            $adminWallet->commission_earned += (float) $auctionProduct['admin_commission'];
            $adminWallet->save();

            AuctionTransaction::updateOrCreate(
                [
                    'auction_product_id' => $auctionProductId,
                    'user_id' => $userId,
                    'type' => TransactionType::COMMISSION_PAYMENT,
                ],
                [
                    'amount' => $auctionProduct['admin_commission'],
                    'payment_method' => $payment_data['payment_method'] ?? null,
                    'payment_status' => PaymentStatus::PAID,
                    'transaction_ref' => $payment_data['transaction_id'] ?? null,
                ]
            );

            AuctionCommissionPayment::updateOrCreate(
                [
                    'auction_product_id' => $auctionProductId,
                    'owner_type' => $auctionProduct->owner_type,
                    'owner_id' => $auctionProduct->owner_id,
                ],
                [
                    'amount' => $auctionProduct['admin_commission'],
                    'payment_method' => $payment_data['payment_method'] ?? null,
                    'payment_status' => CommissionPaymentStatus::PAID,
                    'transaction_ref' => $payment_data['transaction_id'] ?? null,
                ]
            );

            AuctionProduct::where('id', $auctionProductId)
                ->update(['admin_commission_given' => true]);

            return true;
        });

        if ($isSettled) {
            AuctionCommissionPaymentVerifiedEvent::dispatch($auctionProduct);
        }
    }
}

if (! function_exists('auction_cod_commission_fail')) {
    function auction_cod_commission_fail($payment_data): void
    {
        if (! getCheckAddonPublishedStatus(moduleName: 'Auction')) {
            return;
        }
        if (! isset($payment_data)) {
            return;
        }

        $additionalData = json_decode($payment_data['additional_data'] ?? '', true);
        $auctionProductId = $additionalData['auction_product_id'] ?? null;
        $userId = $payment_data['payer_id'] ?? null;

        if (! $auctionProductId || ! $userId) {
            return;
        }

        AuctionTransaction::updateOrCreate(
            [
                'auction_product_id' => $auctionProductId,
                'user_id' => $userId,
                'type' => TransactionType::COMMISSION_PAYMENT,
            ],
            [
                'amount' => $additionalData['base_amount'] ?? 0,
                'payment_method' => $payment_data['payment_method'] ?? null,
                'payment_status' => PaymentStatus::DENIED,
                'transaction_ref' => $payment_data['transaction_id'] ?? null,
            ]
        );
    }
}

if (! function_exists('auction_commission_payment_success')) {
    function auction_commission_payment_success($payment_data): void
    {
        auction_cod_commission_success($payment_data);
    }
}

if (! function_exists('auction_commission_payment_fail')) {
    function auction_commission_payment_fail($payment_data): void
    {
        auction_cod_commission_fail($payment_data);
    }
}

if (! function_exists('config_settings')) {
    function config_settings($key, $settings_type)
    {
        try {
            $config = DB::table('addon_settings')->where('key_name', $key)
                ->where('settings_type', $settings_type)->first();
        } catch (Exception $exception) {
            return null;
        }

        return (isset($config)) ? $config : null;
    }
}

if (! function_exists('getCheckAddonPublishedStatus')) {
    function getCheckAddonPublishedStatus(string $moduleName): int
    {
        try {
            if (in_array($moduleName, ['AI', 'Blog', 'Courier', 'TaxModule'])) {
                return 1;
            }
            $statusFile = base_path('modules_statuses.json');
            if (file_exists($statusFile)) {
                $statuses = json_decode((string) file_get_contents($statusFile), true);
                if (is_array($statuses) && ($statuses[$moduleName] ?? false) !== true) {
                    return 0;
                }
            }

            if (file_exists(base_path("Modules/{$moduleName}/Addon/info.php"))) {
                $full_data = include base_path("Modules/{$moduleName}/Addon/info.php");

                $installedVersion = $full_data['version'] ?? null;

                if (! isAddonVersionSupported(moduleName: $moduleName, version: $installedVersion)
                    || isAddonUpdatePending(moduleName: $moduleName, version: $installedVersion)) {
                    return 0;
                }

                return $full_data['is_published'] == 1 ? 1 : 0;
            }
        } catch (Exception $exception) {
        }

        return 0;
    }
}

if (! function_exists('getAddonSupportedVersionMap')) {
    function getAddonSupportedVersionMap(): array
    {
        return defined('ADDON_SUPPORTED_VERSIONS') && is_array(ADDON_SUPPORTED_VERSIONS) ? ADDON_SUPPORTED_VERSIONS : [];
    }
}

if (! function_exists('getAddonInstallationRecordFile')) {
    function getAddonInstallationRecordFile(): string
    {
        return storage_path(defined('ADDON_INSTALLATION_RECORD_FILE') ? ADDON_INSTALLATION_RECORD_FILE : 'app/addon-installations.json');
    }
}

if (! function_exists('isAddonVersionSupported')) {
    function isAddonVersionSupported(string $moduleName, mixed $version): bool
    {
        $supportedVersions = getAddonSupportedVersionMap()[$moduleName] ?? null;

        if ($supportedVersions === null) {
            return true;
        }

        if (! is_scalar($version)) {
            return false;
        }

        $installedVersion = (string) $version;

        foreach ($supportedVersions as $supportedVersion) {
            $isSameVersion = version_compare($installedVersion, $supportedVersion, '==');
            $isPatchOfSupportedVersion = str_starts_with($installedVersion, $supportedVersion.'.');

            if ($isSameVersion || $isPatchOfSupportedVersion) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('getAddonSupportedVersions')) {
    function getAddonSupportedVersions(string $moduleName): ?string
    {
        $supportedVersions = getAddonSupportedVersionMap()[$moduleName] ?? null;

        return $supportedVersions === null ? null : implode(', ', $supportedVersions);
    }
}

if (! function_exists('getAddonInstallationRecords')) {
    function getAddonInstallationRecords(bool $refresh = false): array
    {
        static $records = null;

        if ($records === null || $refresh) {
            $recordFile = getAddonInstallationRecordFile();
            $decodedRecords = is_file($recordFile) ? json_decode((string) file_get_contents($recordFile), true) : null;
            $records = is_array($decodedRecords) ? $decodedRecords : [];
        }

        return $records;
    }
}

if (! function_exists('recordAddonInstallation')) {
    function recordAddonInstallation(string $moduleName, array $info): bool
    {
        $records = getAddonInstallationRecords(refresh: true);

        $records[$moduleName] = [
            'version' => isset($info['version']) ? (string) $info['version'] : null,
            'is_published' => (int) ($info['is_published'] ?? 0),
            'purchase_code' => $info['purchase_code'] ?? null,
            'username' => $info['username'] ?? null,
            'recorded_at' => now()->toDateTimeString(),
        ];

        $isWritten = @file_put_contents(
            getAddonInstallationRecordFile(),
            json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX,
        ) !== false;

        getAddonInstallationRecords(refresh: true);

        return $isWritten;
    }
}

if (! function_exists('isAddonUpdatePending')) {
    function isAddonUpdatePending(string $moduleName, mixed $version): bool
    {
        if (! array_key_exists($moduleName, getAddonSupportedVersionMap())) {
            return false;
        }

        $recordedVersion = getAddonInstallationRecords()[$moduleName]['version'] ?? null;

        return ! is_scalar($version) || $recordedVersion !== (string) $version;
    }
}

if (! function_exists('addon_published_status')) {
    function addon_published_status(string $moduleName): bool
    {
        return getCheckAddonPublishedStatus(moduleName: $moduleName) === 1;
    }
}

if (! function_exists('moduleService')) {
    function moduleService(string $moduleName, string $serviceClass): ?object
    {
        return addon_published_status(moduleName: $moduleName) && class_exists($serviceClass)
            ? app($serviceClass)
            : null;
    }
}

if (! function_exists('getTaxModuleSystemTypesConfig')) {
    function getTaxModuleSystemTypesConfig($getTaxVatList = true, $tax_payer = 'vendor', $businessModule = BusinessModule::RETAIL): array
    {
        $cacheKey = "tax_system_type_{$businessModule}_{$tax_payer}_".($getTaxVatList ? 'with_vat' : 'no_vat');

        $cacheKeys = Cache::get('cache_tax_system_types_and_config', []);
        if (! in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;
            Cache::put('cache_tax_system_types_and_config', $cacheKeys, 60 * 60 * 24 * 7);
        }

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($getTaxVatList, $tax_payer, $businessModule) {
            if (getCheckAddonPublishedStatus('TaxModule')) {
                $systemTaxVat = SystemTaxSetup::where('is_active', 1)
                    ->with(['additionalData' => function ($query) {
                        return $query->where('is_active', 1);
                    }])
                    ->where('tax_payer', $tax_payer)
                    ->where('business_module', $businessModule)
                    ->where('is_default', 1)
                    ->first();

                if (! $systemTaxVat) {
                    $systemTaxVat = SystemTaxSetup::create([
                        'tax_type' => 'order_wise',
                        'country_code' => null,
                        'tax_payer' => 'vendor',
                        'business_module' => $businessModule,
                        'is_default' => true,
                        'is_active' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    return [
                        'SystemTaxVat' => $systemTaxVat ?? null,
                        'SystemTaxVatType' => $systemTaxVat?->tax_type ?? 'order_wise',
                        'is_included' => $systemTaxVat?->is_included ?? 0,
                        'productWiseTax' => false,
                        'categoryWiseTax' => false,
                        'taxVats' => [],
                    ];
                }

                if ($getTaxVatList) {
                    $taxVats = Tax::where('is_active', 1)
                        ->where('is_default', 1)
                        ->where('business_module', $businessModule)
                        ->get();
                }

                if ($systemTaxVat?->tax_type == 'product_wise') {
                    $productWiseTax = true;
                } elseif ($systemTaxVat?->tax_type == 'category_wise') {
                    $categoryWiseTax = true;
                }
            }

            return [
                'SystemTaxVat' => $systemTaxVat ?? null,
                'SystemTaxVatType' => $systemTaxVat?->tax_type ?? 'order_wise',
                'is_included' => $systemTaxVat?->is_included ?? 0,
                'productWiseTax' => $productWiseTax ?? false,
                'categoryWiseTax' => $categoryWiseTax ?? false,
                'taxVats' => $taxVats ?? [],
            ];
        });
    }
}

if (! function_exists('courierService')) {
    function courierService(): ?object
    {
        return addon_published_status('Courier')
            && class_exists(CourierService::class)
            && app()->bound(CourierService::class)
                ? app(CourierService::class)
                : null;
    }
}

if (! function_exists('deliveryPartnerServiceAvailable')) {
    function deliveryPartnerServiceAvailable(string $businessModule = BusinessModule::RETAIL): bool
    {
        return (bool) getWebConfig(name: DeliveryPartnerSetting::thirdPartyDeliveryKey(businessModule: $businessModule))
            && courierService() !== null;
    }
}

if (! function_exists('deliveryPartnerConfigAvailable')) {
    function deliveryPartnerConfigAvailable(): bool
    {
        return courierService() !== null;
    }
}

if (! function_exists('deliveryPartnerOrderBusinessModule')) {
    function deliveryPartnerOrderBusinessModule(object|array $order): string
    {
        $businessModule = $order['business_module'] ?? BusinessModule::RETAIL;

        return DeliveryPartnerSetting::supports(businessModule: (string) $businessModule)
            ? (string) $businessModule
            : BusinessModule::RETAIL;
    }
}

if (! function_exists('deliveryPartnerRequestBusinessModule')) {
    function deliveryPartnerRequestBusinessModule(): string
    {
        if (courierService() === null) {
            return BusinessModule::RETAIL;
        }

        return app(HostCourierOwnerResolver::class)->resolveBusinessModule();
    }
}

if (! function_exists('deliveryPartnerAvailable')) {
    function deliveryPartnerAvailable(object|array|null $order, string $panel): bool
    {
        if (empty($order)) {
            return false;
        }

        $businessModule = deliveryPartnerOrderBusinessModule(order: $order);

        $isVendorResponsible = ($order['shipping_responsibility'] ?? null) === 'sellerwise_shipping'
            && ($order['seller_is'] ?? null) === 'seller';

        if ($isVendorResponsible && ! vendorDeliveryPartnerSetupAvailable(businessModule: $businessModule)) {
            return false;
        }

        return deliveryPartnerServiceAvailable(businessModule: $businessModule)
            && $panel === ($isVendorResponsible ? 'vendor' : 'admin')
            && (bool) courierService()?->isEnabled();
    }
}

if (! function_exists('vendorDeliveryPartnerConfigAvailable')) {
    function vendorDeliveryPartnerConfigAvailable(string $businessModule = BusinessModule::RETAIL): bool
    {
        return deliveryPartnerConfigAvailable()
            && (bool) (getWebConfig(name: DeliveryPartnerSetting::vendorSetupKey(businessModule: $businessModule)) ?? 1);
    }
}

if (! function_exists('vendorDeliveryPartnerSetupAvailable')) {
    function vendorDeliveryPartnerSetupAvailable(string $businessModule = BusinessModule::RETAIL): bool
    {
        return deliveryPartnerServiceAvailable(businessModule: $businessModule)
            && vendorDeliveryPartnerConfigAvailable(businessModule: $businessModule);
    }
}

if (! function_exists('vendorDeliveryPartnerSetupModule')) {
    function vendorDeliveryPartnerSetupModule(): ?string
    {
        $preferred = function_exists('activeBusinessModule') ? activeBusinessModule()->key : BusinessModule::RETAIL;
        $candidates = array_unique([$preferred, BusinessModule::RETAIL, BusinessModule::WHOLESALE]);

        foreach ($candidates as $businessModule) {
            if ($businessModule !== BusinessModule::RETAIL
                && (! function_exists('businessModuleManager') || ! businessModuleManager()->isAvailableInPanel($businessModule))) {
                continue;
            }

            if (DeliveryPartnerSetting::supports(businessModule: $businessModule)
                && vendorDeliveryPartnerSetupAvailable(businessModule: $businessModule)) {
                return $businessModule;
            }
        }

        return null;
    }
}

if (! function_exists('deliveryPartnerShipment')) {
    function deliveryPartnerShipment(string|int $orderId): ?array
    {
        return courierService()?->summaryFor((string) $orderId);
    }
}

if (! function_exists('deliveryPartnerAssigned')) {
    function deliveryPartnerAssigned(object|array|null $order): bool
    {
        if (empty($order)) {
            return false;
        }

        if (! empty($order['id']) && deliveryPartnerShipment($order['id']) !== null) {
            return true;
        }

        return ($order['delivery_type'] ?? null) === 'third_party_delivery'
            && (filled($order['delivery_service_name'] ?? null) || filled($order['third_party_delivery_tracking_id'] ?? null));
    }
}

if (! function_exists('deliveryPartnerTrackingInfo')) {
    function deliveryPartnerTrackingInfo(?object $order): ?array
    {
        if (empty($order)) {
            return null;
        }

        $instruction = translate('Delivered by a courier. Track it on the courier website or app.').'. '
            .translate('Contact the delivery partner directly with these tracking details.').'.';

        $shipment = deliveryPartnerShipment($order->id);

        if ($shipment !== null) {
            return [
                'delivery_partner' => $shipment['provider_label'],
                'tracking_number' => $shipment['tracking_number'],
                'shipment_status' => $shipment['status'],
                'shipment_status_label' => $shipment['status_label'],
                'tracking_url' => $shipment['tracking_url'],
                'instruction' => $instruction,
            ];
        }

        if (blank($order->delivery_service_name) && blank($order->third_party_delivery_tracking_id)) {
            return null;
        }

        return [
            'delivery_partner' => $order->delivery_service_name,
            'tracking_number' => $order->third_party_delivery_tracking_id,
            'shipment_status' => null,
            'shipment_status_label' => null,
            'tracking_url' => null,
            'instruction' => $instruction,
        ];
    }
}

if (! function_exists('deliveryPartnerShipmentDetails')) {
    function deliveryPartnerShipmentDetails(string|int $orderId): ?array
    {
        return courierService()?->shipmentDetailsFor((string) $orderId);
    }
}

if (! function_exists('getModuleDynamicAsset')) {
    function getModuleDynamicAsset(string $path): string
    {
        if (getModuleAssetsProcessingDirectory() == 'public') {
            $position = strpos($path, 'public/');
            $result = $path;
            if ($position === 0) {
                $result = preg_replace('/public/', '', $path, 1);
            }
        } else {
            $result = $path;
        }

        return asset($result);
    }
}

if (! function_exists('getModuleDynamicStorage')) {
    function getModuleDynamicStorage(string $path): ?string
    {
        if (getModuleAssetsProcessingDirectory() == 'public') {
            $result = str_replace('storage/app/public', 'storage', $path);
        } else {
            $result = $path;
        }

        return is_file($result) ? asset($result) : '';
    }
}

if (! function_exists('getModuleAssetsProcessingDirectory')) {
    function getModuleAssetsProcessingDirectory(): string
    {
        $cacheKey = 'SYSTEM_DOMAIN_POINTED_DIRECTORY_'.md5($_SERVER['SCRIPT_FILENAME']);

        return Cache::rememberForever($cacheKey, function () {
            $scriptPath = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
            $basePath = realpath(base_path());
            $publicPath = realpath(public_path());

            if ($scriptPath === $publicPath) {
                return 'public';
            } elseif ($scriptPath === $basePath) {
                return 'root';
            }

            return 'public';
        });
    }
}
