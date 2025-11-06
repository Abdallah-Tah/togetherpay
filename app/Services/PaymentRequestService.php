<?php

namespace App\Services;

use App\Contracts\PaymentRequestDriver;
use App\Models\BillingCycle;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Notifications\PaymentRequestNotification;
use App\Notifications\PaymentRequestEventNotification;
use App\Services\PaymentDrivers\CashAppDriver;
use App\Services\PaymentDrivers\VenmoDriver;
use App\Support\PRCode;

class PaymentRequestService
{
    protected array $drivers = [];

    public function __construct()
    {
        $this->registerDrivers();
    }

    protected function registerDrivers(): void
    {
        $this->drivers['VENMO'] = new VenmoDriver;
        $this->drivers['CASH_APP'] = new CashAppDriver;
    }

    public function createRequestForMember(BillingCycle $cycle, GroupMember $member): PaymentRequest
    {
        $amountCents = $member->calculateAmount($cycle->total_amount_cents);
        $note = $this->buildCycleNote($member, $cycle);

        return $this->createRequestRecord(
            $member,
            $amountCents,
            $note,
            $cycle,
            markAsSent: false,
            extraMetadata: [
                'note' => $note,
                'cycle_id' => $cycle->id,
            ],
        );
    }

    public function generateRequestsForCycle(BillingCycle $cycle): int
    {
        $members = $cycle->group->activeMembers;
        $count = 0;

        foreach ($members as $member) {
            if (! $member->isVerified()) {
                continue;
            }

            $this->createRequestForMember($cycle, $member);
            $count++;
        }

        return $count;
    }

    public function sendRequest(PaymentRequest $request): bool
    {
        // Mark as sent and update timestamp
        $request->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // In MVP, we just generate the link
        // In future with FF_PAYMENT_API, we'll actually call the API here

        return true;
    }

    public function createManualRequest(
        GroupMember $member,
        int $amountCents,
        string $note,
        ?BillingCycle $cycle = null,
        bool $markAsSent = true
    ): PaymentRequest {
        $resolvedCycle = $this->resolveCycle($cycle, $member);

        $paymentRequest = $this->createRequestRecord(
            $member,
            $amountCents,
            $note,
            $resolvedCycle,
            markAsSent: false,
            extraMetadata: [
                'note' => $note,
                'manual' => true,
            ],
        );

        if ($markAsSent) {
            $this->sendRequest($paymentRequest);

            // Ensure related models are available for notification payloads
            $paymentRequest->loadMissing(['member.user', 'cycle.group']);

            if ($user = $paymentRequest->member?->user) {
                $user->notify(new PaymentRequestNotification($paymentRequest));
            }

            // Notify group owner (sender)
            $owner = $paymentRequest->cycle?->group?->owner ?? $paymentRequest->group?->owner;
            if ($owner) {
                $owner->notify(new PaymentRequestEventNotification($paymentRequest, 'created'));
            }
        }

        return $paymentRequest->fresh(['member.user', 'group.owner', 'cycle']);
    }

    public function verifyHandle(string $channel, string $handle): bool
    {
        try {
            $driver = $this->getDriver($channel);

            return $driver->validateAccount($handle);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    protected function getDriver(string $channel): PaymentRequestDriver
    {
        if (! isset($this->drivers[$channel])) {
            throw new \InvalidArgumentException("Unknown payment channel: {$channel}");
        }

        return $this->drivers[$channel];
    }

    protected function createRequestRecord(
        GroupMember $member,
        int $amountCents,
        string $note,
        ?BillingCycle $cycle,
        bool $markAsSent,
        array $extraMetadata = []
    ): PaymentRequest {
        if (! $cycle) {
            $cycle = $this->resolveCycle(null, $member);
        }

        $channel = $member->preferred_channel;
        $driver = $this->getDriver($channel);

        $cycleTimestamp = $this->resolveCycleTimestamp($cycle);
        $cycleYm = $cycleTimestamp->format('Y-m');
        $prCode = PRCode::make($member->group_id, $member->id, $cycleYm);

        $linkPayload = $driver->buildLink($member, $amountCents, $prCode, $note);

        if (is_string($linkPayload)) {
            $deepLink = $linkPayload;
            $webFallback = $this->buildWebFallbackLink($member);
        } else {
            $deepLink = $linkPayload['uri'] ?? $this->buildWebFallbackLink($member);
            $webFallback = $linkPayload['web_fallback'] ?? $this->buildWebFallbackLink($member);
        }

        $metadata = array_merge([
            'handle' => $member->getPaymentHandle(),
            'amount_display' => '$' . number_format($amountCents / 100, 2),
        ], $extraMetadata);

        return PaymentRequest::create([
            'group_id' => $member->group_id,
            'cycle_id' => $cycle?->id,
            'member_id' => $member->id,
            'amount_cents' => $amountCents,
            'method_hint' => $channel,
            'pr_code' => $prCode,
            'note' => $note,
            'deep_link' => $deepLink,
            'web_fallback_link' => $webFallback,
            'status' => $markAsSent ? 'sent' : 'pending',
            'initiated_at' => now(),
            'sent_at' => $markAsSent ? now() : null,
            'metadata' => $metadata,
        ]);
    }

    protected function resolveCycle(?BillingCycle $cycle, GroupMember $member): BillingCycle
    {
        if ($cycle) {
            return $cycle;
        }

        $group = $member->group;
        $current = $group->currentCycle();
        if ($current) {
            return $current;
        }

        $timezone = $group->timezone ?? config('app.timezone');
        $today = now($timezone)->startOfDay();

        return BillingCycle::create([
            'group_id' => $group->id,
            'period_start' => $today,
            'period_end' => $today,
            'due_at' => $today,
            'status' => 'collecting',
            'total_amount_cents' => $group->total_amount_cents ?? 0,
            'collected_amount_cents' => 0,
        ]);
    }

    protected function buildCycleNote(GroupMember $member, BillingCycle $cycle): string
    {
        $groupName = $member->group->name;
        $periodStart = $cycle->period_start->format('M d');
        $periodEnd = $cycle->period_end->format('M d, Y');

        return "{$groupName} - {$periodStart} to {$periodEnd}";
    }

    protected function buildWebFallbackLink(GroupMember $member): ?string
    {
        return match ($member->preferred_channel) {
            'CASH_APP' => $member->cash_tag
                ? 'https://cash.app/' . ltrim($member->cash_tag, '$')
                : null,
            'VENMO' => $member->venmo_username
                ? 'https://venmo.com/' . ltrim($member->venmo_username, '@')
                : null,
            default => null,
        };
    }

    protected function resolveCycleTimestamp(?BillingCycle $cycle)
    {
        if ($cycle && $cycle->due_at) {
            return $cycle->due_at;
        }

        if ($cycle && $cycle->period_start) {
            return $cycle->period_start;
        }

        return now();
    }
}
