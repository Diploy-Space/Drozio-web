<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Currency\UpdateRequest;
use App\Services\New\CurrencyService;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    private const SESSION_CURRENCY_CODE = 'currency_code';

    private const SESSION_CURRENCY_SYMBOL = 'currency_symbol';

    private const SESSION_CURRENCY_EXCHANGE_RATE = 'currency_exchange_rate';

    private const SESSION_PRICE_CACHE_KEYS = ['default', 'usd'];

    private const SESSION_CHECKOUT_KEYS = ['builder.checkout.quote', 'builder.checkout.state'];

    public function __construct(private readonly CurrencyService $currencyService) {}

    public function update(UpdateRequest $request): JsonResponse
    {
        session()->put(self::SESSION_CURRENCY_CODE, $request['currency_code']);

        $currency = $this->currencyService->findBy(params: ['code' => $request['currency_code']], relations: []);

        session()->put(self::SESSION_CURRENCY_SYMBOL, $currency['symbol']);
        session()->put(self::SESSION_CURRENCY_EXCHANGE_RATE, $currency['exchange_rate']);
        session()->forget(self::SESSION_PRICE_CACHE_KEYS);
        session()->forget(self::SESSION_CHECKOUT_KEYS);

        return response()->json([
            'message' => translate('Currency changed to').' '.$currency['name'],
        ]);
    }
}
