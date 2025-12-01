@component('emails.layout', ['title' => 'Join ' . $groupName])
    <h1>Welcome to TogetherPay! 🎉</h1>

    <p>{{ $invitedByName }} has added you to the group <strong>{{ $groupName }}</strong>.</p>

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
        <div style="margin-top: 15px; text-align: center;">
            <!-- iOS Button -->
            <a href="https://apps.apple.com/app/togetherpay" style="display: inline-block; background-color: #18181b; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; margin: 5px; font-weight: 600; font-size: 14px; vertical-align: middle;">
                <svg width="24" height="24" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 8px;">
                    <path fill="#2196f3" d="M44,24c0,11.044-8.956,20-20,20S4,35.044,4,24S12.956,4,24,4S44,12.956,44,24z"></path>
                    <path fill="#fff" d="M31.684 23.867l-2.48 1.441L22.43 13.652c-.27-.465-.113-1.063.355-1.336l.793-.461c.465-.27 1.063-.113 1.332.355L31.684 23.867zM29.48 25.789l2.48-1.441 1.438 2.469-2.48 1.441L29.48 25.789zM13.84 28.152l5.988-10.246 2.555 1.492-5.988 10.246L13.84 28.152zM12.285 33.375l1.234-4.676 2.555 1.496-3.469 3.367c-.059.063-.156.074-.23.031C12.297 33.547 12.262 33.461 12.285 33.375M20.035 17.547l.863-1.469c.27-.469.871-.625 1.336-.352l.867.508c.465.273.621.871.348 1.336l-.859 1.473L20.035 17.547zM33.023 27.578c-.402.23-1.004.586-1.359.797-.664.395-.152 1.559 0 1.809.859 1.441 1.746 1.238 2.414 2.258.367.559.266.805.379.992.047.066.191.133.246.055 1.031-1.426.73-3.879-.02-4.973C34.336 28.004 33.703 27.191 33.023 27.578M36.637 25.41h-3.563l-1.555-2.855h5.117V25.41zM28.617 25.41h-9.293l1.586-2.855h6.121L28.617 25.41zM14.945 25.41h-3.617v-2.855h5.309L14.945 25.41z"></path>
                </svg>
                <span style="vertical-align: middle;">Download on iOS</span>
            </a>
            <!-- Android Button -->
            <a href="https://play.google.com/store/apps/details?id=com.togetherpay.mobile" style="display: inline-block; background-color: #18181b; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; margin: 5px; font-weight: 600; font-size: 14px; vertical-align: middle;">
                <svg width="24" height="24" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 8px;">
                    <path fill="#4db6ac" d="M7.705,4.043C7.292,4.15,7,4.507,7,5.121c0,1.802,0,18.795,0,18.795S7,42.28,7,43.091c0,0.446,0.197,0.745,0.5,0.856l20.181-20.064L7.705,4.043z"></path>
                    <path fill="#dce775" d="M33.237,18.36l-8.307-4.796c0,0-15.245-8.803-16.141-9.32C8.401,4.02,8.019,3.961,7.705,4.043l19.977,19.84L33.237,18.36z"></path>
                    <path fill="#d32f2f" d="M8.417,43.802c0.532-0.308,15.284-8.825,24.865-14.357l-5.601-5.562L7.5,43.947C7.748,44.038,8.066,44.004,8.417,43.802z"></path>
                    <path fill="#fbc02d" d="M41.398,23.071c-0.796-0.429-8.1-4.676-8.1-4.676l-0.061-0.035l-5.556,5.523l5.601,5.562c4.432-2.559,7.761-4.48,8.059-4.653C42.285,24.248,42.194,23.5,41.398,23.071z"></path>
                </svg>
                <span style="vertical-align: middle;">Get it on Android</span>
            </a>
        </div>
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
