<?php

namespace App\Traits;

use App\Enums\SessionKey;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;

trait CheckoutBlockerRedirect
{
    /**
     * A checkout step that cannot proceed sends the customer back to whatever page can fix it.
     * The cart page states every blocker inline, beside the control that resolves it, so a
     * redirect there carries the reasons in the session; anywhere else still needs a toast.
     *
     * @param  array{status?: int, message?: array<int, string>, errorType?: string, redirect?: string}  $response
     */
    protected function redirectWithCheckoutBlockers(array $response): RedirectResponse
    {
        $messages = array_values(array_filter($response['message'] ?? []));
        $target = $response['redirect'] ?? url('/');

        if ($target === route('shop-cart')) {
            return redirect($target)->with(SessionKey::CHECKOUT_BLOCKERS, [
                'type' => $response['errorType'] ?? null,
                'messages' => $messages,
            ]);
        }

        foreach ($messages as $message) {
            ToastMagic::error($message);
        }

        return redirect($target);
    }
}
