<?php

namespace App\Services\Web;

use App\Events\EmailVerificationEvent;
use App\Utils\Helpers;
use App\Utils\SMSModule;
use Exception;

class CustomerAuthService
{
    public function getCustomerVerificationToken(): string
    {
        return (config('app.mode') == 'live') ? rand(100000, 999999) : 123456;
    }

    public function getCustomerLoginDataReset(): array
    {
        return [
            'login_hit_count' => 0,
            'is_temp_blocked' => 0,
            'temp_block_time' => null,
            'updated_at' => now(),
        ];
    }

    public function sendCustomerPhoneVerificationToken($phone, $token): array
    {
        $response = SMSModule::sendCentralizedSMS($phone, $token);

        return [
            'response' => $response,
            'status' => 'success',
            'message' => translate('Please check your SMS for OTP'),
        ];
    }

    public function sendCustomerEmailVerificationToken($user, $token): array
    {
        $emailServicesSmtp = getWebConfig(name: 'mail_config');
        if ($emailServicesSmtp['status'] == 0) {
            $emailServicesSmtp = getWebConfig(name: 'mail_config_sendgrid');
        }
        if ($emailServicesSmtp['status'] == 1 && $user['email']) {
            try {
                $data = [
                    'userName' => $user['f_name'],
                    'subject' => translate('Registration Verification Code'),
                    'title' => translate('Registration Verification Code'),
                    'verificationCode' => $token,
                    'userType' => 'customer',
                    'templateName' => 'registration-verification',
                ];

                event(new EmailVerificationEvent(email: $user['email'], data: $data));

                return [
                    'status' => 'success',
                    'message' => translate('Check your email'),
                ];
            } catch (Exception $exception) {
                return [
                    'status' => 'error',
                    'message' => translate('Email is not configured').'. '.translate('Contact with the administrator'),
                ];
            }
        } else {
            return [
                'status' => 'error',
                'message' => translate('Email failed'),
            ];
        }
    }

    public function getCustomerRegisterData(object|array $request, object|array|null $referUser): array
    {
        return [
            'name' => $request['f_name'].' '.$request['l_name'],
            'f_name' => $request['f_name'],
            'l_name' => $request['l_name'],
            'email' => $request['email'],
            'phone' => $request['phone'],
            'is_active' => 1,
            'password' => bcrypt($request['password']),
            'referral_code' => Helpers::generate_referer_code(),
            'referred_by' => $referUser ? $referUser['id'] : null,
        ];
    }

    public function storeCustomerAuthReturnURL(): void
    {
        $historyUrls = session('recent_user_routes_history', []);
        if (! empty($historyUrls)) {
            $lastUrl = end($historyUrls);
            session()->put('keep_customer_login_redirect_url', $lastUrl);
        } else {
            session()->forget('keep_customer_login_redirect_url');
        }
    }

    public function getCustomerAuthReturnURL(): string
    {
        $keepReturnUrl = route('home');
        if (session()->has('keep_customer_login_redirect_url')) {
            $keepReturnUrl = session('keep_customer_login_redirect_url');
        }

        return $keepReturnUrl;
    }
}
