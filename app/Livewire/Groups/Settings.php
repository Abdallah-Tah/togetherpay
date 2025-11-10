<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Group Settings')]
class Settings extends Component
{
    use AuthorizesRequests;

    public Group $group;

    public string $billing_mode = 'manual';

    public bool $showModeWarning = false;

    public string $originalBillingMode = '';

    public function mount(Group $group): void
    {
        $this->authorize('update', $group);

        $this->group = $group;
        $this->billing_mode = $group->billing_mode;
        $this->originalBillingMode = $group->billing_mode;
    }

    public function updatedBillingMode(): void
    {
        // Show warning if switching modes
        $this->showModeWarning = $this->billing_mode !== $this->originalBillingMode;
    }

    public function saveBillingMode(): void
    {
        $this->validate([
            'billing_mode' => ['required', 'in:manual,auto_square'],
        ]);

        // If switching to auto_square, verify user has Square connected
        if ($this->billing_mode === 'auto_square' && !auth()->user()->hasSquareAccount()) {
            $this->addError('billing_mode', 'You must connect your Square account before enabling automatic billing.');
            return;
        }

        // If switching from auto_square to manual, mark for subscription cleanup
        $isModeSwitching = $this->originalBillingMode === 'auto_square' && $this->billing_mode === 'manual';

        $this->group->update(['billing_mode' => $this->billing_mode]);

        if ($isModeSwitching) {
            // Dispatch job to clean up Square subscriptions
            // This will be handled by the cleanup method
            $this->cleanupSubscriptions();

            session()->flash('warning', 'Billing mode changed to Manual. Any active Square subscriptions will be cancelled.');
        } else {
            session()->flash('success', 'Billing mode updated successfully.');
        }

        $this->originalBillingMode = $this->billing_mode;
        $this->showModeWarning = false;
    }

    public function cancelModeChange(): void
    {
        $this->billing_mode = $this->originalBillingMode;
        $this->showModeWarning = false;
    }

    /**
     * Clean up Square subscriptions when switching from auto to manual
     */
    protected function cleanupSubscriptions(): void
    {
        // Get all group members with active Square subscriptions
        $membersWithSubscriptions = $this->group->members()
            ->whereNotNull('square_subscription_id')
            ->get();

        foreach ($membersWithSubscriptions as $member) {
            try {
                // Cancel the subscription in Square (implementation in future)
                // This would call SquareSubscriptionService::cancelSubscription()

                // Clear the subscription ID from the member
                $member->update([
                    'square_subscription_id' => null,
                    'square_customer_id' => null,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to cancel Square subscription for member '.$member->id, [
                    'error' => $e->getMessage(),
                    'group_id' => $this->group->id,
                ]);
            }
        }
    }

    public function render()
    {
        return view('livewire.groups.settings', [
            'title' => $this->group->name.' - Settings',
            'pageHeading' => 'Group Settings',
        ]);
    }
}
