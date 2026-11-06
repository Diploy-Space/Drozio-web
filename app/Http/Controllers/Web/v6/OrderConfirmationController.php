<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderConfirmationController extends Controller
{
    private const NEW_CUSTOMER_REGISTER_SESSION_KEY = 'newCustomerRegister';

    private const NEW_REGISTER_CUSTOMER_INFO_SESSION_KEY = 'newRegisterCustomerInfo';

    public function index(): View
    {
        $isNewCustomerInSession = session(self::NEW_CUSTOMER_REGISTER_SESSION_KEY);
        session()->forget(self::NEW_CUSTOMER_REGISTER_SESSION_KEY);

        return view(themeViewName(key: 'order_complete'), [
            'isNewCustomerInSession' => $isNewCustomerInSession,
        ]);
    }

    public function success(Request $request): View
    {
        $isNewCustomerInSession = session(self::NEW_CUSTOMER_REGISTER_SESSION_KEY);
        session()->forget(self::NEW_CUSTOMER_REGISTER_SESSION_KEY);
        session()->forget(self::NEW_REGISTER_CUSTOMER_INFO_SESSION_KEY);

        return view(themeViewName(key: 'order_complete'), [
            'order_ids' => $this->getRenderableOrderIds(encodedOrderIds: $request['orderIds']),
            'isNewCustomerInSession' => $isNewCustomerInSession,
        ]);
    }

    private function getRenderableOrderIds(mixed $encodedOrderIds): ?array
    {
        $orderIds = is_string($encodedOrderIds) ? json_decode($encodedOrderIds, true) : null;

        if (! is_array($orderIds)) {
            return null;
        }

        foreach ($orderIds as $orderId) {
            if ($orderId !== null && ! is_scalar($orderId)) {
                return null;
            }
        }

        return $orderIds;
    }
}
