<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GroupMember extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'group_id',
        'user_id',
        'preferred_channel',
        'venmo_username',
        'cash_tag',
        'cash_phone',
        'cash_email',
        'share_type',
        'share_value',
        'status',
        'verified_at',
        'square_customer_id',
        'square_subscription_id',
        'square_subscription_created_at',
    ];

    protected $casts = [
        'share_value' => 'decimal:2',
        'verified_at' => 'datetime',
        'square_subscription_created_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['share_value', 'share_type', 'status', 'preferred_channel'])
            ->logOnlyDirty();
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'member_id');
    }

    public function isVerified(): bool
    {
        return ! is_null($this->verified_at) || ! is_null($this->getPaymentHandle());
    }

    public function calculateAmount(int $totalCents): int
    {
        if ($this->share_type === 'PERCENT') {
            return (int) ($totalCents * ($this->share_value / 100));
        }

        return (int) ($this->share_value * 100);
    }

    public function getPaymentHandle(): ?string
    {
        return match ($this->preferred_channel) {
            'VENMO' => $this->venmo_username,
            'CASH_APP' => $this->cash_tag ?? $this->cash_phone ?? $this->cash_email,
            default => null,
        };
    }

    /**
     * Check if member has Square subscription
     */
    public function hasSquareSubscription(): bool
    {
        return filled($this->square_customer_id) && filled($this->square_subscription_id);
    }

    /**
     * Check if member is enrolled in auto-square payments
     */
    public function isAutoSquareEnrolled(): bool
    {
        return $this->hasSquareSubscription() && $this->square_subscription_created_at !== null;
    }
}
