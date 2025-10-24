<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentRequest extends Model
{
    protected $fillable = [
        'cycle_id',
        'member_id',
        'amount_cents',
        'channel',
        'link',
        'external_id',
        'status',
        'sent_at',
        'paid_at',
        'fail_reason',
        'metadata',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(BillingCycle::class, 'cycle_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class, 'member_id');
    }

    public function dunningEvents(): HasMany
    {
        return $this->hasMany(DunningEvent::class);
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    public function setAmountAttribute(float $value): void
    {
        $this->amount_cents = (int) ($value * 100);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' || $this->status === 'sent';
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Update cycle collected amount
        $this->cycle->increment('collected_amount_cents', $this->amount_cents);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'fail_reason' => $reason,
        ]);
    }
}
