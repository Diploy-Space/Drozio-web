<?php

namespace App\View\Composers\Web;

use App\Enums\BusinessModule;
use App\Services\New\WishlistService;
use Illuminate\View\View;

class WishlistPanelComposer
{
    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    public function __construct(
        private readonly WishlistService $wishlistService,
    ) {}

    public function compose(View $view): void
    {
        $view->with($this->resolved ??= $this->build(customerId: auth('customer')->id()));
    }

    /** @return array<string, mixed> */
    private function build(?int $customerId): array
    {
        if (! businessModuleManager()->isAvailable(BusinessModule::WHOLESALE)) {
            return [
                'headerModuleWishlists' => [],
                'headerModuleWishlistCount' => 0,
                'headerWishlistCounts' => [],
                'headerWishlistCount' => $this->wishlistService->countForCustomer(customerId: $customerId),
            ];
        }

        $counts = $this->wishlistService->getCustomerCountsByBusinessModule(customerId: $customerId);

        $moduleWishlists = [
            $this->formatModuleWishlist(
                label: translate('Retail'),
                itemCount: $counts[BusinessModule::RETAIL] ?? 0,
                viewUrl: route('wishlists.index'),
                clearUrl: route('wishlists.destroy-all', ['business_module' => BusinessModule::RETAIL]),
            ),
            $this->formatModuleWishlist(
                label: translate('Wholesale'),
                itemCount: $counts[BusinessModule::WHOLESALE] ?? 0,
                viewUrl: route('wholesale.wishlist.index'),
                clearUrl: route('wishlists.destroy-all', ['business_module' => BusinessModule::WHOLESALE]),
            ),
        ];

        return [
            'headerModuleWishlists' => $moduleWishlists,
            'headerModuleWishlistCount' => array_sum(array_column($moduleWishlists, 'item_count')),
            'headerWishlistCounts' => $counts,
            'headerWishlistCount' => array_sum($counts),
        ];
    }

    /** @return array{label: string, item_count: int, view_url: string, clear_url: string} */
    private function formatModuleWishlist(string $label, int $itemCount, string $viewUrl, string $clearUrl): array
    {
        return [
            'label' => $label,
            'item_count' => $itemCount,
            'view_url' => $viewUrl,
            'clear_url' => $clearUrl,
        ];
    }
}
