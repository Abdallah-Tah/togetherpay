<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\PaymentRequest;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Return payment stats for a group within a date range.
     */
    public function summary(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);

        $group = $this->resolveGroup($request, $filters['group_id']);
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        $baseQuery = PaymentRequest::whereHas('cycle', function ($query) use ($group, $startDate, $endDate) {
            $query->where('group_id', $group->id)
                ->where(function ($q) use ($startDate, $endDate) {
                    // Include cycles that overlap with the date range
                    $q->where(function ($overlap) use ($startDate, $endDate) {
                        $overlap->whereBetween('period_start', [$startDate->toDateString(), $endDate->toDateString()])
                            ->orWhereBetween('period_end', [$startDate->toDateString(), $endDate->toDateString()])
                            ->orWhere(function ($contains) use ($startDate, $endDate) {
                                $contains->where('period_start', '<=', $startDate->toDateString())
                                    ->where('period_end', '>=', $endDate->toDateString());
                            });
                    });
                });
        });

        $totalRequests = (clone $baseQuery)->count();
        $paidRequests = (clone $baseQuery)->whereIn('status', ['paid', 'owner_confirmed'])->count();
        $pendingRequests = (clone $baseQuery)->whereIn('status', ['pending', 'sent', 'member_marked'])->count();
        $failedRequests = (clone $baseQuery)->whereIn('status', ['failed', 'expired', 'cancelled'])->count();

        $collectedCents = (clone $baseQuery)->whereIn('status', ['paid', 'owner_confirmed'])->sum('amount_cents');
        $pendingCents = (clone $baseQuery)->whereIn('status', ['pending', 'sent', 'member_marked'])->sum('amount_cents');

        $successRate = $totalRequests > 0
            ? round(($paidRequests / $totalRequests) * 100, 1)
            : 0.0;

        return response()->json([
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'currency' => $group->currency ?? 'USD',
            ],
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'stats' => [
                'total_collected' => round($collectedCents / 100, 2),
                'total_pending' => round($pendingCents / 100, 2),
                'paid_requests' => $paidRequests,
                'total_requests' => $totalRequests,
                'success_rate' => $successRate,
                'pending_requests' => $pendingRequests,
                'failed_requests' => $failedRequests,
                'status_breakdown' => [
                    'paid' => $paidRequests,
                    'pending' => $pendingRequests,
                    'failed' => $failedRequests,
                ],
            ],
        ]);
    }

    /**
     * Download CSV export for the current filters.
     */
    public function exportCsv(Request $request, ExportService $exportService)
    {
        $filters = $this->validateFilters($request);

        $group = $this->resolveGroup($request, $filters['group_id']);
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        $path = $exportService->exportGroupCsv($group, $startDate->toDateString(), $endDate->toDateString());

        return response()->download(storage_path('app/'.$path))->deleteFileAfterSend();
    }

    /**
     * Download PDF export for the current filters.
     */
    public function exportPdf(Request $request, ExportService $exportService)
    {
        $filters = $this->validateFilters($request);

        $group = $this->resolveGroup($request, $filters['group_id']);
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        $path = $exportService->exportGroupPdf($group, $startDate->toDateString(), $endDate->toDateString());

        return response()->download(storage_path('app/'.$path))->deleteFileAfterSend();
    }

    protected function validateFilters(Request $request): array
    {
        return $request->validate([
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }

    protected function resolveGroup(Request $request, int $groupId): Group
    {
        $group = Group::where('id', $groupId)
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        $this->authorize('view', $group);

        return $group;
    }

    /**
     * Normalize date range inputs with month defaults.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveDateRange(array $filters): array
    {
        $start = isset($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : now()->startOfMonth();

        $end = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : now()->endOfMonth();

        return [$start, $end];
    }
}
