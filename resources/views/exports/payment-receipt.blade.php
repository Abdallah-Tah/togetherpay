<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $paymentRequest->pr_code }}</title>
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
            max-width: 600px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #22c55e;
        }
        .header h1 {
            color: #22c55e;
            font-size: 28px;
            margin-bottom: 5px;
        }
        .header .status {
            display: inline-block;
            background-color: #dcfce7;
            color: #166534;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .receipt-number {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f0fdf4;
            border-radius: 8px;
        }
        .receipt-number .label {
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
        }
        .receipt-number .value {
            color: #166534;
            font-size: 18px;
            font-weight: 700;
            margin-top: 5px;
            font-family: 'Courier New', monospace;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #4b5563;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row .label {
            color: #6b7280;
            font-weight: 500;
        }
        .detail-row .value {
            color: #1f2937;
            font-weight: 600;
        }
        .amount-section {
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-radius: 8px;
            border: 2px solid #22c55e;
        }
        .amount-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .amount-row .label {
            color: #4b5563;
            font-weight: 600;
        }
        .amount-row .value {
            color: #1f2937;
            font-weight: 600;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 2px solid #22c55e;
            font-size: 16px;
            font-weight: 700;
        }
        .total-row .label {
            color: #166534;
        }
        .total-row .value {
            color: #22c55e;
        }
        .payment-method {
            margin-top: 15px;
            padding: 12px;
            background-color: #f3f4f6;
            border-radius: 6px;
        }
        .payment-method .label {
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .payment-method .value {
            color: #1f2937;
            font-weight: 600;
            margin-top: 4px;
        }
        .verification {
            margin-top: 30px;
            padding: 20px;
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            text-align: center;
        }
        .verification .checkmark {
            font-size: 32px;
            color: #22c55e;
            margin-bottom: 8px;
        }
        .verification p {
            color: #92400e;
            font-size: 11px;
            margin: 4px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
        .divider {
            border: none;
            border-top: 2px dashed #d1d5db;
            margin: 20px 0;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background-color: #f9fafb;
            border-radius: 8px;
        }
        .qr-code p {
            color: #6b7280;
            font-size: 9px;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>✓ PAYMENT RECEIVED</h1>
            <div class="status">Paid Receipt</div>
        </div>

        <!-- Receipt Number -->
        <div class="receipt-number">
            <div class="label">Receipt Number</div>
            <div class="value">{{ $paymentRequest->pr_code }}</div>
        </div>

        <!-- Group and Member Info -->
        <div class="section">
            <div class="section-title">Transaction Details</div>
            <div class="detail-row">
                <span class="label">Group Name</span>
                <span class="value">{{ $group->name }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Member</span>
                <span class="value">{{ $member?->user?->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Paid On</span>
                <span class="value">{{ $paymentRequest->paid_at->format('M d, Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Time</span>
                <span class="value">{{ $paymentRequest->paid_at->format('g:i A') }}</span>
            </div>
        </div>

        <hr class="divider">

        <!-- Amount -->
        <div class="amount-section">
            <div class="amount-row">
                <span class="label">Amount Paid</span>
                <span class="value">${{ number_format($paymentRequest->amount_cents / 100, 2) }}</span>
            </div>
            <div class="total-row">
                <span class="label">Total</span>
                <span class="value">${{ number_format($paymentRequest->amount_cents / 100, 2) }}</span>
            </div>

            <div class="payment-method">
                <div class="label">Payment Method</div>
                <div class="value">{{ ucfirst(str_replace('_', ' ', $paymentRequest->method_hint ?? 'Unknown')) }}</div>
            </div>
        </div>

        <!-- Verification -->
        <div class="verification">
            <div class="checkmark">✓</div>
            <p><strong>Payment Confirmed</strong></p>
            <p>This payment has been successfully processed and recorded in the TogetherPay system.</p>
        </div>

        <!-- Additional Info -->
        <div class="section" style="margin-top: 30px;">
            <div class="section-title">Receipt Information</div>
            <div class="detail-row">
                <span class="label">Billing Cycle</span>
                <span class="value">{{ ucfirst($group->cadence) }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Group Owner</span>
                <span class="value">{{ $group->owner?->name ?? 'N/A' }}</span>
            </div>
            @if($paymentRequest->note)
                <div class="detail-row">
                    <span class="label">Note</span>
                    <span class="value">{{ $paymentRequest->note }}</span>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>TogetherPay Receipt</strong></p>
            <p>Generated on {{ now()->format('F d, Y \a\t H:i A') }}</p>
            <p>This is an official receipt for your payment. Please keep this for your records.</p>
            <p>For support, visit togetherpay.app or contact support@togetherpay.app</p>
        </div>
    </div>
</body>
</html>
