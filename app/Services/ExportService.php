<?php

namespace App\Services;

use App\Models\Export;
use App\Models\Group;
use App\Models\PaymentRequest;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportService
{
    /**
     * Export group payment data to CSV
     */
    public function exportGroupCsv(Group $group, ?string $startDate = null, ?string $endDate = null): string
    {
        $query = PaymentRequest::whereHas('cycle', function ($q) use ($group) {
            $q->where('group_id', $group->id);
        })->with(['member.user', 'cycle']);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        $filename = 'exports/group_'.$group->id.'_'.now()->format('Y-m-d_His').'.csv';
        $fullPath = storage_path('app/'.$filename);

        // Ensure directory exists
        if (! file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $handle = fopen($fullPath, 'w');

        // CSV Headers
        fputcsv($handle, [
            'Cycle Period',
            'Member Name',
            'Member Email',
            'Amount',
            'Payment Channel',
            'Status',
            'Sent At',
            'Paid At',
            'Due Date',
        ]);

        // CSV Data
        foreach ($requests as $request) {
            fputcsv($handle, [
                $request->cycle->period_start->format('Y-m-d').' to '.$request->cycle->period_end->format('Y-m-d'),
                $request->member->user->name,
                $request->member->user->email,
                '$'.number_format($request->amount_cents / 100, 2),
                $request->channel,
                ucfirst($request->status),
                $request->sent_at?->format('Y-m-d H:i:s') ?? '-',
                $request->paid_at?->format('Y-m-d H:i:s') ?? '-',
                $request->cycle->due_at->format('Y-m-d'),
            ]);
        }

        fclose($handle);

        // Log export
        Export::create([
            'group_id' => $group->id,
            'user_id' => auth()->id(),
            'export_type' => 'group_payments',
            'format' => 'csv',
            'file_path' => $filename,
            'params' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);

        return $filename;
    }

    /**
     * Export group payment data to PDF
     */
    public function exportGroupPdf(Group $group, ?string $startDate = null, ?string $endDate = null): string
    {
        $query = PaymentRequest::whereHas('cycle', function ($q) use ($group) {
            $q->where('group_id', $group->id);
        })->with(['member.user', 'cycle']);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        // Calculate stats
        $stats = [
            'total_collected' => $requests->whereIn('status', ['paid', 'owner_confirmed'])->sum(fn ($r) => $r->amount_cents / 100),
            'total_pending' => $requests->whereIn('status', ['pending', 'sent', 'member_marked'])->sum(fn ($r) => $r->amount_cents / 100),
            'paid_count' => $requests->whereIn('status', ['paid', 'owner_confirmed'])->count(),
            'pending_count' => $requests->whereIn('status', ['pending', 'sent', 'member_marked'])->count(),
            'failed_count' => $requests->whereIn('status', ['failed', 'expired'])->count(),
            'total_count' => $requests->count(),
            'success_rate' => $requests->count() > 0
                ? round(($requests->whereIn('status', ['paid', 'owner_confirmed'])->count() / $requests->count()) * 100, 1)
                : 0,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('exports.group-payment-report', [
            'group' => $group,
            'requests' => $requests,
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now(),
        ]);

        $filename = 'exports/group_'.$group->id.'_'.now()->format('Y-m-d_His').'.pdf';
        $fullPath = storage_path('app/'.$filename);

        // Ensure directory exists
        if (! file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $pdf->save($fullPath);

        // Log export
        Export::create([
            'group_id' => $group->id,
            'user_id' => auth()->id(),
            'export_type' => 'group_payments',
            'format' => 'pdf',
            'file_path' => $filename,
            'params' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);

        return $filename;
    }
}
