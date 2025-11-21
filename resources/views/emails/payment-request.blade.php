<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Request</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f4f5;
            color: #18181b;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            margin-top: 40px;
            margin-bottom: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            height: 40px;
            width: auto;
        }
        h1 {
            color: #14b8a6; /* brand-500 */
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
        }
        p {
            margin-bottom: 20px;
            font-size: 16px;
            color: #52525b; /* zinc-600 */
        }
        .payment-details {
            background-color: #f4f4f5;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .payment-details .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 16px;
        }
        .payment-details .row:last-child {
            margin-bottom: 0;
        }
        .payment-details .label {
            color: #71717a; /* zinc-500 */
            font-weight: 500;
        }
        .payment-details .value {
            color: #18181b;
            font-weight: 600;
        }
        .amount {
            font-size: 20px;
            color: #14b8a6;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #a1a1aa; /* zinc-400 */
        }
        .button {
            display: inline-block;
            background-color: #14b8a6;
            color: #ffffff !important;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
            font-size: 16px;
            text-align: center;
        }
        .button-container {
            text-align: center;
            margin: 25px 0;
        }
        .note {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #78350f;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span style="font-size: 24px; font-weight: bold; color: #18181b;">TogetherPay</span>
        </div>

        <h1>💰 Payment Request</h1>

        <p>Hi {{ $notifiable->name }},</p>

        @if($group)
        <p>Your payment for <strong>{{ $group->name }}</strong> is now due.</p>
        @else
        <p>You have a new payment request.</p>
        @endif

        <div class="payment-details">
            @if($group)
            <div class="row">
                <span class="label">Group:</span>
                <span class="value">{{ $group->name }}</span>
            </div>
            @endif
            <div class="row">
                <span class="label">Amount Due:</span>
                <span class="value amount">${{ $amount }}</span>
            </div>
            <div class="row">
                <span class="label">Due Date:</span>
                <span class="value">{{ $dueDate }}</span>
            </div>
        </div>

        <div class="button-container">
            <a href="{{ $payLink }}" class="button">Pay Now</a>
        </div>

        <div class="note">
            <strong>Next steps:</strong> After you've sent your payment, click "I've Sent the Payment" on the payment page to confirm.
        </div>

        <p style="font-size: 14px; color: #71717a;">
            Questions? Reply to this email and we'll be happy to help.
        </p>

        <div class="footer">
            &copy; {{ date('Y') }} TogetherPay. All rights reserved.
        </div>
    </div>
</body>
</html>
