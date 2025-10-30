<?php

namespace App\Services;

use App\Contracts\PaymentRequestDriver;
use App\Models\BillingCycle;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
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
        $this->drivers['VENMO'] = new VenmoDriver();
        $this->drivers['CASH_APP'] = new CashAppDriver();
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
            if (!$member->isVerified()) {
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
        $paymentRequest = $this->createRequestRecord(
            $member,
            $amountCents,
            $note,
            $cycle,
            markAsSent: false,
            extraMetadata: [
                'note' => $note,
                'manual' => true,
            ],
        );

        if ($markAsSent) {
            $this->sendRequest($paymentRequest);
        }

        return $paymentRequest->fresh(['member.user', 'group.owner', 'cycle']);
    }

    public function verifyHandle(string $channel, string $handle): bool
    {
        try {
            $driver = $this->getDriver($channel);
            return $driver->verifyHandle($handle);
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
        $channel = $member->preferred_channel;
        $driver = $this->getDriver($channel);

        $deepLink = $driver->buildLink($member, $amountCents, $note);
        $webFallback = $this->buildWebFallbackLink($member);

        $cycleTimestamp = $this->resolveCycleTimestamp($cycle);
        $cycleYm = $cycleTimestamp->format('Y-m');

        $metadata = array_merge([
            'handle' => $member->getPaymentHandle(),
            'amount_display' => '$'.number_format($amountCents / 100, 2),
        ], $extraMetadata);

        return PaymentRequest::create([
            'group_id' => $member->group_id,
            'cycle_id' => $cycle?->id,
            'member_id' => $member->id,
            'amount_cents' => $amountCents,
            'method_hint' => $channel,
            'pr_code' => PRCode::make($member->group_id, $member->id, $cycleYm),
            'note' => $note,
            'deep_link' => $deepLink,
            'web_fallback_link' => $webFallback,
            'status' => $markAsSent ? 'sent' : 'pending',
            'initiated_at' => now(),
            'sent_at' => $markAsSent ? now() : null,
            'metadata' => $metadata,
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
                ? 'https://cash.app/'.ltrim($member->cash_tag, '$')
                : null,
            'VENMO' => $member->venmo_username
                ? 'https://venmo.com/'.ltrim($member->venmo_username, '@')
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
