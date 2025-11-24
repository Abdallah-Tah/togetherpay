@component('emails.layout', ['title' => 'Join ' . $group->name])
    <h1>Welcome to TogetherPay! 🎉</h1>

    <p>{{ $invitedBy->name }} has added you to the group <strong>{{ $group->name }}</strong>.</p>

    <p>TogetherPay makes splitting recurring bills effortless. Here's what you can do:</p>

    <div class="payment-details">
        <div class="row">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#14b8a6" style="width: 20px; height: 20px; margin-right: 10px; flex-shrink: 0; vertical-align: middle;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <span class="label">Receive automatic payment requests</span>
        </div>
        <div class="row">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#14b8a6" style="width: 20px; height: 20px; margin-right: 10px; flex-shrink: 0; vertical-align: middle;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <span class="label">Pay instantly via Venmo or Cash App</span>
        </div>
        <div class="row">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#14b8a6" style="width: 20px; height: 20px; margin-right: 10px; flex-shrink: 0; vertical-align: middle;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <span class="label">Track your payment history</span>
        </div>
        <div class="row">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#14b8a6" style="width: 20px; height: 20px; margin-right: 10px; flex-shrink: 0; vertical-align: middle;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <span class="label">Get reminders before due dates</span>
        </div>
    </div>

    <h2 style="color: #18181b; font-size: 20px; font-weight: 600; margin-top: 30px; margin-bottom: 15px;">Get Started</h2>

    <p style="margin-bottom: 10px;"><strong>Step 1:</strong> Download the TogetherPay app from your app store</p>
    <p style="margin-bottom: 10px;"><strong>Step 2:</strong> Tap the button below to complete your registration</p>
    <p style="margin-bottom: 20px;"><strong>Step 3:</strong> Set up your account and payment preferences</p>

    <div class="button-container">
        <a href="{{ $invitationUrl }}" class="button">Complete Registration</a>
    </div>

    <div class="note note-info">
        <strong>📱 Don't have the TogetherPay app yet?</strong><br>
        Download now to get started:<br>
        <a href="https://apps.apple.com/app/togetherpay" style="color: #1e3a8a;">• iPhone/iPad (App Store)</a><br>
        <a href="https://play.google.com/store/apps/details?id=com.togetherpay.mobile" style="color: #1e3a8a;">• Android (Google Play)</a>
    </div>

    <div class="payment-details" style="margin-top: 25px;">
        <div class="row">
            <span class="label">Your one-time code:</span>
            <span class="value" style="font-family: monospace;">{{ $token }}</span>
        </div>
        <div class="row">
            <span class="label">Expires:</span>
            <span class="value">7 days from now</span>
        </div>
    </div>

    <p style="font-size: 14px; color: #a1a1aa; margin-top: 30px;">Use the one-time code if prompted in the app.</p>
@endcomponent
