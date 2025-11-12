<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $paymentRequest->pr_code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #2d3748;
            line-height: 1.6;
            background-color: #f7fafc;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
        }
        .logo-section h1 {
            color: #3b82f6;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .logo-section p {
            color: #718096;
            font-size: 11px;
        }
        .invoice-meta {
            text-align: right;
        }
        .invoice-meta .label {
            color: #718096;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .invoice-meta .value {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            background-color: #edf2f7;
            padding: 10px 15px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 11px;
            text-transform: uppercase;
            border-left: 4px solid #3b82f6;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .details-row .label {
            color: #718096;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
        }
        .details-row .value {
            color: #2d3748;
            font-weight: 500;
        }
        .amount-section {
            margin-top: 30px;
            text-align: right;
        }
        .amount-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
            gap: 20px;
        }
        .amount-row .label {
            color: #718096;
            font-weight: 600;
            width: 150px;
            text-align: left;
        }
        .amount-row .value {
            width: 100px;
            text-align: right;
        }
        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #3b82f6;
            gap: 20px;
            font-size: 14px;
            font-weight: 700;
        }
        .total-row .label {
            width: 150px;
            text-align: left;
        }
        .total-row .value {
            width: 100px;
            text-align: right;
            color: #3b82f6;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #718096;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #edf2f7;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            color: #2d3748;
            border: 1px solid #cbd5e0;
        }
        td {
            padding: 10px;
            border: 1px solid #e2e8f0;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #c6f6d5;
            color: #22543d;
        }
        .status-pending {
            background-color: #feebc8;
            color: #7c2d12;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <h1>TogetherPay</h1>
                <p>Group Payment Management</p>
            </div>
            <div class="invoice-meta">
                <div class="label">Invoice Number</div>
                <div class="value">{{ $paymentRequest->pr_code }}</div>
                <div class="label" style="margin-top: 10px;">Invoice Date</div>
                <div class="value">{{ $paymentRequest->created_at->format('M d, Y') }}</div>
                <div class="label" style="margin-top: 10px;">Status</div>
                <div class="value">
                    <span class="status-badge {{ $paymentRequest->status === 'paid' ? 'status-paid' : 'status-pending' }}">
                        {{ ucfirst($paymentRequest->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Group Information -->
        <div class="section">
            <div class="section-title">Group Information</div>
            <div class="details-row">
                <div class="label">Group Name</div>
                <div class="value">{{ $group->name }}</div>
            </div>
            <div class="details-row">
                <div class="label">Billing Cycle</div>
                <div class="value">{{ ucfirst($group->cadence) }}</div>
            </div>
            <div class="details-row">
                <div class="label">Group Owner</div>
                <div class="value">{{ $group->owner?->name ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Member Information -->
        <div class="section">
            <div class="section-title">Member Information</div>
            <div class="details-row">
                <div class="label">Member Name</div>
                <div class="value">{{ $member?->user?->name ?? 'N/A' }}</div>
            </div>
            <div class="details-row">
                <div class="label">Member Email</div>
                <div class="value">{{ $member?->user?->email ?? 'N/A' }}</div>
            </div>
            @if($member?->venmo_username)
                <div class="details-row">
                    <div class="label">Venmo Handle</div>
                    <div class="value">@{{ $member->venmo_username }}</div>
                </div>
            @endif
            @if($member?->cash_tag)
                <div class="details-row">
                    <div class="label">Cash App Tag</div>
                    <div class="value">${{ $member->cash_tag }}</div>
                </div>
            @endif
        </div>

        <!-- Amount Details -->
        <div class="section">
            <div class="section-title">Amount Details</div>
            <div class="amount-row">
                <span class="label">Amount Due:</span>
                <span class="value">${{ number_format($paymentRequest->amount_cents / 100, 2) }}</span>
            </div>
            @if($paymentRequest->paid_at)
                <div class="amount-row">
                    <span class="label">Paid On:</span>
                    <span class="value">{{ $paymentRequest->paid_at->format('M d, Y \a\t H:i A') }}</span>
                </div>
            @endif
        </div>

        <!-- Square Invoice Details -->
        @if($invoice)
            <div class="section">
                <div class="section-title">Square Invoice Details</div>
                <div class="details-row">
                    <div class="label">Square Invoice ID</div>
                    <div class="value">{{ $invoice['id'] ?? 'N/A' }}</div>
                </div>
                <div class="details-row">
                    <div class="label">Status</div>
                    <div class="value">{{ ucfirst($invoice['status'] ?? 'unknown') }}</div>
                </div>
                <div class="details-row">
                    <div class="label">Invoice URL</div>
                    <div class="value">{{ $invoice['invoice_url'] ?? 'N/A' }}</div>
                </div>
            </div>
        @endif

        <!-- Notes -->
        <div class="section">
            <div class="section-title">Additional Information</div>
            <div class="details-row">
                <div class="label">Payment Method</div>
                <div class="value">{{ ucfirst(str_replace('_', ' ', $paymentRequest->method_hint ?? 'Unknown')) }}</div>
            </div>
            @if($paymentRequest->note)
                <div class="details-row">
                    <div class="label">Note</div>
                    <div class="value">{{ $paymentRequest->note }}</div>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This invoice was generated on {{ now()->format('F d, Y \a\t H:i A') }}</p>
            <p>TogetherPay - Simplifying Group Payments</p>
        </div>
    </div>
</body>
</html>
