<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create Group')]
class Create extends Component
{
    public string $name = '';
    public string $total_amount = '';
    public string $cadence = 'MONTHLY';
    public int $due_day = 1;
    public int $grace_days = 7;
    public string $timezone = 'America/New_York';
    public string $currency = 'USD';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'cadence' => ['required', 'in:MONTHLY,EVERY_4_WEEKS'],
            'due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:30'],
            'timezone' => ['required', 'string'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $group = Group::create([
            'owner_id' => auth()->id(),
            'name' => $validated['name'],
            'total_amount_cents' => (int) ($validated['total_amount'] * 100),
            'cadence' => $validated['cadence'],
            'due_day' => $validated['due_day'],
            'grace_days' => $validated['grace_days'],
            'timezone' => $validated['timezone'],
            'currency' => $validated['currency'],
            'status' => 'active',
        ]);

        if (! auth()->user()->isOwner()) {
            auth()->user()->update(['role' => 'owner']);
        }

        session()->flash('success', 'Group created successfully!');

        $this->redirect(route('app.groups.show', $group), navigate: true);
    }

    public function render()
    {
        return view('livewire.groups.create', [
            'title' => 'Create Group',
        ]);
    }
}
