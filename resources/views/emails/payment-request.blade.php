@component('emails.layout', ['title' => 'Payment Request'])
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
@endcomponent
