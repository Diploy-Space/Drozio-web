<?php

namespace App\Http\Controllers\Web\v6\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Shop\ToggleRequest;
use App\Services\New\ShopFollowerService;
use Illuminate\Http\JsonResponse;

class FollowerController extends Controller
{
    public function __construct(private readonly ShopFollowerService $shopFollowerService) {}

    public function toggle(ToggleRequest $request): JsonResponse
    {
        if (! auth('customer')->check()) {
            return response()->json([
                'message' => translate('Login first'),
                'value' => 0,
            ]);
        }

        $shopId = (int) $request['shop_id'];

        $isFollowing = $this->shopFollowerService->toggleFollow(
            customerId: (int) auth('customer')->id(),
            shopId: $shopId,
        );

        return response()->json([
            'text' => $isFollowing ? translate('Unfollow') : translate('Follow'),
            'message' => ($isFollowing ? translate('Followed successfully') : translate('Unfollowed successfully')).'!',
            'value' => $isFollowing ? 1 : 2,
            'followers' => $this->shopFollowerService->count(filters: ['shop_id' => $shopId]),
        ]);
    }
}
