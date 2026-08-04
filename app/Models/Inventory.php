<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Inventory extends Model
{
    protected $table = 'inventories';
    protected $fillable = ['product_id', 'on_hand', 'reserved'];
    protected function casts(): array { return ['on_hand' => 'integer', 'reserved' => 'integer']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function available(): int { return $this->on_hand - $this->reserved; }
}
