<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'TogetherPay' }}</title>
    <style>
        /* Styles will be inlined by Laravel, but keeping them here for reference/fallback */
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
        }
        .button-container {
            text-align: center;
            margin: 25px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span style="font-size: 24px; font-weight: bold; color: #18181b;">TogetherPay</span>
        </div>
        
        {{ $slot }}
        
        <div class="footer">
            &copy; {{ date('Y') }} TogetherPay. All rights reserved.
        </div>
    </div>
</body>
</html>
