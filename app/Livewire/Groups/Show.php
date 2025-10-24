<?php

namespace App\Livewire\Groups;

use App\Models\BillingCycle;
use App\Models\Group;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Group $group;

    public function mount(Group $group): void
    {
        $this->authorize('view', $group);

        $this->group = $group->load([
            'members.user',
            'cycles' => function ($query) {
                $query->latest()->with('paymentRequests')->take(5);
            },
        ]);
    }

    #[Computed]
    public function currentCycle(): ?BillingCycle
    {
        return $this->group->currentCycle();
    }

    public function render()
    {
        return view('livewire.groups.show', [
            'currentCycle' => $this->currentCycle,
            'title' => $this->group->name.' - Group',
            'pageHeading' => $this->group->name,
        ]);
    }
}
