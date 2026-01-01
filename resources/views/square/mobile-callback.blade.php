<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Square Connection</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            color: #111827;
            text-align: center;
        }
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 12px 45px rgba(15, 23, 42, 0.15);
            padding: 32px 24px;
            max-width: 420px;
            width: 100%;
        }
        .card h1 {
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        .card p {
            margin: 0;
            color: #4b5563;
            line-height: 1.5;
        }
        .success {
            color: #059669;
        }
        .error {
            color: #dc2626;
        }
        .subtitle {
            font-size: 0.9rem;
            margin-top: 16px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="card">
        @if (!empty($success))
            <h1 class="success">Square Connected 🎉</h1>
            <p>Your Square account is now linked. You can close this tab and return to the TogetherPay app.</p>
        @else
            <h1 class="error">Connection Failed</h1>
            <p>{{ $message ?? 'We could not finish connecting your Square account. Please try again.' }}</p>
        @endif
        <p class="subtitle">If this page stays open, just switch back to TogetherPay.</p>
        <p class="subtitle">If the app doesn't open automatically, tap below:</p>
        <p class="subtitle">
            <a href="{{ !empty($success) ? 'togetherpay://square/success' : 'togetherpay://square/error' }}" style="color:#059669;text-decoration:none;font-weight:600;">
                Open TogetherPay
            </a>
        </p>
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const url = @json(!empty($success) ? 'togetherpay://square/success' : 'togetherpay://square/error');
            setTimeout(() => {
                window.location.href = url;
            }, 500);
        });
    </script>
</body>
</html>
