@component('emails.layout', ['title' => 'You were invited to ' . $groupName])
    <h1>You were invited to join {{ $groupName }}</h1>

    <p>{{ $invitedByName }} added you to <strong>{{ $groupName }}</strong> on TogetherPay.</p>

    <p>Use the button below to open your invite and complete signup.</p>

    <div class="button-container">
        <a href="{{ $invitationUrl }}" class="button">Open Invitation</a>
    </div>

    <div class="payment-details">
        <div class="row">
            <span class="label">Invitation code</span>
            <span class="value" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">{{ $token }}</span>
        </div>
        <div class="row">
            <span class="label">Expires</span>
            <span class="value">7 days from now</span>
        </div>
    </div>

    <p style="font-size: 14px; color: #71717a; margin-top: 24px;">
        If you did not expect this invitation, you can safely ignore this email.
    </p>
@endcomponent
