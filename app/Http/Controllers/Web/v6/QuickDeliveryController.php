<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Models\ShippingAddress;
use App\Services\New\ProductRetailService;
use App\Services\New\QuickDelivery\QuickProductService;
use App\Services\New\QuickDelivery\QuickServiceabilityService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Uses the SAME home.blade.php, retail product cards, and existing shops. */
class QuickDeliveryController extends Controller
{
    private const SESSION_KEY = 'quick_delivery.address';

    public function __construct(
        private readonly QuickServiceabilityService $shopService,
        private readonly QuickProductService $quickProductService,
        private readonly ProductRetailService $productService,
    ) {}

    public function index(Request $request): View
    {
        $address = $this->getSelectedAddress($request);
        $ids = [];
        $shopCount = 0;

        if ($address !== null) {
            $shops = $this->shopService->getEligibleStores($address);
            $shopCount = $shops->count();
            $ids = $this->quickProductService->getAvailableProductIds($shops->modelKeys());
        }
        return view(themeViewName(key: 'home'), [
            'isQuickDelivery' => true,
            'quickAddress' => $address,
            'savedAddresses' => $this->savedAddresses(),
            'quickShopCount' => $shopCount,
            'quickProducts' => $this->getProducts($ids, 36),
            'quickFeaturedProducts' => $this->getProducts($ids, 12, 'featured_product_priority', ['featured' => 1]),
            'quickNewArrivalProducts' => $this->getProducts($ids, 8, 'new_arrival_product_list_priority'),
            'quickCategories' => $this->quickProductService->getAvailableCategories($ids),
        ]);
    }

    public function selectAddress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'address_id' => ['nullable', 'integer', 'min:1'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if (! empty($data['address_id'])) {
            $customerId = Auth::guard('customer')->id();
            if ($customerId === null) {
                throw ValidationException::withMessages(['address_id' => 'Sign in to use saved addresses.']);
            }

            $saved = ShippingAddress::query()
                ->where('customer_id', $customerId)
                ->find((int) $data['address_id']);

            if ($saved === null) {
                throw ValidationException::withMessages(['address_id' => 'Address not found in your account.']);
            }

            $selected = [
                'address' => trim((string) $saved->address),
                'address_id' => (int) $saved->getKey(),
                'customer_id' => (int) $customerId,
                'source' => 'saved',
                'latitude' => is_numeric($saved->latitude) ? (float) $saved->latitude : null,
                'longitude' => is_numeric($saved->longitude) ? (float) $saved->longitude : null,
            ];
        } else {
            $text = trim((string) ($data['address'] ?? ''));
            if ($text === '') {
                throw ValidationException::withMessages(['address' => 'Enter your delivery address.']);
            }

            // No geocode at this stage! The service checks exact address match first.
            $selected = [
                'address' => $text,
                'address_id' => null,
                'source' => 'manual',
                'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
            ];
        }

        if ($selected['address'] === '') {
            throw ValidationException::withMessages(['address' => 'Saved address is empty.']);
        }

        $request->session()->put(self::SESSION_KEY, $selected);
        $request->session()->forget('quick_delivery.checkout_quote');

        return redirect()->route('quick-delivery.index');
    }

    public function changeAddress(Request $request): RedirectResponse
    {
        $request->session()->forget([self::SESSION_KEY, 'quick_delivery.checkout_quote']);
        return redirect()->route('quick-delivery.index');
    }

    private function getSelectedAddress(Request $request): ?array
    {
        $selected = $request->session()->get(self::SESSION_KEY);
        if (! is_array($selected)) {
            return null;
        }

        if (($selected['source'] ?? '') === 'saved') {
            $customerId = Auth::guard('customer')->id();
            if ($customerId === null || (int) ($selected['customer_id'] ?? 0) !== (int) $customerId) {
                $request->session()->forget(self::SESSION_KEY);
                return null;
            }

            $saved = ShippingAddress::query()
                ->where('customer_id', $customerId)
                ->find((int) ($selected['address_id'] ?? 0));

            if ($saved === null) {
                $request->session()->forget(self::SESSION_KEY);
                return null;
            }

            $selected['address'] = trim((string) $saved->address);
            $selected['latitude'] = is_numeric($saved->latitude) ? (float) $saved->latitude : null;
            $selected['longitude'] = is_numeric($saved->longitude) ? (float) $saved->longitude : null;
        }

        return trim((string) ($selected['address'] ?? '')) === '' ? null : $selected;
    }

    private function savedAddresses(): Collection
    {
        $id = Auth::guard('customer')->id();
        return $id === null ? new Collection() : ShippingAddress::query()
            ->where('customer_id', $id)
            ->orderByDesc('id')
            ->get();
    }

    private function getProducts(array $ids, int $limit, ?string $sort = null, array $extra = []): Collection|LengthAwarePaginator
    {
        if ($ids === []) {
            return new Collection(); // Never accidentally show all Retail products.
        }

        return $this->productService->getList(
            dataLimit: $limit,
            relations: ['reviews', 'seller.shop'],
            filters: [ProductRetailService::FILTER_ACTIVE => true, 'id' => $ids, ...$extra],
            sortBy: $sort,
        );
    }
}
