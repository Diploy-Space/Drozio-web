<?php

namespace App\Http\Controllers\Vendor\Order;

use App\Http\Controllers\Shared\Order\BaseOrderEditController;
use App\Services\New\OrderEditService;
use Illuminate\Database\Eloquent\Model;

class OrderEditController extends BaseOrderEditController
{
    protected function findEditOrder(int|string $orderId, array $relations): ?Model
    {
        return $this->orderService->findForSeller(id: $orderId, sellerId: (int) auth('seller')->id(), relations: $relations);
    }

    protected function getEditOrderType(): string
    {
        return OrderEditService::TYPE_VENDOR;
    }

    protected function getEditor(): array
    {
        $seller = auth('seller')->user();

        return [
            'edit_by' => OrderEditService::EDITOR_SELLER,
            'edited_user_id' => $seller->id,
            'edited_user_name' => $seller->f_name.' '.$seller->l_name,
        ];
    }

    protected function getViewPrefix(): string
    {
        return 'vendor-views';
    }

    protected function getOrderDetailsRouteName(): string
    {
        return 'vendor.retail.orders.details';
    }

    protected function getAddedProductResponseData(): array
    {
        return [];
    }
}
