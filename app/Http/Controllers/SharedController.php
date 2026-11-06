<?php

namespace App\Http\Controllers;

use App\Traits\ActivationClass;
use App\Traits\RecaptchaTrait;
use App\Utils\Helpers;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;

class SharedController extends Controller
{
    use ActivationClass;
    use RecaptchaTrait;

    public function getComponentView(): View
    {
        return view('layouts.admin.component');
    }

    public function getComponentSnippetsView(): View
    {
        return view('layouts.admin.component-snippets');
    }

    public function changeLanguage(Request $request): JsonResponse
    {
        $direction = 'ltr';
        $language = getWebConfig('language');
        foreach ($language as $data) {
            if ($data['code'] == $request['language_code']) {
                $direction = $data['direction'] ?? 'ltr';
            }
        }
        session()->forget('language_settings');
        Helpers::language_load();
        session()->put('local', $request['language_code']);
        Session::put('direction', $direction);
        Artisan::call('cache:clear');

        return response()->json(['message' => translate(':item changed successfully', ['item' => translate('Language')]).'.']);
    }

    public function getSessionRecaptchaCode(Request $request): JsonResponse
    {
        if (config('app.mode') == 'dev' && session()->has($request['sessionKey'])) {
            $code = session($request['sessionKey']);
        }

        return response()->json(['code' => $code ?? '']);
    }

    public function storeRecaptchaResponse(Request $request): JsonResponse
    {
        $response = $request->get('g_recaptcha_response', null);
        if ($response) {
            session()->put('g-recaptcha-response', $response);
        }

        return response()->json(['recaptcha' => $response]);
    }

    public function storeRecaptchaSession(Request $request): Response
    {
        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        if (session()->has($request['sessionKey'])) {
            Session::forget($request['sessionKey']);
        }
        Session::put($request['sessionKey'], $recaptchaBuilder->getPhrase());

        return response($recaptchaBuilder->get(), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT',
        ]);
    }

    public function getActivationCheckView(Request $request): View|RedirectResponse
    {
        $config = $this->getAddonsConfig();
        $adminPanel = $config['admin_panel'] ?? [];
        $status = ($this->is_local() || config('app.development_environment')) ? 1 : ($adminPanel['active'] ?? 0);

        return $status == 1 ? redirect(url('/')) : view('installation.activation-check');
    }

    public function activationCheck(Request $request): RedirectResponse
    {
        $response = $this->getRequestConfig(
            username: $request['username'],
            purchaseKey: $request['purchase_key'],
            softwareType: $request->get('software_type', base64_decode('cHJvZHVjdA==')),
            identifier: $request['email'],
        );
        $this->updateActivationConfig(app: 'admin_panel', response: $response);

        if (! empty($response['errors'])) {
            foreach ($response['errors'] as $error) {
                $message = is_array($error) ? ($error[0] ?? 'Unknown error') : $error;
                ToastMagic::error($message);
            }

            return redirect()->back();
        }

        return redirect(url('/'));
    }
}
