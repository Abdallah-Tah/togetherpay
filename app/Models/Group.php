<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Group extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'timezone',
        'currency',
        'cadence',
        'due_day',
        'grace_days',
        'total_amount_cents',
        'status',
        'billing_mode',
    ];

    protected $casts = [
        'total_amount_cents' => 'integer',
        'due_day' => 'integer',
        'grace_days' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'total_amount_cents', 'cadence'])
            ->logOnlyDirty();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(BillingCycle::class);
    }

    public function exports(): HasMany
    {
        return $this->hasMany(Export::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active');
    }

    public function currentCycle()
    {
        return $this->cycles()
            ->whereIn('status', ['draft', 'collecting'])
            ->orderBy('due_at', 'desc')
            ->first();
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->total_amount_cents / 100;
    }

    public function setTotalAmountAttribute(float $value): void
    {
        $this->total_amount_cents = (int) ($value * 100);
    }

    /**
     * Check if group uses manual billing mode
     */
    public function isManualBilling(): bool
    {
        return $this->billing_mode === 'manual';
    }

    /**
     * Check if group uses automatic Square billing
     */
    public function isAutoSquareBilling(): bool
    {
        return $this->billing_mode === 'auto_square';
    }

    /**
     * Check if owner has Square connected (required for auto billing)
     */
    public function ownerHasSquareConnected(): bool
    {
        return $this->owner && $this->owner->hasSquareConnected();
    }
}
