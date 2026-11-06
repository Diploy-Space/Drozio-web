<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\Shared\Order\BaseOrderEditController;
use App\Services\New\OrderEditService;
use App\Services\New\OrderService;
use Illuminate\Database\Eloquent\Model;

class OrderEditController extends BaseOrderEditController
{
    protected function findEditOrder(int|string $orderId, array $relations): ?Model
    {
        return $this->orderService->findBy(params: ['id' => $orderId], relations: $relations);
    }

    protected function getEditOrderType(): string
    {
        return OrderEditService::TYPE_ADMIN;
    }

    protected function getEditor(): array
    {
        return [
            'edit_by' => OrderService::EDITOR_ADMIN,
            'edited_user_id' => auth('admin')->id(),
            'edited_user_name' => auth('admin')->user()->name,
        ];
    }

    protected function getViewPrefix(): string
    {
        return 'admin-views';
    }

    protected function getOrderDetailsRouteName(): string
    {
        return 'admin.retail.orders.details';
    }

    protected function getAddedProductResponseData(): array
    {
        return ['submit_button_text' => translate('Update Cart')];
    }
}
