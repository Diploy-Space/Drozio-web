<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Enums\SessionKey;
use App\Events\VendorRegistrationEvent;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\Auth\RegistrationRequest;
use App\Services\New\Auth\AdminAuthService;
use App\Services\New\Auth\VendorAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\New\HelpTopicService;
use App\Services\New\ShopService;
use App\Services\New\VendorKycService;
use App\Services\New\VendorRegistrationReasonService;
use App\Services\New\VendorWalletService;
use App\Services\RecaptchaService;
use App\Traits\EmailTemplateTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RegisterController extends BaseController
{
    use EmailTemplateTrait;

    public function __construct(
        private readonly VendorAuthService $vendorAuthService,
        private readonly ShopService $shopService,
        private readonly VendorWalletService $sellerWalletService,
        private readonly AdminAuthService $adminAuthService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly HelpTopicService $helpTopicService,
        private readonly VendorRegistrationReasonService $vendorRegistrationReasonService,
        private readonly VendorKycService $vendorKycService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $businessMode = getWebConfig(name: 'business_mode');
        $vendorRegistration = getWebConfig(name: 'seller_registration');
        if ((isset($businessMode) && $businessMode == 'single') || (isset($vendorRegistration) && $vendorRegistration == 0)) {
            ToastMagic::warning(translate('Access denied').'!!');

            return redirect('/');
        }
        $vendorRegistrationHeader = json_decode($this->businessSettingService->findBy(params: ['type' => 'vendor_registration_header'])?->value);
        $vendorRegistrationReasons = $this->vendorRegistrationReasonService->getList(
            dataLimit: 'all',
            filters: ['status' => 1],
            sortBy: VendorRegistrationReasonService::SORT_PRIORITY_DESC,
        );
        $sellWithUs = json_decode($this->businessSettingService->findBy(params: ['type' => 'vendor_registration_sell_with_us'])?->value);
        $downloadVendorApp = json_decode($this->businessSettingService->findBy(params: ['type' => 'download_vendor_app'])?->value);
        $businessProcess = json_decode($this->businessSettingService->findBy(params: ['type' => 'business_process_main_section'])?->value);
        $businessProcessStep = json_decode($this->businessSettingService->findBy(params: ['type' => 'business_process_step'])?->value);
        $helpTopics = $this->helpTopicService->getList(
            dataLimit: 'all',
            filters: ['type' => 'vendor_registration', 'status' => '1'],
        );

        return view(VIEW_FILE_NAMES['seller_registration'], compact('vendorRegistrationHeader', 'vendorRegistrationReasons', 'sellWithUs', 'downloadVendorApp', 'helpTopics', 'businessProcess', 'businessProcessStep'));
    }

    public function add(RegistrationRequest $request): JsonResponse
    {
        $result = RecaptchaService::verificationStatus(request: $request, session: SessionKey::VENDOR_RECAPTCHA_KEY, action: 'register');
        if ($result && ! $result['status']) {
            if ($request->ajax()) {
                return response()->json([
                    'error' => $result['message'],
                ]);
            }
        }
        if ($this->adminAuthService->identityExists(identity: $request['email'])) {
            return response()->json([
                'error' => translate('Email has already been taken'),
            ]);
        }

        $vendor = DB::transaction(function () use ($request) {
            $vendor = $this->vendorAuthService->create(
                data: $request->validated(),
                image: $request->file('image'),
            );

            $this->shopService->create(
                data: [
                    'seller_id' => $vendor->id,
                    'name' => $request['shop_name'],
                    'contact' => $request['phone'],
                    'address' => $request['shop_address'],
                    'tax_identification_number' => $request['tax_identification_number'] ?? $request['gst_number'],
                    'tin_expire_date' => $request['tin_expire_date'],
                ],
                images: [
                    'image' => $request->file('logo'),
                    'banner' => $request->file('banner'),
                    'bottom_banner' => $request->file('bottom_banner'),
                    'tin_certificate' => $request->file('tin_certificate'),
                ],
            );

            $this->sellerWalletService->createForSeller(sellerId: $vendor->id);

            // KYC: create/update profile, upload documents, attempt online verification (primary) with manual fallback, and submit
            $profile = $this->vendorKycService->getOrCreateForSeller($vendor);
            $mode = $request->input('kyc_verification_mode', 'online');
            $profile = $this->vendorKycService->updateProfile($profile, array_merge(
                $request->only([
                    'business_type','legal_name','trade_name','registration_type','gst_number',
                    'pan_number','pan_holder_name','pan_dob',
                    'business_address_line1','business_address_line2','business_city','business_state','business_pincode','business_country',
                    'bank_account_name','bank_name','bank_branch','bank_account_no','bank_ifsc',
                ]),
                ['verification_mode' => in_array($mode, ['online','manual'], true) ? $mode : 'online']
            ));

            $docs = [
                'pan_card' => $request->file('kyc_pan_card'),
                'gst_certificate' => $request->file('kyc_gst_certificate'),
                'address_proof' => $request->file('kyc_address_proof'),
                'bank_proof' => $request->file('kyc_bank_proof'),
                'business_registration' => $request->file('kyc_business_registration'),
            ];
            foreach ($docs as $type => $file) {
                if ($file) {
                    $this->vendorKycService->uploadDocument($profile, $type, $file);
                }
            }

            $sandbox = app(\App\Services\New\Sandbox\SandboxKycVerificationService::class);
            if ($mode === 'online' && $sandbox->isEnabled()) {
                $types = ['pan'];
                if (($profile->registration_type ?? '') === 'gst') $types[] = 'gstin';
                $types[] = 'bank';
                $this->vendorKycService->verifyOnline($profile->fresh(), $types, [
                    'pan_dob' => $request->input('pan_dob'),
                    'consent' => $request->input('online_consent', 'Y'),
                    'reason' => 'Vendor registration KYC verification',
                ]);
            } elseif ($mode === 'online' && !$sandbox->isEnabled() && !$sandbox->manualFallbackEnabled()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['kyc' => [translate('Online KYC verification is currently unavailable')]]);
            }

            $profile = $profile->fresh()->load('documents');
            $missing = $this->vendorKycService->missingRequirements($profile);
            if (!empty($missing)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'kyc_documents' => [translate('KYC incomplete. Verify online or upload required manual documents').': '.implode(', ', $missing)],
                ]);
            }

            $submit = $this->vendorKycService->submit($profile, 'vendor', $vendor->id, false);
            if (!($submit['ok'] ?? false)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['kyc_submit' => [$submit['message'] ?? translate('Unable to submit KYC'), 'missing' => $submit['missing'] ?? []]]);
            }

            return $vendor;
        });

        event(new VendorRegistrationEvent(email: $vendor->email, data: [
            'vendorName' => $vendor->f_name,
            'status' => $vendor->status,
            'subject' => translate('Vendor registration completed successfully'),
            'title' => translate('Vendor registration completed successfully'),
            'userType' => 'vendor',
            'templateName' => 'registration',
        ]));

        return response()->json(
            ['status' => 1, 'redirectRoute' => route('vendor.auth.login')]
        );
    }
}
