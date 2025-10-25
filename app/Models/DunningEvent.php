<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DunningEvent extends Model
{
    protected $fillable = [
        'payment_request_id',
        'kind',
        'channel',
        'sent_at',
        'delivered_at',
        'opened_at',
        'meta',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'meta' => 'array',
    ];

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    /**
     * Scope for specific dunning kind
     */
    public function scopeKind($query, string $kind)
    {
        return $query->where('kind', $kind);
    }

    /**
     * Scope for specific channel
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}
