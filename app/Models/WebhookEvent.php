<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WebhookEvent extends Model
{
    protected $fillable = ['event_id', 'provider', 'topic', 'payload_hash', 'payload', 'status', 'attempts', 'processed_at', 'last_error'];
    protected function casts(): array { return ['payload' => 'encrypted:array', 'attempts' => 'integer', 'processed_at' => 'immutable_datetime']; }
}
