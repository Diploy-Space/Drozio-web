<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Courier\app\Services\New\ProviderRegistry;

return new class extends Migration
{
    private const THIRD_PARTY_DELIVERY = 'third_party_delivery';

    public function up(): void
    {
        if (! Schema::hasTable('courier_shipments') || ! class_exists(ProviderRegistry::class)) {
            return;
        }

        $registry = app(ProviderRegistry::class);

        DB::table('courier_shipments')
            ->join('orders', 'orders.id', '=', 'courier_shipments.host_order_reference')
            ->whereNull('courier_shipments.superseded_at')
            ->whereNull('orders.delivery_type')
            ->orderByDesc('courier_shipments.id')
            ->get([
                'orders.id as order_id',
                'courier_shipments.provider',
                'courier_shipments.tracking_code',
                'courier_shipments.consignment_id',
            ])
            ->unique('order_id')
            ->each(function (object $shipment) use ($registry): void {
                DB::table('orders')
                    ->where('id', $shipment->order_id)
                    ->whereNull('delivery_type')
                    ->update([
                        'delivery_type' => self::THIRD_PARTY_DELIVERY,
                        'delivery_service_name' => $registry->labelFor($shipment->provider),
                        'third_party_delivery_tracking_id' => $shipment->tracking_code ?: $shipment->consignment_id,
                    ]);
            });
    }

    public function down(): void {}
};
