<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Report - {{ $group->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.5;
        }
        .header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #4f46e5;
        }
        .header h1 {
            font-size: 24px;
            color: #4f46e5;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 14px;
            color: #6b7280;
        }
        .meta-info {
            margin-bottom: 25px;
            padding: 12px;
            background-color: #f9fafb;
            border-radius: 6px;
        }
        .meta-info table {
            width: 100%;
        }
        .meta-info td {
            padding: 4px 0;
        }
        .meta-info .label {
            font-weight: 600;
            color: #4b5563;
            width: 30%;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }
        .stat-card {
            display: table-cell;
            width: 25%;
            padding: 12px;
            text-align: center;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        .stat-card .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
        }
        .stat-card .stat-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .stat-card.success .stat-value {
            color: #059669;
        }
        .stat-card.warning .stat-value {
            color: #d97706;
        }
        .stat-card.info .stat-value {
            color: #2563eb;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table thead {
            background-color: #f3f4f6;
        }
        table.data-table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            color: #374151;
            border-bottom: 2px solid #d1d5db;
        }
        table.data-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.data-table tbody tr:hover {
            background-color: #f9fafb;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-sent {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-failed {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-expired {
            background-color: #f3f4f6;
            color: #374151;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payment Report</h1>
        <div class="subtitle">{{ $group->name }}</div>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td class="label">Report Period:</td>
                <td>
                    @if($startDate && $endDate)
                        {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                    @else
                        All Time
                    @endif
                </td>
                <td class="label">Generated:</td>
                <td>{{ $generatedAt->format('M d, Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Group Owner:</td>
                <td>{{ $group->owner->name }}</td>
                <td class="label">Total Amount:</td>
                <td>${{ number_format($group->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Cadence:</td>
                <td>{{ ucfirst(strtolower(str_replace('_', ' ', $group->cadence))) }}</td>
                <td class="label">Active Members:</td>
                <td>{{ $group->members()->where('status', 'active')->count() }}</td>
            </tr>
        </table>
    </div>

    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-value">${{ number_format($stats['total_collected'], 2) }}</div>
            <div class="stat-label">Total Collected</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-value">${{ number_format($stats['total_pending'], 2) }}</div>
            <div class="stat-label">Total Pending</div>
        </div>
        <div class="stat-card info">
            <div class="stat-value">{{ $stats['paid_count'] }}/{{ $stats['total_count'] }}</div>
            <div class="stat-label">Paid Requests</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $stats['success_rate'] }}%</div>
            <div class="stat-label">Success Rate</div>
        </div>
    </div>

    @if($requests->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cycle Period</th>
                    <th>Member</th>
                    <th style="text-align: right;">Amount</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Sent</th>
                    <th>Paid</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $request)
                    <tr>
                        <td>{{ $request->cycle->period_start->format('M d') }} - {{ $request->cycle->period_end->format('M d, Y') }}</td>
                        <td>{{ $request->member->user->name }}</td>
                        <td style="text-align: right;">${{ number_format($request->amount_cents / 100, 2) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $request->channel)) }}</td>
                        <td>
                            <span class="badge badge-{{ $request->status }}">
                                {{ ucfirst($request->status) }}
                            </span>
                        </td>
                        <td>{{ $request->sent_at?->format('M d, H:i') ?? '-' }}</td>
                        <td>{{ $request->paid_at?->format('M d, H:i') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <p>No payment requests found for the selected period.</p>
        </div>
    @endif

    <div class="footer">
        Generated by TogetherPay on {{ $generatedAt->format('F d, Y \a\t H:i') }} | Page 1 of 1
    </div>
</body>
</html>
