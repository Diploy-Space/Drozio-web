<?php

namespace App\Services\Courier;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\Seller;
use Modules\Courier\app\Contracts\CourierBusinessModuleCatalog;
use Modules\Courier\app\Contracts\CourierOwnerResolver;
use Modules\Courier\app\Exceptions\CourierException;
use Modules\Courier\app\ValueObjects\CourierOwner;

class HostCourierOwnerResolver implements CourierBusinessModuleCatalog, CourierOwnerResolver
{
    public const BUSINESS_MODULE_INPUT = 'business_module';

    public const ORDER_REFERENCE_INPUT = 'host_order_reference';

    private const RESOLVABLE_BUSINESS_MODULES = [BusinessModule::RETAIL, BusinessModule::WHOLESALE];

    private const WHOLESALE_ROUTE_NAME_PREFIXES = ['admin.wholesale.', 'vendor.wholesale.'];

    private const WHOLESALE_API_PATH_PATTERN = 'api/v6/wholesale/*';

    private const CONFIG_ROUTE_NAMES = [
        'admin.settings.courier.config.index',
        'admin.settings.courier.config.update',
        'vendor.courier.config.index',
        'vendor.courier.config.update',
    ];

    public function __construct(private readonly Order $order) {}

    public function resolve(): CourierOwner
    {
        return $this->resolveAccountHolder()->inBusinessModule($this->resolveBusinessModule());
    }

    /** @return array<string, string> */
    public function businessModules(): array
    {
        $businessModules = [BusinessModule::RETAIL => translate('Retail')];

        if ($this->isWholesaleAvailable()) {
            $businessModules[BusinessModule::WHOLESALE] = translate('Wholesale');
        }

        if (! $this->isVendorRequest()) {
            return $businessModules;
        }

        return array_filter(
            $businessModules,
            fn (string $businessModule): bool => vendorDeliveryPartnerConfigAvailable(businessModule: $businessModule),
            ARRAY_FILTER_USE_KEY,
        );
    }

    public function resolveBusinessModule(): string
    {
        $orderBusinessModule = $this->findBusinessModuleForRequestedOrder();

        if ($orderBusinessModule !== null) {
            return $this->acceptedBusinessModule(businessModule: $orderBusinessModule);
        }

        if ($this->isWholesaleRequest()) {
            return BusinessModule::WHOLESALE;
        }

        if ($this->isCourierConfigRequest()) {
            return $this->acceptedConfigBusinessModule(businessModule: request()->input(self::BUSINESS_MODULE_INPUT));
        }

        return BusinessModule::RETAIL;
    }

    private function isVendorRequest(): bool
    {
        return request()->get('seller') instanceof Seller
            || str_starts_with($this->currentRouteName(), 'vendor.');
    }

    public function findBusinessModuleForOrder(int|string $orderId): ?string
    {
        return $this->order->newQuery()->whereKey($orderId)->value('business_module');
    }

    private function resolveAccountHolder(): CourierOwner
    {
        $apiSeller = request()->get('seller');

        if ($apiSeller instanceof Seller) {
            return CourierOwner::vendor(id: (int) $apiSeller->id);
        }

        if (! str_starts_with($this->currentRouteName(), 'vendor.')) {
            return CourierOwner::platform();
        }

        $vendorId = auth('seller')->id();

        throw_if($vendorId === null, CourierException::class, 'A vendor panel request cannot resolve its vendor owner.');

        return CourierOwner::vendor(id: (int) $vendorId);
    }

    private function findBusinessModuleForRequestedOrder(): ?string
    {
        $orderReference = request()->input(self::ORDER_REFERENCE_INPUT);

        if (! is_scalar($orderReference) || ! ctype_digit((string) $orderReference)) {
            return null;
        }

        return $this->findBusinessModuleForOrder(orderId: (string) $orderReference);
    }

    private function isWholesaleRequest(): bool
    {
        $routeName = $this->currentRouteName();

        foreach (self::WHOLESALE_ROUTE_NAME_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return request()->is(self::WHOLESALE_API_PATH_PATTERN);
    }

    private function isCourierConfigRequest(): bool
    {
        return in_array($this->currentRouteName(), self::CONFIG_ROUTE_NAMES, true);
    }

    private function acceptedBusinessModule(mixed $businessModule): string
    {
        return in_array($businessModule, self::RESOLVABLE_BUSINESS_MODULES, true) ? $businessModule : BusinessModule::RETAIL;
    }

    private function acceptedConfigBusinessModule(mixed $businessModule): string
    {
        $accepted = $this->acceptedBusinessModule(businessModule: $businessModule);

        return array_key_exists($accepted, $this->businessModules()) ? $accepted : BusinessModule::RETAIL;
    }

    private function isWholesaleAvailable(): bool
    {
        return function_exists('businessModuleManager')
            && businessModuleManager()->isAvailableInPanel(BusinessModule::WHOLESALE);
    }

    private function currentRouteName(): string
    {
        return (string) request()->route()?->getName();
    }
}
