<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Newsletter\SubscribeRequest;
use App\Services\New\SubscriptionService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    private const HOME_REDIRECT_REFERRER_SEGMENTS = ['checkout-complete', 'web-payment'];

    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function subscribe(SubscribeRequest $request): RedirectResponse
    {
        $email = $request['subscription_email'];

        if ($this->subscriptionService->exists(params: ['email' => $email])) {
            ToastMagic::info(translate('You already subscribed this site'));
        } else {
            $this->subscriptionService->create(data: ['email' => $email]);
            ToastMagic::success(translate('Your subscription successfully done'));
        }

        if (Str::contains(url()->previous(), self::HOME_REDIRECT_REFERRER_SEGMENTS)) {
            return redirect()->route('home');
        }

        return back();
    }
}
