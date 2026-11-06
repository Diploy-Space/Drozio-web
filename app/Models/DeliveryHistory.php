<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryHistory extends Model
{
    protected $fillable = [
        'order_id',
        'deliveryman_id',
        'time',
        'longitude',
        'latitude',
        'location',
    ];

    protected $casts = [
        'id' => 'integer',
        'order_id' => 'integer',
        'deliveryman_id' => 'integer',
        'longitude' => 'string',
        'latitude' => 'string',
        'location' => 'string',
        'time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'deliveryman_id');
    }
}
