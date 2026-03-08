@component('emails.layout', ['title' => 'Payment Request'])
    <h1>Payment request</h1>

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
            <span class="label">Amount due</span>
            <span class="value amount">${{ $amount }}</span>
        </div>
        <div class="row">
            <span class="label">Due date</span>
            <span class="value">{{ $dueDate }}</span>
        </div>
    </div>

    <div class="button-container">
        <a href="{{ $payLink }}" class="button">Pay now</a>
    </div>

    <div class="note">
        After sending payment, confirm it on the payment page so the group owner can track it.
    </div>

    <p style="font-size: 14px; color: #71717a; margin-top: 24px;">
        If this does not look right, contact your group owner.
    </p>
@endcomponent
