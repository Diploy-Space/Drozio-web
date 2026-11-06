<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Services\New\OrderService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentResultController extends Controller
{
    private const FLAG_SUCCESS = 'success';

    private const PAYMENT_MODE_APP = 'app';

    private const SESSION_PAYMENT_MODE = 'payment_mode';

    private const SESSION_ORDER_SUCCESS_IDS = 'order_success_ids';

    private const SESSION_NEW_CUSTOMER_REGISTER = 'newCustomerRegister';

    private const TOKEN_PAIR_SEPARATOR = '&&';

    private const TOKEN_TRANSACTION_REFERENCE = 'transaction_reference';

    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse|RedirectResponse
    {
        $isAppPayment = session(self::SESSION_PAYMENT_MODE) === self::PAYMENT_MODE_APP;

        if ($request['flag'] !== self::FLAG_SUCCESS) {
            if ($isAppPayment) {
                return $this->fail();
            }

            ToastMagic::error(translate('Payment failed').'!');

            return redirect(url('/'));
        }

        if ($isAppPayment) {
            return $this->success();
        }

        $transactionReference = $this->getTransactionReference(token: $request['token']);
        if ($transactionReference) {
            $orderIds = $this->orderService
                ->getList(dataLimit: 'all', filters: ['transaction_ref' => $transactionReference])
                ->pluck('id')
                ->toArray();
            session([self::SESSION_ORDER_SUCCESS_IDS => $orderIds]);
        }

        ToastMagic::success(translate('Payment successful'));
        session()->forget(self::SESSION_NEW_CUSTOMER_REGISTER);

        return auth('customer')->check()
            ? redirect()->route('account-oder')
            : redirect()->route('home');
    }

    public function success(): JsonResponse
    {
        return response()->json(['message' => 'Payment succeeded']);
    }

    public function fail(): JsonResponse
    {
        return response()->json(['message' => 'Payment failed'], 403);
    }

    private function getTransactionReference(?string $token): ?string
    {
        if (empty($token)) {
            return null;
        }

        $tokenData = [];
        foreach (explode(self::TOKEN_PAIR_SEPARATOR, base64_decode($token)) as $pair) {
            [$key, $value] = explode('=', $pair);
            $tokenData[$key] = $value;
        }

        return $tokenData[self::TOKEN_TRANSACTION_REFERENCE] ?? null;
    }
}
