@component('emails.layout', ['title' => 'Join ' . $groupName])
    <h1>Welcome to TogetherPay! 🎉</h1>

    <p>{{ $invitedByName }} has added you to the group <strong>{{ $groupName }}</strong>.</p>

    <p>TogetherPay makes splitting recurring bills effortless. Here's what you can do:</p>

    <div class="payment-details">
        <div class="row">
            <span class="label">✓ Receive automatic payment requests</span>
        </div>
        <div class="row">
            <span class="label">✓ Pay instantly via Venmo or Cash App</span>
        </div>
        <div class="row">
            <span class="label">✓ Track your payment history</span>
        </div>
        <div class="row">
            <span class="label">✓ Get reminders before due dates</span>
        </div>
    </div>

    <h2 style="color: #18181b; font-size: 20px; font-weight: 600; margin-top: 30px; margin-bottom: 15px;">Get Started</h2>

    <p style="margin-bottom: 10px;"><strong>Step 1:</strong> Download the TogetherPay app from your app store</p>
    <p style="margin-bottom: 10px;"><strong>Step 2:</strong> Tap the button below to accept your invitation</p>
    <p style="margin-bottom: 20px;"><strong>Step 3:</strong> Verify your email with a one-time code and create your password</p>

    <div class="button-container">
        <a href="{{ $invitationUrl }}" class="button">Accept Invitation</a>
    </div>

    <div class="note note-info">
        <strong>📱 Don't have the TogetherPay app yet?</strong><br>
        Download now to get started:<br>
        <a href="https://apps.apple.com/app/togetherpay" style="color: #1e3a8a;">• iPhone/iPad (App Store)</a><br>
        <a href="https://play.google.com/store/apps/details?id=com.togetherpay.mobile" style="color: #1e3a8a;">• Android (Google Play)</a>
    </div>

    <div class="payment-details" style="margin-top: 25px;">
        <div class="row">
            <span class="label">Your invitation code:</span>
            <span class="value" style="font-family: monospace;">{{ $token }}</span>
        </div>
        <div class="row">
            <span class="label">Expires:</span>
            <span class="value">{{ $expiresAt->format('F j, Y') }}</span>
        </div>
    </div>

    <p style="font-size: 14px; color: #a1a1aa; margin-top: 30px;">Use the invitation code if prompted in the app.</p>
@endcomponent
