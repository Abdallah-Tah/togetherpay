<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'group_id',
        'cycle_id',
        'member_id',
        'amount_cents',
        'method_hint',
        'pr_code',
        'note',
        'deep_link',
        'web_fallback_link',
        'external_id',
        'status',
        'evidence_url',
        'evidence_type',
        'initiated_at',
        'sent_at',
        'marked_at',
        'confirmed_at',
        'paid_at',
        'fail_reason',
        'metadata',
        'square_invoice_id',
        'square_payment_id',
        'square_confirmed_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'initiated_at' => 'datetime',
        'sent_at' => 'datetime',
        'marked_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'paid_at' => 'datetime',
        'square_confirmed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

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
        return in_array($this->status, ['paid', 'owner_confirmed', 'auto_verified'], true);
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'sent'], true);
    }

    public function isMarkedByMember(): bool
    {
        return $this->status === 'member_marked';
    }

    public function isConfirmed(): bool
    {
        return in_array($this->status, ['owner_confirmed', 'auto_verified'], true);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'member_marked',
            'marked_at' => now(),
        ]);
    }

    public function confirmPayment(): void
    {
        $this->update([
            'status' => 'owner_confirmed',
            'confirmed_at' => now(),
        ]);

        // Update cycle collected amount
        $this->cycle->increment('total_collected_cents', $this->amount_cents);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'fail_reason' => $reason,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeStatus($query, string|array $status)
    {
        return $query->whereIn('status', (array) $status);
    }

    /**
     * Scope for payments awaiting owner confirmation
     */
    public function scopeAwaitingConfirmation($query)
    {
        return $query->where('status', 'member_marked');
    }

    /**
     * Check if payment was verified by Square webhook
     */
    public function isSquareVerified(): bool
    {
        return filled($this->square_payment_id) && $this->square_confirmed_at !== null;
    }

    /**
     * Confirm payment via Square webhook
     */
    public function confirmViaSquare(string $paymentId): void
    {
        $this->update([
            'status' => 'auto_verified',
            'square_payment_id' => $paymentId,
            'square_confirmed_at' => now(),
        ]);

        // Update cycle collected amount
        $this->cycle->increment('total_collected_cents', $this->amount_cents);
    }
}
