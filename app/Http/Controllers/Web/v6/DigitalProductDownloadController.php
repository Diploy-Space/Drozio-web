<?php

namespace App\Http\Controllers\Web\v6;

use App\Events\DigitalProductOtpVerificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Order\DigitalDownloadOtpResendRequest;
use App\Http\Requests\Customer\Order\DigitalDownloadOtpVerifyRequest;
use App\Services\New\DigitalProductOtpVerificationService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderService;
use App\Services\New\SettingService;
use App\Utils\Helpers;
use App\Utils\SMSModule;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DigitalProductDownloadController extends Controller
{
    private const OTP_REQUIRED_STATUS = 2;

    private const PRODUCT_TYPE_DIGITAL = 'digital';

    private const DIGITAL_TYPE_READY_PRODUCT = 'ready_product';

    private const DIGITAL_TYPE_READY_AFTER_SELL = 'ready_after_sell';

    private const SMS_CONFIG_TYPE = 'sms_config';

    private const OTP_MAIL_TEMPLATE = 'digital-product-otp';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly SettingService $settingService,
        private readonly DigitalProductOtpVerificationService $digitalProductOtpVerificationService,
    ) {}

    public function download(int|string $id): JsonResponse
    {
        $orderDetail = $this->orderDetailService->find(id: $id, relations: ['order.customer']);
        $order = $orderDetail?->order;

        if ($order === null) {
            return response()->json([
                'status' => 0,
                'message' => translate('Not found').' !',
            ]);
        }

        if ($order->payment_status !== OrderService::PAYMENT_STATUS_PAID) {
            return response()->json([
                'status' => 0,
                'message' => translate('Payment must be confirmed first').' !!',
            ]);
        }

        if ($order->is_guest) {
            $guestContact = $this->getGuestContact(order: $order);

            return $this->sendDownloadOtp(orderDetail: $orderDetail, email: $guestContact['email'] ?? '', phone: $guestContact['phone'] ?? '');
        }

        if (auth('customer')->check() && auth('customer')->id() == $order->customer?->id) {
            $digitalFile = $this->getDigitalFile(orderDetail: $orderDetail);

            if (! is_null($digitalFile['name']) && $digitalFile['exists']) {
                return response()->json([
                    'status' => 1,
                    'file_path' => $digitalFile['path'],
                    'file_name' => $digitalFile['name'],
                ]);
            }

            return response()->json([
                'status' => 0,
                'message' => translate('Not found'),
            ]);
        }

        return $this->sendDownloadOtp(
            orderDetail: $orderDetail,
            email: $order->customer->email ?? '',
            phone: $order->customer->phone ?? '',
        );
    }

    public function verifyOtp(DigitalDownloadOtpVerifyRequest $request): JsonResponse
    {
        $tokenParams = ['token' => $request['otp'], 'order_details_id' => $request['order_details_id']];
        $verification = $this->digitalProductOtpVerificationService->findBy(params: $tokenParams);
        $orderDetail = $this->orderDetailService->find(id: $request['order_details_id'], relations: ['order.customer']);

        if ($verification === null) {
            return response()->json([
                'status' => 0,
                'message' => translate('The OTP is incorrect').' !',
            ]);
        }

        $digitalFile = $orderDetail === null
            ? ['path' => null, 'name' => '', 'exists' => false]
            : $this->getDigitalFile(orderDetail: $orderDetail);

        $this->digitalProductOtpVerificationService->deleteBy(params: $tokenParams);

        if (! is_null($digitalFile['name']) && $digitalFile['exists']) {
            return response()->json([
                'status' => 1,
                'file_path' => $digitalFile['path'],
                'file_name' => $digitalFile['name'],
                'message' => translate('Verified successfully'),
            ]);
        }

        return response()->json([
            'status' => 0,
            'message' => translate('Not found'),
        ]);
    }

    public function resendOtp(DigitalDownloadOtpResendRequest $request): JsonResponse
    {
        $latestToken = $this->digitalProductOtpVerificationService->findBy(params: ['order_details_id' => $request['order_details_id']]);
        $otpIntervalTime = getWebConfig(name: 'otp_resend_time') ?? 1;

        if ($latestToken !== null && Carbon::parse($latestToken->created_at)->diffInSeconds() < $otpIntervalTime) {
            $remainingTime = CarbonInterval::seconds($otpIntervalTime - Carbon::parse($latestToken->created_at)->diffInSeconds())->cascade()->forHumans();

            return response()->json([
                'status' => 0,
                'time_count' => $remainingTime,
                'message' => translate('Please try again after').' '.$remainingTime,
            ]);
        }

        $orderDetail = $this->orderDetailService->find(id: $request['order_details_id'], relations: ['order.customer']);

        if ($orderDetail === null) {
            return response()->json([
                'status' => 0,
                'message' => translate('Not found').' !',
            ]);
        }

        $contact = $this->getOrderContact(order: $orderDetail->order);
        $token = rand(1000, 9999);

        $this->digitalProductOtpVerificationService->storeTokenForIdentities(
            orderDetailId: $orderDetail->id,
            identities: [$contact['email'], $contact['phone']],
            token: $token,
        );

        $mailStatus = $this->getMailConfig()['status'] == 1
            ? $this->dispatchOtpMail(email: $contact['email'], userName: $contact['name'], token: $token)
            : 0;

        $smsStatus = SMSModule::sendCentralizedSMS($contact['phone'], $token) == 'not_found' ? 0 : 1;

        return response()->json([
            'mail_status' => $mailStatus,
            'sms_status' => $smsStatus,
            'status' => ($mailStatus || $smsStatus) ? 1 : 0,
            'new_time' => $otpIntervalTime,
            'message' => ($mailStatus || $smsStatus) ? translate(':item sent successfully', ['item' => translate('OTP')]) : translate('OTP sending failed'),
        ]);
    }

    public function index(Request $request): View
    {
        $order = $this->orderService->find(id: $request['order_id'], relations: ['customer', 'orderDetails.product']);
        $orderDetails = $this->orderDetailService->getList(
            dataLimit: 'all',
            relations: ['order.customer', 'product'],
            filters: ['order_id' => $request['order_id']],
        );

        if ($order === null || $order->customer === null || $order->customer['email'] != $request['email']) {
            ToastMagic::warning(translate('Invalid order'));

            return view(themeViewName(key: 'digital_product_download'));
        }

        return view(themeViewName(key: 'digital_product_download'), [
            'order' => $order,
            'orderDetails' => $orderDetails,
            'isDigitalProductExist' => $this->countDigitalLines(orderDetails: $orderDetails),
            'isDigitalProductReadyCount' => $this->countDownloadableLines(orderDetails: $orderDetails),
        ]);
    }

    private function sendDownloadOtp(Model $orderDetail, ?string $email, ?string $phone): JsonResponse
    {
        $mailConfig = $this->getMailConfig();
        $smsConfigStatus = $this->hasActiveSmsGateway() ? 1 : 0;

        if (! $mailConfig['status'] && ! $smsConfigStatus) {
            return $this->otpFailedResponse();
        }

        $token = rand(1000, 9999);

        if ($email == '' && $phone == '') {
            return $this->otpFailedResponse();
        }

        $latestToken = $this->digitalProductOtpVerificationService->findLatestByIdentity(orderDetailId: $orderDetail->id, email: $email, phone: $phone);
        $otpIntervalTime = getWebConfig(name: 'otp_resend_time') ?? 1;

        if ($latestToken !== null && Carbon::parse($latestToken->created_at)->diffInSeconds() < $otpIntervalTime) {
            return $this->otpVerifyResponse(
                orderDetailId: $orderDetail->id,
                timeCount: $otpIntervalTime - Carbon::parse($latestToken->created_at)->diffInSeconds(),
            );
        }

        $this->digitalProductOtpVerificationService->storeTokenForIdentities(
            orderDetailId: $orderDetail->id,
            identities: [$email, $phone],
            token: $token,
        );

        $storedToken = $this->digitalProductOtpVerificationService->findLatestByIdentity(orderDetailId: $orderDetail->id, email: $email, phone: $phone);
        $otpResendTime = getWebConfig(name: 'otp_resend_time') > 0 ? getWebConfig(name: 'otp_resend_time') : 0;
        $resendAvailableAt = Carbon::parse($storedToken->created_at)->addSeconds((int) $otpResendTime);
        $timeCount = $resendAvailableAt > Carbon::now() ? Carbon::now()->diffInSeconds($resendAvailableAt) : 0;

        $mailStatus = $mailConfig['status'] == 1
            ? $this->dispatchOtpMail(email: $email, userName: explode('@', $email)[0], token: $token)
            : 0;

        $smsResponse = SMSModule::sendCentralizedSMS($phone, $token);
        $smsStatus = ($smsResponse == 'not_found' || $smsConfigStatus == 0) ? 0 : 1;

        if ($mailStatus || $smsStatus) {
            return $this->otpVerifyResponse(orderDetailId: $orderDetail->id, timeCount: $timeCount);
        }

        return $this->otpFailedResponse();
    }

    private function otpVerifyResponse(int|string $orderDetailId, int|float $timeCount): JsonResponse
    {
        return response()->json([
            'status' => self::OTP_REQUIRED_STATUS,
            'file_path' => '',
            'view' => view(themeViewName(key: 'digital_product_order_otp_verify'), ['orderDetailID' => $orderDetailId, 'time_count' => $timeCount])->render(),
        ]);
    }

    private function otpFailedResponse(): JsonResponse
    {
        return response()->json([
            'status' => self::OTP_REQUIRED_STATUS,
            'file_path' => '',
            'view' => view(themeViewName(key: 'digital_product_order_otp_verify_failed'))->render(),
        ]);
    }

    private function dispatchOtpMail(?string $email, ?string $userName, int $token): int
    {
        try {
            event(new DigitalProductOtpVerificationEvent(email: $email, data: [
                'userName' => $userName,
                'userType' => 'customer',
                'templateName' => self::OTP_MAIL_TEMPLATE,
                'subject' => translate('Verification Code'),
                'title' => translate('Verification Code').'!',
                'verificationCode' => $token,
            ]));

            return 1;
        } catch (Exception) {
            return 0;
        }
    }

    private function getMailConfig(): array
    {
        $mailConfig = getWebConfig(name: 'mail_config');

        return $mailConfig['status'] == 0 ? getWebConfig(name: 'mail_config_sendgrid') : $mailConfig;
    }

    private function hasActiveSmsGateway(): bool
    {
        $filters = ['settings_type' => self::SMS_CONFIG_TYPE, 'is_active' => 1];

        if ((config('get_payment_publish_status') ?? 0) != 1) {
            $filters['key_name'] = Helpers::getDefaultSMSGateways();
        }

        return $this->settingService->count(filters: $filters) > 0;
    }

    private function getGuestContact(Model $order): array
    {
        $address = $order->shipping_address_data ?: $order->billing_address_data;

        return [
            'name' => $address?->contact_person_name ?? null,
            'email' => $address?->email ?? null,
            'phone' => $address?->phone ?? null,
        ];
    }

    private function getOrderContact(?Model $order): array
    {
        if ($order === null) {
            return ['name' => null, 'email' => '', 'phone' => ''];
        }

        if ($order->is_guest) {
            return $this->getGuestContact(order: $order);
        }

        return [
            'name' => $order->customer?->f_name,
            'email' => $order->customer?->email ?? '',
            'phone' => $order->customer?->phone ?? '',
        ];
    }

    private function getDigitalFile(Model $orderDetail): array
    {
        $productDetails = json_decode($orderDetail['product_details'], true);

        if ($productDetails['digital_product_type'] == self::DIGITAL_TYPE_READY_PRODUCT && $productDetails['digital_file_ready']) {
            $storedFile = storageLink('product/digital-product', $productDetails['digital_file_ready'], ($productDetails['storage_path'] ?? 'public'));

            return [
                'path' => $storedFile['path'],
                'name' => $productDetails['digital_file_ready'],
                'exists' => $storedFile['status'] == 200,
            ];
        }

        $storedFile = $orderDetail->digital_file_after_sell_full_url;

        return [
            'path' => $storedFile['path'],
            'name' => $orderDetail['digital_file_after_sell'],
            'exists' => $storedFile['status'] == 200,
        ];
    }

    private function countDigitalLines(Collection $orderDetails): int
    {
        return $orderDetails->filter(fn (Model $orderDetail) => $orderDetail->product?->product_type == self::PRODUCT_TYPE_DIGITAL)->count();
    }

    private function countDownloadableLines(Collection $orderDetails): int
    {
        return $orderDetails->filter(function (Model $orderDetail): bool {
            if ($orderDetail->product?->product_type != self::PRODUCT_TYPE_DIGITAL) {
                return false;
            }

            $digitalProductType = $orderDetail->product->retailDetail?->digital_product_type;

            return ($digitalProductType == self::DIGITAL_TYPE_READY_AFTER_SELL && $orderDetail->digital_file_after_sell)
                || $digitalProductType == self::DIGITAL_TYPE_READY_PRODUCT;
        })->count();
    }
}
