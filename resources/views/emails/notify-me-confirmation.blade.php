<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're on the list!</title>
    <style>
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
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <!-- Ideally use a hosted image URL here for email clients -->
            <span style="font-size: 24px; font-weight: bold; color: #18181b;">TogetherPay</span>
        </div>
        
        <h1>You're on the list! 🚀</h1>
        
        <p>Thanks for signing up! We're thrilled you're interested in TogetherPay.</p>
        
        <p>We're working hard to put the finishing touches on our mobile app. You'll be the first to know as soon as it's available for download on iOS and Android.</p>
        
        <p>In the meantime, get your group ready to split bills without the awkwardness.</p>
        
        <div style="text-align: center;">
            <a href="{{ route('welcome') }}" class="button">Visit Website</a>
        </div>
        
        <div class="footer">
            &copy; {{ date('Y') }} TogetherPay. All rights reserved.
        </div>
    </div>
</body>
</html>
