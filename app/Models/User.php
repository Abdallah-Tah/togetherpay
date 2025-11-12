<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\PaymentLinkNotification;
use App\Notifications\PaymentRequestNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
        'cashapp_tag',
        'cash_tag',
        'cash_phone',
        'cash_email',
        'cashapp_customer_id',
        'venmo_username',
        'venmo_customer_id',
        'preferred_payment_method',
        'payment_metadata',
        'notification_preferences',
        'invitation_token',
        'invitation_sent_at',
        'invitation_accepted_at',
        'is_placeholder',
        'square_merchant_id',
        'square_access_token',
        'square_location_id',
        'square_connected_at',
        'preferred_billing_mode',
        'onboarding_completed_at',
    ];

    /**
     * Attributes that should be appended when serializing.
     *
     * @var list<string>
     */
    protected $appends = [
        'cash_tag',
        'cash_phone',
        'cash_email',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'invitation_sent_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'is_placeholder' => 'boolean',
            'password' => 'hashed',
            'payment_metadata' => 'array',
            'notification_preferences' => 'array',
            'square_connected_at' => 'datetime',
        ];
    }

    public function getCashTagAttribute(): ?string
    {
        return $this->attributes['cashapp_tag'] ?? null;
    }

    public function setCashTagAttribute(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            $this->attributes['cashapp_tag'] = null;

            return;
        }

        $raw = trim($value);
        $normalized = Str::startsWith($raw, '$') ? $raw : '$'.ltrim($raw, '$');
        $this->attributes['cashapp_tag'] = $normalized;
    }

    public function getCashPhoneAttribute(): ?string
    {
        $metadata = $this->payment_metadata ?? [];

        return $metadata['cash_phone'] ?? null;
    }

    public function setCashPhoneAttribute(?string $value): void
    {
        $this->setPaymentMetadataValue('cash_phone', $value);
    }

    public function getCashEmailAttribute(): ?string
    {
        $metadata = $this->payment_metadata ?? [];

        return $metadata['cash_email'] ?? null;
    }

    public function setCashEmailAttribute(?string $value): void
    {
        $this->setPaymentMetadataValue('cash_email', $value);
    }

    public function setVenmoUsernameAttribute(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            $this->attributes['venmo_username'] = null;

            return;
        }

        $normalized = '@'.ltrim($value, '@');
        $this->attributes['venmo_username'] = $normalized;
    }

    protected function setPaymentMetadataValue(string $key, ?string $value): void
    {
        $metadata = $this->payment_metadata ?? [];

        if ($value === null || trim($value) === '') {
            unset($metadata[$key]);
        } else {
            $metadata[$key] = $value;
        }

        $this->attributes['payment_metadata'] =
            empty($metadata) ? null : json_encode($metadata);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the groups owned by the user
     */
    public function ownedGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    /**
     * Get the group memberships for the user
     */
    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * Get the exports created by the user
     */
    public function exports(): HasMany
    {
        return $this->hasMany(Export::class, 'user_id');
    }

    /**
     * Registered push-notification devices for the user
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Phone number for Twilio SMS notifications
     */
    public function routeNotificationForTwilio(?Notification $notification = null): ?string
    {
        $phone = $this->phone;

        if (! $phone && $notification instanceof PaymentRequestNotification) {
            $phone = $notification->paymentRequest->member?->cash_phone;
        }

        if (! $phone && $notification instanceof PaymentLinkNotification) {
            $phone = $notification->paymentRequest->member?->cash_phone;
        }

        if (empty($phone)) {
            return null;
        }

        $raw = trim((string) $phone);

        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, '+')) {
            return preg_replace('/[^\+\d]/', '', $raw);
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+'.$digits;
        }

        return '+'.$digits;
    }

    /**
     * Check if user is an owner
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Determine if the user has configured at least one payment method.
     */
    public function hasPaymentMethodConfigured(): bool
    {
        return filled($this->venmo_username) || filled($this->cashapp_tag);
    }

    /**
     * Check if user has completed onboarding
     */
    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    /**
     * Mark onboarding as completed
     */
    public function completeOnboarding(): void
    {
        $this->update(['onboarding_completed_at' => now()]);
    }

    /**
     * Generate and assign invitation token
     */
    public function generateInvitationToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'invitation_token' => $token,
            'invitation_sent_at' => now(),
        ]);

        return $token;
    }

    /**
     * Accept invitation and mark user as active
     */
    public function acceptInvitation(): void
    {
        $this->update([
            'invitation_token' => null,
            'invitation_accepted_at' => now(),
            'is_placeholder' => false,
        ]);
    }

    /**
     * Check if user needs to complete invitation setup
     */
    public function needsInvitationSetup(): bool
    {
        return $this->is_placeholder && $this->invitation_token !== null;
    }

    /**
     * Check if invitation token is valid
     */
    public function isInvitationTokenValid(string $token): bool
    {
        if ($this->invitation_token !== $token) {
            return false;
        }

        // Token expires after 7 days
        if ($this->invitation_sent_at && $this->invitation_sent_at->addDays(7)->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if user has connected Square account
     */
    public function hasSquareAccount(): bool
    {
        return filled($this->square_merchant_id) && filled($this->square_access_token);
    }

    /**
     * Check if user's Square account is properly configured
     */
    public function isSquareConfigured(): bool
    {
        return $this->hasSquareAccount() && $this->square_connected_at !== null;
    }

    /**
     * Disconnect Square account
     */
    public function disconnectSquare(): void
    {
        $this->update([
            'square_merchant_id' => null,
            'square_access_token' => null,
            'square_location_id' => null,
            'square_connected_at' => null,
        ]);
    }

    /**
     * Set user's preferred billing mode for new groups
     */
    public function setPreferredBillingMode(string $mode): void
    {
        if (!in_array($mode, ['manual', 'auto_square'])) {
            throw new \InvalidArgumentException("Invalid billing mode: {$mode}");
        }

        $this->update(['preferred_billing_mode' => $mode]);
    }

    /**
     * Get user's preferred billing mode
     */
    public function getPreferredBillingMode(): string
    {
        return $this->preferred_billing_mode ?? 'manual';
    }
}
