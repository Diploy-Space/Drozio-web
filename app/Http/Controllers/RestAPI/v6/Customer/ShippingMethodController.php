<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Shipping\ChooseForOrderRequest;
use App\Http\Requests\Customer\Shipping\SellerMethodListRequest;
use App\Http\Resources\V6\Customer\ShippingMethodResource;
use App\Services\New\CartService;
use App\Services\New\CartShippingService;
use App\Services\New\GuestUserService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use App\Traits\CustomerOwnerTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ShippingMethodController extends BaseApiController
{
    use CustomerOwnerTrait;

    public function __construct(
        private readonly ShippingMethodService $shippingMethodService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly CartShippingService $cartShippingService,
        private readonly CartService $cartService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'Active shipping methods of an in-house or vendor shop',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper. seller_is is admin for in-house, seller for a vendor shop.',
        tag: 'Shipping',
        query: ['guest_id' => 'nullable|integer', 'seller_id' => 'required|integer', 'seller_is' => 'required|in:admin,seller'],
        status: [401, 422],
        auth: false,
        guard: 'api',
    )]
    public function getSellerMethods(SellerMethodListRequest $request): JsonResponse
    {
        if ($this->resolveCustomerOwner($request) === null) {
            return $this->customerOwnerNotResolved();
        }

        $shippingMethods = $this->shippingMethodService->getActiveMethodsForSeller(
            sellerId: $request['seller_id'],
            sellerIs: $request->isInHouse() ? ShippingMethodService::CREATOR_ADMIN : ShippingMethodService::CREATOR_SELLER,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ShippingMethodResource::collection($shippingMethods),
                'params' => $this->paramsFormatter(['seller_id' => $request['seller_id'], 'seller_is' => $request['seller_is']]),
            ],
        );
    }

    #[ApiDoc(
        summary: 'One shipping method',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Shipping',
        query: ['guest_id' => 'nullable|integer'],
        status: [401, 404],
        auth: false,
        guard: 'api',
    )]
    public function find(Request $request, string|int $id): JsonResponse
    {
        if ($this->resolveCustomerOwner($request) === null) {
            return $this->customerOwnerNotResolved();
        }

        $shippingMethod = $this->shippingMethodService->find(id: $id);

        if ($shippingMethod === null) {
            return $this->responseFormatter(config: config('response.shipping_method_not_found'));
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new ShippingMethodResource($shippingMethod)],
        );
    }

    #[ApiDoc(
        summary: 'Choose a shipping method for one cart group, or for every group with all_cart_group',
        description: 'The cart group must belong to the caller, and the method must be one the group\'s shop offers.',
        tag: 'Shipping',
        status: [401, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function choose(ChooseForOrderRequest $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $ownerCartGroupIds = $this->getOwnerCartGroupIds(owner: $owner);

        if (! $request->isForAllCartGroups() && ! in_array($request['cart_group_id'], $ownerCartGroupIds, true)) {
            return $this->responseFormatter(config: config('response.shipping_cart_group_not_found'));
        }

        $shippingMethod = $this->shippingMethodService->find(id: $request['id']);

        if ($shippingMethod === null) {
            return $this->responseFormatter(config: config('response.shipping_method_not_found'));
        }

        $cartGroupIds = $request->isForAllCartGroups() ? $ownerCartGroupIds : [$request['cart_group_id']];

        foreach ($cartGroupIds as $cartGroupId) {
            if (! $this->isMethodOfferedForCartGroup(cartGroupId: $cartGroupId, shippingMethod: $shippingMethod, owner: $owner)) {
                return $this->responseFormatter(config: config('response.shipping_method_unavailable'));
            }
        }

        foreach ($cartGroupIds as $cartGroupId) {
            $this->cartShippingService->saveForCartGroup(
                cartGroupId: $cartGroupId,
                shippingMethodId: $shippingMethod->id,
                shippingCost: $shippingMethod->cost,
            );
        }

        return $this->responseFormatter(config: config('response.shipping_method_chosen'));
    }

    #[ApiDoc(
        summary: 'Shipping methods chosen for the cart groups of the customer or guest',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Shipping',
        query: ['guest_id' => 'nullable|integer'],
        status: [401],
        auth: false,
        guard: 'api',
    )]
    public function getChosen(Request $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $cartGroupIds = $this->getOwnerCartGroupIds(owner: $owner);
        $cartShippings = $this->cartShippingService->getList(
            dataLimit: 'all',
            filters: ['cart_group_id' => $cartGroupIds],
            sortBy: CartShippingService::SORT_OLDEST,
        );
        $checkedCartGroupIds = $this->cartService->getList(
            dataLimit: 'all',
            filters: ['is_checked' => 1, 'cart_group_id' => $cartGroupIds],
        )->pluck('cart_group_id')->unique()->flip();

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => $cartShippings->map(fn (Model $cartShipping) => $this->formatChosenShipping(
                    cartShipping: $cartShipping,
                    hasCheckedItem: $checkedCartGroupIds->has($cartShipping->cart_group_id),
                ))->values(),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Shipping type in force for an in-house or vendor shop',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper. Returns order_wise, category_wise or product_wise.',
        tag: 'Shipping',
        query: ['guest_id' => 'nullable|integer', 'seller_id' => 'required|integer', 'seller_is' => 'required|in:admin,seller'],
        status: [401, 422],
        auth: false,
        guard: 'api',
    )]
    public function getShippingType(SellerMethodListRequest $request): JsonResponse
    {
        if ($this->resolveCustomerOwner($request) === null) {
            return $this->customerOwnerNotResolved();
        }

        $sellerId = $request->isInHouse() ? ShippingTypeService::ADMIN_SELLER_ID : $request['seller_id'];

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => ['shipping_type' => $this->shippingTypeService->resolveTypeForSeller(sellerId: $sellerId)]],
        );
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function getOwnerCartGroupIds(array $owner): array
    {
        return $this->cartService->getCartGroupIds(
            customerId: $owner['is_guest'] ? null : (int) $owner['id'],
            guestId: $owner['is_guest'] ? $owner['id'] : 0,
        );
    }

    private function isMethodOfferedForCartGroup(string $cartGroupId, Model $shippingMethod, array $owner): bool
    {
        $cartItem = $this->cartService->findBy(params: ['cart_group_id' => $cartGroupId] + $this->customerOwnerFilters($owner));

        if ($cartItem === null) {
            return false;
        }

        $shop = ['added_by' => $cartItem->seller_is, 'user_id' => $cartItem->seller_id];
        $offeredMethods = $this->shippingMethodService->getCartShippingOptions(
            product: $shop,
            shippingType: $this->shippingTypeService->resolveTypeForProduct(product: $shop),
        );

        return $offeredMethods !== null && $offeredMethods->contains('id', $shippingMethod->id);
    }

    private function formatChosenShipping(Model $cartShipping, bool $hasCheckedItem): array
    {
        $freeDeliveryCartItem = $this->cartService->findFreeDeliveryCartItem(cartGroupId: $cartShipping->cart_group_id);
        $freeDeliveryOrderAmount = $this->cartService->getFreeDeliveryOrderAmount(
            cartGroupId: $cartShipping->cart_group_id,
            freeDeliveryCartItem: $freeDeliveryCartItem,
            seller: $freeDeliveryCartItem !== null && $freeDeliveryCartItem->seller_is != CartService::SELLER_IS_ADMIN
                ? $freeDeliveryCartItem->seller
                : null,
        );

        return [
            'id' => $cartShipping->id,
            'cart_group_id' => $cartShipping->cart_group_id,
            'shipping_method_id' => $cartShipping->shipping_method_id,
            'shipping_cost' => $cartShipping->shipping_cost,
            'created_at' => $cartShipping->created_at,
            'updated_at' => $cartShipping->updated_at,
            'free_delivery_status' => $freeDeliveryOrderAmount['status'],
            'is_check_item_exist' => $hasCheckedItem ? 1 : 0,
        ];
    }
}
