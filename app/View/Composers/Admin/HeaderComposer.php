<?php

namespace App\View\Composers\Admin;

use App\Services\New\ContactService;
use App\Services\New\OrderService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class HeaderComposer
{
    private const RECENT_ORDER_LIMIT = 5;

    private const UNSEEN = 0;

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly ContactService $contactService,
    ) {}

    public function compose(View $view): void
    {
        $view->with($this->resolved ??= $this->build());
    }

    /** @return array<string, mixed> */
    private function build(): array
    {
        $moduleKey = activeBusinessModule()->key;
        $surface = config("business_modules.admin_pending_orders.{$moduleKey}");
        $showOrderIcon = $surface !== null && Helpers::module_permission_check($surface['permission']);

        return [
            'headerShowOrderIcon' => $showOrderIcon,
            'headerPendingOrderCount' => $showOrderIcon ? $this->orderService->countPendingByBusinessModule($moduleKey) : 0,
            'headerRecentOrders' => $showOrderIcon ? $this->recentPendingOrders($moduleKey) : new Collection,
            'headerOrderListUrl' => $showOrderIcon ? route($surface['list_route'], ['status' => OrderService::STATUS_PENDING]) : null,
            'headerOrderDetailsRoute' => $showOrderIcon ? $surface['details_route'] : null,
            'headerUnseenMessages' => $this->contactService->count(['seen' => self::UNSEEN]),
        ];
    }

    private function recentPendingOrders(string $moduleKey): Collection
    {
        return $this->orderService->getRecentPendingByBusinessModule(
            businessModule: $moduleKey,
            limit: self::RECENT_ORDER_LIMIT,
            relations: ['details.productAllStatus'],
        );
    }
}
