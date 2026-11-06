<?php

namespace App\Utils;

use App\Services\New\OrderService;

class CustomerOrderListSections
{
    public const STATUS_TONE_DANGER = 'danger';

    public const STATUS_TONE_SUCCESS = 'success';

    public const STATUS_TONE_PRIMARY = 'primary';

    /** @return array<int, array{value: string, label: string, url: string, is_selected: bool}> */
    public static function getStatusFilterOptions(string $routeName, string $selectedStatus): array
    {
        $options = [OrderService::STATUS_GROUP_ALL => translate('All'), ...self::getStatusLabels()];

        return array_map(
            static fn (string $status, string $label): array => [
                'value' => $status,
                'label' => $label,
                'url' => $status === OrderService::STATUS_GROUP_ALL ? route($routeName) : route($routeName, ['status' => $status]),
                'is_selected' => $status === $selectedStatus,
            ],
            array_keys($options),
            $options,
        );
    }

    public static function getStatusFilterLabel(string $selectedStatus): string
    {
        return self::getStatusLabels()[$selectedStatus] ?? translate('All');
    }

    /** @return array<string, string> */
    public static function getStatusLabels(): array
    {
        return [
            OrderService::STATUS_PENDING => translate('Pending'),
            OrderService::STATUS_CONFIRMED => translate('Confirmed'),
            OrderService::STATUS_PROCESSING => translate('Packaging'),
            OrderService::STATUS_OUT_FOR_DELIVERY => translate('Out for delivery'),
            OrderService::STATUS_DELIVERED => translate('Delivered'),
            OrderService::STATUS_RETURNED => translate('Returned'),
            OrderService::STATUS_FAILED => translate('Failed to deliver'),
            OrderService::STATUS_CANCELED => translate('Canceled'),
        ];
    }

    public static function getStatusTone(string $orderStatus): string
    {
        return match ($orderStatus) {
            OrderService::STATUS_FAILED, OrderService::STATUS_CANCELED => self::STATUS_TONE_DANGER,
            OrderService::STATUS_CONFIRMED, OrderService::STATUS_PROCESSING, OrderService::STATUS_DELIVERED => self::STATUS_TONE_SUCCESS,
            default => self::STATUS_TONE_PRIMARY,
        };
    }
}
