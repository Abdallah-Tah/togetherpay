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
        'auto_confirm_at',
        'undo_available_until',
        'fail_reason',
        'dispute_reason',
        'disputed_at',
        'metadata',
        'square_invoice_id',
        'square_subscription_id',
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
        'auto_confirm_at' => 'datetime',
        'undo_available_until' => 'datetime',
        'disputed_at' => 'datetime',
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
        // Calculate auto-confirm time based on member's trust score
        $trustScore = $this->member->trust_score ?? 100.0;
        $hoursUntilAutoConfirm = $trustScore >= 95.0 ? 1 : 24; // High trust = 1 hour, normal = 24 hours

        $this->update([
            'status' => 'member_marked',
            'marked_at' => now(),
            'auto_confirm_at' => now()->addHours($hoursUntilAutoConfirm),
        ]);
    }

    public function confirmPayment(): void
    {
        $this->update([
            'status' => 'owner_confirmed',
            'confirmed_at' => now(),
            'undo_available_until' => now()->addMinutes(10), // 10-minute undo window
        ]);

        // Update cycle collected amount
        $this->cycle->increment('collected_amount_cents', $this->amount_cents);

        // Update member's trust score (reward on-time payment)
        $this->updateMemberTrustScore(true);
    }

    public function autoConfirmPayment(): void
    {
        $this->update([
            'status' => 'owner_confirmed',
            'confirmed_at' => now(),
        ]);

        // Update cycle collected amount
        $this->cycle->increment('collected_amount_cents', $this->amount_cents);

        // Update member's trust score (reward on-time payment)
        $this->updateMemberTrustScore(true);
    }

    public function undoConfirmation(): bool
    {
        // Can only undo within the undo window
        if (! $this->undo_available_until || now()->isAfter($this->undo_available_until)) {
            return false;
        }

        // Reverse the cycle collected amount
        $this->cycle->decrement('collected_amount_cents', $this->amount_cents);

        $this->update([
            'status' => 'member_marked',
            'confirmed_at' => null,
            'undo_available_until' => null,
            'auto_confirm_at' => now()->addHours(24), // Reset grace period
        ]);

        return true;
    }

    public function reportIssue(string $reason): void
    {
        $this->update([
            'status' => 'disputed',
            'dispute_reason' => $reason,
            'disputed_at' => now(),
            'auto_confirm_at' => null, // Cancel auto-confirmation
        ]);
    }

    public function resolveDispute(): void
    {
        $this->update([
            'status' => 'member_marked',
            'dispute_reason' => null,
            'disputed_at' => null,
            'auto_confirm_at' => now()->addHours(24), // Restart grace period
        ]);
    }

    protected function updateMemberTrustScore(bool $onTime): void
    {
        if (! $this->member) {
            return;
        }

        $member = $this->member;

        if ($onTime) {
            $member->increment('on_time_payments');
        } else {
            $member->increment('late_payments');
        }

        // Calculate new trust score (simple algorithm)
        $totalPayments = $member->on_time_payments + $member->late_payments;
        if ($totalPayments > 0) {
            $newScore = $member->on_time_payments / $totalPayments * 100;
            $member->update(['trust_score' => $newScore]);
        }
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
        $this->cycle->increment('collected_amount_cents', $this->amount_cents);
    }
}
