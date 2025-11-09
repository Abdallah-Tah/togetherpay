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

        <!-- Debug info (visible in production for now) -->
        <div style="margin-top: 10px; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 4px; font-size: 11px;">
            <strong>DEBUG:</strong><br>
            Note value: "{{ $note }}"<br>
            Note empty?: {{ empty($note) ? 'YES' : 'NO' }}<br>
            Note isset?: {{ isset($note) ? 'YES' : 'NO' }}<br>
            Note length: {{ strlen($note) }}
        </div>

        @if(!empty($note))
            <p style="margin-top: 15px; padding: 10px; background: rgba(255, 255, 255, 0.15); border-radius: 8px;">
                <small><strong>For:</strong> {{ $note }}</small>
            </p>
        @endif

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
