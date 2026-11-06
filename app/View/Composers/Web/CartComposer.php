<?php

namespace App\View\Composers\Web;

use App\Enums\BusinessModule;
use App\Services\New\CartService;
use App\Services\New\CartShippingService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use App\Services\New\ShopService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CartComposer
{
    private const ADMIN_SELLER_ID = 0;

    private const IN_HOUSE_SELLER_ID = 1;

    private const ACTIVE = 1;

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    public function __construct(
        private readonly CartService $cartService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly ShopService $shopService,
        private readonly CartShippingService $cartShippingService,
        private readonly ShippingMethodService $shippingMethodService,
    ) {}

    public function compose(View $view): void
    {
        $view->with($this->resolved ??= $this->build(
            customerId: auth('customer')->id(),
            guestId: session('guest_id'),
        ));
    }

    /** @return array<string, mixed> */
    private function build(mixed $customerId, mixed $guestId): array
    {
        $cart = $this->cartItems($customerId, $guestId);
        $sellerIds = $cart->pluck('seller_id')->filter()->unique()->values()->all();
        $groupIds = $cart->pluck('cart_group_id')->filter()->unique()->values()->all();

        return [
            'cartAdminShipping' => $this->shippingTypeService->findBy(params: ['seller_id' => self::ADMIN_SELLER_ID, 'context' => BusinessModule::RETAIL]),
            'cartSellerShippingTypes' => $this->keyBySeller($sellerIds === [] ? Collection::make() : $this->shippingTypeService->getList(
                dataLimit: 'all',
                filters: ['seller_id' => $sellerIds, 'context' => BusinessModule::RETAIL],
            )),
            'cartShops' => $this->keyBySeller($sellerIds === [] ? Collection::make() : $this->shopService->getList(
                dataLimit: 'all',
                relations: ['seller'],
                filters: ['seller_id' => $sellerIds],
            )),
            'cartChosenShippings' => Collection::make($groupIds === [] ? [] : $this->cartShippingService->getList(
                dataLimit: 'all',
                filters: ['cart_group_id' => $groupIds],
            ))->groupBy('cart_group_id')->map->first(),
            'cartShippingMethods' => $this->shippingMethods($cart),
        ];
    }

    private function cartItems(mixed $customerId, mixed $guestId): Collection
    {
        $owner = $customerId
            ? ['customer_id' => $customerId, 'is_guest' => CartService::CUSTOMER]
            : ['customer_id' => $guestId, 'is_guest' => CartService::GUEST];

        if (empty($owner['customer_id'])) {
            return Collection::make();
        }

        return Collection::make($this->cartService->getList(
            dataLimit: 'all',
            filters: $owner + [CartService::FILTER_ACTIVE_PRODUCT => true],
        ));
    }

    private function keyBySeller(Collection|iterable $records): Collection
    {
        return Collection::make($records)->groupBy('seller_id')->map->first();
    }

    /** @return array<string, mixed> */
    private function shippingMethods(Collection $cart): array
    {
        $methods = [];

        foreach ($cart->unique(fn (Model $item): string => $item->seller_id.'|'.$item->seller_is) as $item) {
            $methods[$item->seller_id.'|'.$item->seller_is] = $this->methodsFor(
                creatorId: $item->seller_id,
                creatorType: $item->seller_is,
            );
        }

        $inHouseKey = self::IN_HOUSE_SELLER_ID.'|'.ShippingMethodService::CREATOR_ADMIN;
        $methods[$inHouseKey] ??= $this->methodsFor(
            creatorId: self::IN_HOUSE_SELLER_ID,
            creatorType: ShippingMethodService::CREATOR_ADMIN,
        );

        return $methods;
    }

    private function methodsFor(mixed $creatorId, string $creatorType): Collection
    {
        $filters = ['status' => self::ACTIVE, 'creator_type' => $creatorType, 'context' => BusinessModule::RETAIL];

        if ($creatorType !== ShippingMethodService::CREATOR_ADMIN) {
            $filters['creator_id'] = $creatorId;
        }

        return Collection::make($this->shippingMethodService->getList(dataLimit: 'all', filters: $filters));
    }
}
