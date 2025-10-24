<?php

namespace App\Livewire\PaymentRequests;

use App\Models\PaymentRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Payments')]
class Index extends Component
{
    use WithPagination;

    public string $filter = 'all';

    protected $queryString = ['filter'];

    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        $query = PaymentRequest::whereHas('cycle.group', function ($q) {
            $q->where('owner_id', auth()->id());
        })->with(['cycle.group', 'member.user']);

        $query = match ($this->filter) {
            'pending' => $query->whereIn('status', ['pending', 'sent']),
            'paid' => $query->where('status', 'paid'),
            'failed' => $query->whereIn('status', ['failed', 'expired']),
            default => $query,
        };

        return $query->latest()->paginate(20);
    }

    #[Computed]
    public function stats(): array
    {
        $baseQuery = PaymentRequest::whereHas('cycle.group', function ($q) {
            $q->where('owner_id', auth()->id());
        });

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->whereIn('status', ['pending', 'sent'])->count(),
            'paid' => (clone $baseQuery)->where('status', 'paid')->count(),
            'failed' => (clone $baseQuery)->whereIn('status', ['failed', 'expired'])->count(),
        ];
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.payment-requests.index', [
            'requests' => $this->requests,
            'stats' => $this->stats,
        ])->layout('components.layouts.dashboard', [
            'heading' => 'Payments',
            'title' => 'Payments',
        ]);
    }
}

