<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Product\ViewStyleUpdateRequest;
use Illuminate\Http\JsonResponse;

class ProductViewStyleController extends Controller
{
    private const SESSION_KEY = 'product_view_style';

    public function update(ViewStyleUpdateRequest $request): JsonResponse
    {
        session()->put(self::SESSION_KEY, $request['value']);

        return response()->json([
            'message' => translate('View style updated').'!',
        ]);
    }
}
