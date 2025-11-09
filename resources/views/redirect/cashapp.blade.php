<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opening Cash App...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #00D54B 0%, #00B341 100%);
            color: white;
            text-align: center;
            padding: 20px;
        }
        .container {
            max-width: 400px;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 30px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        h1 {
            font-size: 24px;
            margin: 0 0 10px;
        }
        p {
            font-size: 16px;
            opacity: 0.9;
            margin: 0 0 20px;
        }
        .amount {
            font-size: 48px;
            font-weight: bold;
            margin: 20px 0;
        }
        .fallback {
            margin-top: 30px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
        .fallback a {
            color: white;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h1>Opening Cash App...</h1>
        <div class="amount">${{ number_format($amount, 2) }}</div>
        <p>You will be redirected to Cash App to send payment to <strong>{{ $cashtag }}</strong></p>

        <div class="fallback">
            <p><small>If Cash App doesn't open automatically, tap the button below:</small></p>
            <a href="{{ $cashAppUrl }}" style="display: inline-block; background: white; color: #00D54B; padding: 12px 24px; border-radius: 25px; text-decoration: none; font-weight: bold; margin-top: 10px;">
                Open Cash App
            </a>
        </div>
    </div>

    <script>
        // Redirect to Cash App after 1 second
        setTimeout(function() {
            window.location.href = '{{ $cashAppUrl }}';
        }, 1000);
    </script>
</body>
</html>
