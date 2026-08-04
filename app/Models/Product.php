<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Product extends Model
{
    protected $fillable = ['sku', 'name', 'price', 'is_active'];
    protected function casts(): array { return ['price' => 'integer', 'is_active' => 'boolean']; }
    public function inventory(): HasOne { return $this->hasOne(Inventory::class); }
}
