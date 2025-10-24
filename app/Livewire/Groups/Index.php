<?php

namespace App\Livewire\Groups;

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Groups')]
class Index extends Component
{
    public string $search = '';

    #[Computed]
    public function groups(): Collection
    {
        return auth()->user()
            ->ownedGroups()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->withCount('members')
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.groups.index', [
            'groups' => $this->groups,
        ])->layout('components.layouts.dashboard', [
            'heading' => 'Groups',
            'title' => 'Groups',
        ]);
    }
}
