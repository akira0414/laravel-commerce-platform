<?php
namespace App\Models;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Order extends Model
{
    protected $fillable = ['number', 'status', 'currency', 'subtotal', 'shipping_fee', 'total', 'customer_email', 'shipping_address', 'expires_at', 'paid_at'];
    protected function casts(): array { return ['status' => OrderStatus::class, 'shipping_address' => 'array', 'subtotal' => 'integer', 'shipping_fee' => 'integer', 'total' => 'integer', 'expires_at' => 'immutable_datetime', 'paid_at' => 'immutable_datetime']; }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function shipments(): HasMany { return $this->hasMany(Shipment::class); }
}
