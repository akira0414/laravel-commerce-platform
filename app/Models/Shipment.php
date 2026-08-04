<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = ['provider', 'provider_shipment_id', 'tracking_number', 'status', 'shipped_at', 'delivered_at', 'raw_payload'];

    protected function casts(): array
    {
        return ['status' => ShipmentStatus::class, 'shipped_at' => 'immutable_datetime', 'delivered_at' => 'immutable_datetime', 'raw_payload' => 'encrypted:array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
