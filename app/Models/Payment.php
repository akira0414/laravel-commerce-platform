<?php
namespace App\Models;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Payment extends Model
{
    protected $fillable = ['provider', 'provider_payment_id', 'status', 'amount', 'currency', 'failure_reason', 'paid_at', 'raw_payload'];
    protected function casts(): array { return ['status' => PaymentStatus::class, 'amount' => 'integer', 'paid_at' => 'immutable_datetime', 'raw_payload' => 'encrypted:array']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
