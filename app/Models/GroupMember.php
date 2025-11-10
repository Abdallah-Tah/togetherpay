<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GroupMember extends Model
{
    use LogsActivity, SoftDeletes;

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
    ];

    protected $casts = [
        'share_value' => 'decimal:2',
        'verified_at' => 'datetime',
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
     * Check if this member has a Square customer created.
     */
    public function hasSquareCustomer(): bool
    {
        return ! empty($this->square_customer_id);
    }

    /**
     * Check if this member has an active Square subscription.
     */
    public function hasSquareSubscription(): bool
    {
        return ! empty($this->square_subscription_id);
    }

    /**
     * Get the group owner's Square access token (for making API calls).
     */
    public function getOwnerSquareAccessToken(): ?string
    {
        if (! $this->group || ! $this->group->owner) {
            return null;
        }

        return $this->group->owner->getSquareAccessToken();
    }

    /**
     * Check if this member is in an auto-Square billing group.
     */
    public function isInAutoSquareGroup(): bool
    {
        return $this->group && $this->group->isAutoSquareBilling();
    }

    /**
     * Store Square customer and subscription IDs.
     */
    public function updateSquareIds(?string $customerId = null, ?string $subscriptionId = null): void
    {
        $data = [];

        if ($customerId !== null) {
            $data['square_customer_id'] = $customerId;
        }

        if ($subscriptionId !== null) {
            $data['square_subscription_id'] = $subscriptionId;
        }

        if (! empty($data)) {
            $this->update($data);
        }
    }

    /**
     * Clear Square IDs (used when disconnecting or canceling).
     */
    public function clearSquareIds(): void
    {
        $this->update([
            'square_customer_id' => null,
            'square_subscription_id' => null,
        ]);
    }
}
