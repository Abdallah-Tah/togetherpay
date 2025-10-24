<?php

namespace App\Services;

use App\Contracts\PaymentRequestDriver;
use App\Models\BillingCycle;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Services\PaymentDrivers\CashAppDriver;
use App\Services\PaymentDrivers\VenmoDriver;

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

    public function getDriver(string $channel): PaymentRequestDriver
    {
        if (!isset($this->drivers[$channel])) {
            throw new \InvalidArgumentException("Unknown payment channel: {$channel}");
        }

        return $this->drivers[$channel];
    }

    public function createRequestForMember(BillingCycle $cycle, GroupMember $member): PaymentRequest
    {
        $amountCents = $member->calculateAmount($cycle->total_amount_cents);
        $channel = $member->preferred_channel;
        $driver = $this->getDriver($channel);

        $groupName = $member->group->name;
        $periodStart = $cycle->period_start->format('M d');
        $periodEnd = $cycle->period_end->format('M d, Y');
        $note = "{$groupName} - {$periodStart} to {$periodEnd}";

        $link = $driver->buildLink($member, $amountCents, $note);

        return PaymentRequest::create([
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => $amountCents,
            'channel' => $channel,
            'link' => $link,
            'status' => 'pending',
            'metadata' => [
                'handle' => $member->getPaymentHandle(),
                'note' => $note,
            ],
        ]);
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

    public function verifyHandle(string $channel, string $handle): bool
    {
        try {
            $driver = $this->getDriver($channel);
            return $driver->verifyHandle($handle);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}
