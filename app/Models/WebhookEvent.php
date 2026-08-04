<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = ['event_id', 'provider', 'topic', 'payload_hash', 'payload', 'status', 'attempts', 'processed_at', 'last_error'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'attempts' => 'integer', 'processed_at' => 'immutable_datetime'];
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => '等待處理',
            'processing' => '處理中',
            'processed' => '處理完成',
            'failed' => '處理失敗',
            default => '未知狀態',
        };
    }
}
