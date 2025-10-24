<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingCycle extends Model
{
    protected $fillable = [
        'group_id',
        'period_start',
        'period_end',
        'due_at',
        'status',
        'total_amount_cents',
        'collected_amount_cents',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_at' => 'datetime',
        'total_amount_cents' => 'integer',
        'collected_amount_cents' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'cycle_id');
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->total_amount_cents / 100;
    }

    public function getCollectedAmountAttribute(): float
    {
        return $this->collected_amount_cents / 100;
    }

    public function getOutstandingAmountCentsAttribute(): int
    {
        return $this->total_amount_cents - $this->collected_amount_cents;
    }

    public function getCollectionRateAttribute(): float
    {
        if ($this->total_amount_cents === 0) {
            return 0;
        }

        return ($this->collected_amount_cents / $this->total_amount_cents) * 100;
    }

    public function isOverdue(): bool
    {
        return $this->due_at->isPast() && $this->status === 'collecting';
    }
}
