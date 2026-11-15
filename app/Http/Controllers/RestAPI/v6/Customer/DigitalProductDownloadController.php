<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Events\DigitalProductOtpVerificationEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Order\DigitalDownloadOtpResendRequest;
use App\Http\Requests\Customer\Order\DigitalDownloadOtpVerifyRequest;
use App\Services\New\DigitalProductOtpVerificationService;
use App\Services\New\GuestUserService;
use App\Services\New\OrderDetailService;
use App\Services\New\SettingService;
use App\Traits\CustomerOwnerTrait;
use App\Utils\Helpers;
use App\Utils\SMSModule;
use Carbon\CarbonInterval;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DigitalProductDownloadController extends BaseApiController
{
    use CustomerOwnerTrait;

    private const OTP_CONTACT_MISSING_STATUS = 2;

    private const DOWNLOAD_ACTION = 'download';

    private const OTP_MAIL_TEMPLATE = 'digital-product-otp';

    private const SMS_CONFIG_TYPE = 'sms_config';

    private const SMS_NOT_SENT_RESPONSE = 'not_found';

    private const OTP_RESEND_TIME_SETTING = 'otp_resend_time';

    public function __construct(
        private readonly OrderDetailService $orderDetailService,
        private readonly DigitalProductOtpVerificationService $digitalProductOtpVerificationService,
        private readonly SettingService $settingService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'Download a purchased digital file, or send a download OTP for a guest order',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper. A signed-in owner receives the file; a guest order receives an OTP instead.',
        tag: 'Order',
        query: ['guest_id' => 'nullable|integer'],
        status: [401, 403, 404, 429],
        auth: false,
        guard: 'api',
    )]
    public function download(Request $request, int|string $orderDetailId): JsonResponse|BinaryFileResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $orderDetail = $this->findOwnedOrderDetail(owner: $owner, orderDetailId: $orderDetailId);

        if ($orderDetail === null) {
            return $this->orderDetailNotFound();
        }

        if ($orderDetail->order->payment_status !== OrderDetailService::PAYMENT_STATUS_PAID) {
            return $this->responseFormatter(config: config('response.digital_download_payment_unconfirmed'));
        }

        if ($orderDetail->order->is_guest) {
            $contact = $this->getAddressContact(order: $orderDetail->order, missingAddressValue: '');

            return $this->sendDownloadOtp(orderDetail: $orderDetail, contact: $contact);
        }

        return $this->downloadFile(orderDetail: $orderDetail);
    }

    #[ApiDoc(
        summary: 'Send a new digital download OTP to the order contact',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Order',
        status: [401, 403, 404, 422, 429],
        auth: false,
        guard: 'api',
    )]
    public function sendOtp(DigitalDownloadOtpResendRequest $request, int|string $orderDetailId): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $orderDetail = $this->findOwnedOrderDetail(owner: $owner, orderDetailId: $orderDetailId);

        if ($orderDetail === null) {
            return $this->orderDetailNotFound();
        }

        $waitSeconds = $this->digitalProductOtpVerificationService->getResendWaitSeconds(
            latestToken: $this->digitalProductOtpVerificationService->findBy(params: ['order_details_id' => $orderDetail->id]),
        );

        if ($waitSeconds > 0) {
            return $this->responseFormatter(config: config('response.otp_resend_wait'), content: [
                'status' => 0,
                'time_count_in_second' => $waitSeconds,
                'message' => translate('Please try again after :time', ['time' => CarbonInterval::seconds($waitSeconds)->cascade()->forHumans()]),
            ]);
        }

        $contact = $this->getAddressContact(order: $orderDetail->order, missingAddressValue: null);
        $token = rand(1000, 9999);

        $mailStatus = $this->getMailConfig()['status'] == 1
            ? $this->dispatchOtpMail(email: $contact['email'], userName: $contact['name'], token: $token)
            : 0;

        $smsStatus = SMSModule::sendCentralizedSMS($contact['phone'], $token) == self::SMS_NOT_SENT_RESPONSE ? 0 : 1;
        $isSent = $mailStatus || $smsStatus;

        if ($isSent) {
            $this->digitalProductOtpVerificationService->storeTokenForIdentities(
                orderDetailId: $orderDetail->id,
                identities: [$contact['email'], $contact['phone']],
                token: $token,
            );
        }

        return $this->responseFormatter(
            config: config($isSent ? 'response.digital_download_otp_sent' : 'response.digital_download_otp_send_failed'),
            content: [
                'mail_status' => $mailStatus,
                'sms_status' => $smsStatus,
                'status' => $isSent ? 1 : 0,
                'new_time' => getWebConfig(name: self::OTP_RESEND_TIME_SETTING) ?? 1,
            ],
        );
    }

    #[ApiDoc(
        summary: 'Verify a digital download OTP and download the file',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper. Pass action=download to consume the OTP.',
        tag: 'Order',
        status: [401, 403, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function verifyOtp(DigitalDownloadOtpVerifyRequest $request, int|string $orderDetailId): JsonResponse|BinaryFileResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $orderDetail = $this->findOwnedOrderDetail(owner: $owner, orderDetailId: $orderDetailId);

        if ($orderDetail === null) {
            return $this->orderDetailNotFound();
        }

        $tokenParams = ['token' => $request['otp'], 'order_details_id' => $orderDetail->id];

        if ($this->digitalProductOtpVerificationService->findValidToken(
            orderDetailId: $orderDetail->id,
            token: $request['otp'],
        ) === null) {
            return $this->responseFormatter(config: config('response.digital_download_otp_incorrect'));
        }

        if ($request['action'] === self::DOWNLOAD_ACTION) {
            $this->digitalProductOtpVerificationService->deleteBy(params: $tokenParams);
        }

        return $this->downloadFile(orderDetail: $orderDetail);
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function findOwnedOrderDetail(array $owner, int|string $orderDetailId): ?Model
    {
        return $this->orderDetailService->findForCustomerOwner(
            id: $orderDetailId,
            customerId: $owner['id'],
            isGuest: $owner['is_guest'],
            relations: ['order', 'product'],
        );
    }

    private function sendDownloadOtp(Model $orderDetail, array $contact): JsonResponse
    {
        $mailConfigStatus = $this->getMailConfig()['status'];
        $smsConfigStatus = $this->hasActiveSmsGateway() ? 1 : 0;
        $configStatus = ['email_config_status' => $mailConfigStatus, 'sms_config_status' => $smsConfigStatus];

        if (! $mailConfigStatus && ! $smsConfigStatus) {
            return $this->responseFormatter(config: config('response.digital_download_otp_unavailable'), content: ['status' => 0, ...$configStatus]);
        }

        $token = rand(1000, 9999);

        if ($contact['email'] == '' && $contact['phone'] == '') {
            return $this->responseFormatter(config: config('response.digital_download_otp_contact_missing'), content: [
                'status' => self::OTP_CONTACT_MISSING_STATUS,
                'file_path' => '',
            ]);
        }

        $waitSeconds = $this->digitalProductOtpVerificationService->getResendWaitSeconds(
            latestToken: $this->findLatestToken(orderDetail: $orderDetail, contact: $contact),
        );

        if ($waitSeconds > 0) {
            return $this->responseFormatter(config: config('response.otp_resend_wait'), content: [
                'status' => 0,
                ...$configStatus,
                'time_count_in_second' => $waitSeconds,
            ]);
        }

        $this->digitalProductOtpVerificationService->storeTokenForIdentities(
            orderDetailId: $orderDetail->id,
            identities: [$contact['email'], $contact['phone']],
            token: $token,
        );

        $countdownSeconds = $this->digitalProductOtpVerificationService->getResendCountdownSeconds(
            storedToken: $this->findLatestToken(orderDetail: $orderDetail, contact: $contact),
        );

        $mailStatus = $mailConfigStatus == 1
            ? $this->dispatchOtpMail(email: $contact['email'], userName: $contact['name'], token: $token)
            : 0;

        $smsResponse = SMSModule::sendCentralizedSMS($contact['phone'], $token);
        $smsStatus = ($smsResponse == self::SMS_NOT_SENT_RESPONSE || $smsConfigStatus == 0) ? 0 : 1;
        $isSent = $mailStatus || $smsStatus;

        return $this->responseFormatter(
            config: config($isSent ? 'response.digital_download_otp_sent' : 'response.digital_download_otp_send_failed'),
            content: [
                'status' => $isSent ? 1 : 0,
                ...$configStatus,
                'email_sent_status' => $mailStatus,
                'sms_sent_status' => $smsStatus,
                'time_count_in_second' => $countdownSeconds,
            ],
        );
    }

    private function findLatestToken(Model $orderDetail, array $contact): ?Model
    {
        return $this->digitalProductOtpVerificationService->findLatestByIdentity(
            orderDetailId: $orderDetail->id,
            email: $contact['email'],
            phone: $contact['phone'],
        );
    }

    private function downloadFile(Model $orderDetail): JsonResponse|BinaryFileResponse
    {
        $fileName = $this->orderDetailService->getDownloadableFileName(orderDetail: $orderDetail);
        $filePath = storage_path('app/public/'.OrderDetailService::DIGITAL_FILE_DIRECTORY.$fileName);

        if (empty($fileName) || ! File::isFile($filePath)) {
            return $this->responseFormatter(config: config('response.digital_download_file_not_found'));
        }

        return response()->download($filePath);
    }

    private function getAddressContact(Model $order, ?string $missingAddressValue): array
    {
        $address = $order->shipping_address_data ?: $order->billing_address_data;

        if (! $address) {
            return ['name' => null, 'email' => $missingAddressValue, 'phone' => $missingAddressValue];
        }

        return [
            'name' => $address->contact_person_name ?? null,
            'email' => $address->email ?? null,
            'phone' => $address->phone ?? null,
        ];
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

    private function orderDetailNotFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.order_not_found'));
    }
}
