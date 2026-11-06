<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Contact\AddRequest;
use App\Services\New\ContactService;
use App\Services\RecaptchaService;
use App\Traits\RecaptchaTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    use RecaptchaTrait;

    private const CAPTCHA_SESSION_KEY = 'default_captcha_value_contact';

    private const CAPTCHA_CODE_SESSION_KEY = 'default_captcha_code';

    private const RECAPTCHA_ACTION = 'contact';

    private const CAPTCHA_PHRASE_LENGTH = 4;

    public function __construct(private readonly ContactService $contactService) {}

    public function store(AddRequest $request): RedirectResponse
    {
        $result = RecaptchaService::verificationStatus(
            request: $request,
            session: self::CAPTCHA_SESSION_KEY,
            action: self::RECAPTCHA_ACTION,
        );

        if ($result && ! $result['status']) {
            ToastMagic::error($result['message']);

            return back();
        }

        $this->contactService->create(data: [
            'name' => $request['name'],
            'email' => $request['email'],
            'mobile_number' => $request['mobile_number'],
            'subject' => $request['subject'],
            'message' => $request['message'],
        ]);

        ToastMagic::success(translate('Message sent successfully'));

        return back();
    }

    public function captcha(string $tmp): Response
    {
        $builder = $this->generateDefaultReCaptcha(captureLength: self::CAPTCHA_PHRASE_LENGTH);

        $this->saveRecaptchaValueInSession(
            sessionKey: self::CAPTCHA_CODE_SESSION_KEY,
            sessionValue: $builder->getPhrase(),
        );

        return response($builder->get(), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
