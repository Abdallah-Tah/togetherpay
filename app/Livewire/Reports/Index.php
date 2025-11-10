<?php

namespace App\Livewire\Reports;

use App\Models\Group;
use App\Models\PaymentRequest;
use App\Services\ExportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Reports')]
class Index extends Component
{
    use AuthorizesRequests;

    public ?int $selectedGroup = null;

    public string $startDate;

    public string $endDate;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    #[Computed]
    public function groups(): Collection
    {
        return auth()->user()->ownedGroups()->get();
    }

    #[Computed]
    public function stats(): ?array
    {
        if (! $this->selectedGroup) {
            return null;
        }

        $group = Group::find($this->selectedGroup);

        if (! $group) {
            return null;
        }

        $baseQuery = PaymentRequest::whereHas('cycle', function ($q) use ($group) {
            $q->where('group_id', $group->id)
                ->where(function ($query) {
                    // Include cycles that overlap with the date range
                    $query->where(function ($overlap) {
                        $overlap->whereBetween('period_start', [$this->startDate, $this->endDate])
                            ->orWhereBetween('period_end', [$this->startDate, $this->endDate])
                            ->orWhere(function ($contains) {
                                $contains->where('period_start', '<=', $this->startDate)
                                    ->where('period_end', '>=', $this->endDate);
                            });
                    });
                });
        });

        $collectedCents = (clone $baseQuery)->whereIn('status', ['paid', 'owner_confirmed'])->sum('amount_cents');
        $pendingCents = (clone $baseQuery)->whereIn('status', ['pending', 'sent', 'member_marked'])->sum('amount_cents');
        $totalCents = $collectedCents + $pendingCents;

        return [
            'total_collected' => $collectedCents / 100,
            'total_pending' => $pendingCents / 100,
            'paid_requests' => (clone $baseQuery)->whereIn('status', ['paid', 'owner_confirmed'])->count(),
            'total_requests' => (clone $baseQuery)->count(),
            'success_rate' => $totalCents > 0 ? round(($collectedCents / $totalCents) * 100, 1) : 0,
        ];
    }

    public function exportCsv(ExportService $exportService)
    {
        $group = $this->resolveSelectedGroup();

        if (! $group) {
            return null;
        }

        $path = $exportService->exportGroupCsv($group, $this->startDate, $this->endDate);

        return response()->download(storage_path('app/'.$path))->deleteFileAfterSend();
    }

    public function exportPdf(ExportService $exportService)
    {
        $group = $this->resolveSelectedGroup();

        if (! $group) {
            return null;
        }

        $path = $exportService->exportGroupPdf($group, $this->startDate, $this->endDate);

        return response()->download(storage_path('app/'.$path))->deleteFileAfterSend();
    }

    protected function resolveSelectedGroup(): ?Group
    {
        if (! $this->selectedGroup) {
            $this->addError('selectedGroup', 'Please select a group first');

            return null;
        }

        $group = Group::find($this->selectedGroup);

        if (! $group || $group->owner_id !== auth()->id()) {
            $this->addError('selectedGroup', 'You do not have access to this group');

            return null;
        }

        $this->authorize('view', $group);

        return $group;
    }

    public function render()
    {
        return view('livewire.reports.index', [
            'groups' => $this->groups,
            'stats' => $this->stats,
            'title' => 'Reports',
        ]);
    }
}
