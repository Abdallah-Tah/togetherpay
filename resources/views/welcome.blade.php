<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <style>
        :root {
            --bg: #0d111a;
            --bg-soft: #111828;
            --bg-panel: #131b2d;
            --text: #e6ecf5;
            --muted: #9fb3c8;
            --accent: #ffb454;
            --accent-strong: #ff8a1f;
            --card: rgba(255, 255, 255, 0.04);
            --border: rgba(255, 255, 255, 0.08);
            --shadow: 0 15px 50px rgba(0, 0, 0, 0.35);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Instrument Sans", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(255, 180, 84, 0.08), transparent 35%),
                        radial-gradient(circle at 80% 10%, rgba(255, 138, 31, 0.07), transparent 40%),
                        linear-gradient(135deg, #0b0f17, #0f1625 55%, #0a0f18);
            color: var(--text);
            min-height: 100vh;
        }

        a { color: inherit; text-decoration: none; }
        header { position: sticky; top: 0; z-index: 10; backdrop-filter: blur(10px); background: rgba(13, 17, 26, 0.7); border-bottom: 1px solid var(--border); }
        nav { max-width: 1100px; margin: 0 auto; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: 700; letter-spacing: 0.02em; }
        .logo-mark { width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(135deg, var(--accent), var(--accent-strong)); display: grid; place-items: center; color: #0d111a; font-weight: 800; box-shadow: 0 8px 25px rgba(255, 138, 31, 0.35); }
        .nav-links { display: flex; gap: 18px; align-items: center; }
        .nav-links a { color: var(--muted); font-weight: 500; padding: 8px 10px; border-radius: 10px; transition: color 0.2s, background 0.2s, transform 0.2s; }
        .nav-links a:hover { color: var(--text); background: rgba(255, 255, 255, 0.06); transform: translateY(-2px); }
        .cta { background: linear-gradient(135deg, var(--accent), var(--accent-strong)); color: #0d111a; padding: 10px 16px; border-radius: 12px; font-weight: 700; box-shadow: 0 10px 30px rgba(255, 180, 84, 0.35); }

        main { max-width: 1100px; margin: 0 auto; padding: 60px 24px 80px; display: flex; flex-direction: column; gap: 80px; }

        .hero { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 32px; align-items: center; }
        .hero-card { background: var(--card); border: 1px solid var(--border); border-radius: 22px; padding: 28px; box-shadow: var(--shadow); position: relative; overflow: hidden; }
        .hero-card::after { content: ""; position: absolute; inset: 0; background: radial-gradient(circle at 30% 20%, rgba(255, 180, 84, 0.14), transparent 45%); pointer-events: none; }
        .eyebrow { display: inline-flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 999px; background: rgba(255, 255, 255, 0.06); color: var(--muted); font-weight: 600; letter-spacing: 0.02em; }
        .eyebrow span { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), var(--accent-strong)); display: inline-block; box-shadow: 0 0 0 6px rgba(255, 180, 84, 0.13); }
        h1 { font-size: clamp(32px, 4vw, 46px); margin: 16px 0 10px; letter-spacing: -0.02em; }
        .lead { color: var(--muted); font-size: 17px; line-height: 1.7; max-width: 540px; }
        .hero-actions { display: flex; gap: 14px; align-items: center; margin-top: 24px; flex-wrap: wrap; }
        .ghost { border: 1px solid var(--border); padding: 10px 16px; border-radius: 12px; color: var(--text); background: rgba(255,255,255,0.03); font-weight: 600; }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-top: 30px; }
        .stat { padding: 16px 18px; border-radius: 14px; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); }
        .stat strong { display: block; font-size: 26px; color: #fff; margin-bottom: 6px; }
        .stat span { color: var(--muted); font-size: 14px; }

        section { display: grid; gap: 24px; }
        .section-header { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; }
        .section-header h2 { margin: 0; font-size: 26px; letter-spacing: -0.01em; }
        .section-header p { margin: 0; color: var(--muted); max-width: 620px; line-height: 1.6; }

        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--shadow); position: relative; overflow: hidden; }
        .card::before { content: ""; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255, 180, 84, 0.06), transparent 50%); opacity: 0.9; pointer-events: none; }
        .card h3 { margin: 0 0 10px; font-size: 18px; }
        .card p { margin: 0; color: var(--muted); line-height: 1.6; }

        .step { display: flex; gap: 16px; align-items: flex-start; }
        .step-number { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--accent), var(--accent-strong)); color: #0d111a; display: grid; place-items: center; font-weight: 800; box-shadow: 0 6px 16px rgba(255, 180, 84, 0.35); }
        .step.purple { background: linear-gradient(145deg, rgba(122, 86, 255, 0.16), rgba(97, 62, 238, 0.12)); border-color: rgba(122, 86, 255, 0.35); }
        .step.purple .step-number { background: linear-gradient(135deg, #a26bff, #7b4dff); color: #f8f5ff; box-shadow: 0 6px 16px rgba(122, 86, 255, 0.45); }

        footer { margin-top: 40px; padding: 24px 0 10px; color: var(--muted); font-size: 14px; border-top: 1px solid var(--border); text-align: center; }

        @media (max-width: 640px) {
            nav { padding: 14px 18px; }
            .nav-links { gap: 12px; }
            main { padding: 40px 18px 70px; }
            .section-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<header>
    <nav>
        <div class="logo">
            <div class="logo-mark">T</div>
            <div>{{ config('app.name', 'TogetherPay') }}</div>
        </div>
        <div class="nav-links">
            <a href="#welcome">Welcome</a>
            <a href="#about">About</a>
            <a href="#how-it-works">How it works</a>
            @if (Route::has('login'))
                <a class="cta" href="{{ route('login') }}">Log in</a>
            @endif
        </div>
    </nav>
</header>

<main>
    <section id="welcome" class="hero">
        <div class="hero-card">
            <div class="eyebrow"><span></span>Modern peer-to-peer payments</div>
            <h1>Send, split, and settle together without friction.</h1>
            <p class="lead">TogetherPay keeps your money movement organized with transparent status updates, smart reminders, and a secure checkout built for both individuals and teams.</p>
            <div class="hero-actions">
                <a class="cta" href="#how-it-works">Get started</a>
                <a class="ghost" href="#about">Learn more</a>
            </div>
            <div class="stats">
                <div class="stat">
                    <strong>2.3m+</strong>
                    <span>Transactions processed</span>
                </div>
                <div class="stat">
                    <strong>4.9★</strong>
                    <span>Customer satisfaction</span>
                </div>
                <div class="stat">
                    <strong>&lt;2 min</strong>
                    <span>Average settlement time</span>
                </div>
            </div>
        </div>
        <div class="hero-card" style="display:grid; place-items:center; text-align:center;">
            <div style="width: 100%; max-width: 460px; aspect-ratio: 4 / 3; border-radius: 18px; background: radial-gradient(circle at 35% 35%, rgba(255, 180, 84, 0.22), transparent 50%), linear-gradient(145deg, #0f1724, #0c121d); border: 1px solid var(--border); position: relative; overflow: hidden;">
                <div style="position:absolute; inset:18px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.08); display:grid; gap:10px; align-content:center; background: rgba(255,255,255,0.02);">
                    <div style="display:flex; justify-content:space-between; padding:12px 14px;">
                        <span style="color: var(--muted);">Balance</span>
                        <span style="font-weight:700;">$12,840.22</span>
                    </div>
                    <div style="height:1px; background: var(--border);"></div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; padding: 0 14px 14px;">
                        <div style="padding:12px; border-radius: 12px; background: rgba(255,255,255,0.04); border:1px solid var(--border); text-align:left;">
                            <div style="color: var(--muted); font-size: 13px;">Upcoming request</div>
                            <div style="font-weight:700; margin-top:4px;">$420.00</div>
                            <div style="color: var(--muted); font-size: 12px; margin-top:6px;">Due Friday</div>
                        </div>
                        <div style="padding:12px; border-radius: 12px; background: rgba(255,180,84,0.08); border:1px solid rgba(255,180,84,0.35); text-align:left;">
                            <div style="color: var(--muted); font-size: 13px;">Split in motion</div>
                            <div style="font-weight:700; margin-top:4px;">4 friends</div>
                            <div style="color: var(--muted); font-size: 12px; margin-top:6px;">3 paid, 1 pending</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about">
        <div class="section-header">
            <h2>About TogetherPay</h2>
            <p>Built for people who move money together. Whether you're sharing rent, reimbursing a teammate, or keeping side projects organized, TogetherPay keeps every transfer transparent and on time.</p>
        </div>
        <div class="card-grid">
            <div class="card">
                <h3>Clarity first</h3>
                <p>Live status for every request, gentle nudges when due dates approach, and a unified view of all the payments you're waiting on.</p>
            </div>
            <div class="card">
                <h3>Secure by default</h3>
                <p>Encrypted payment flows, device verification, and configurable approval rules keep your funds and data protected.</p>
            </div>
            <div class="card">
                <h3>Made for teams</h3>
                <p>Shared wallets, expense labels, and export-ready summaries let groups stay accountable without spreadsheets.</p>
            </div>
        </div>
    </section>

    <section id="how-it-works">
        <div class="section-header">
            <h2>How it works</h2>
            <p>Three simple steps to move money together with confidence.</p>
        </div>
        <div class="card-grid">
            <div class="card step purple">
                <div class="step-number">1</div>
                <div>
                    <h3>Create or join a group</h3>
                    <p>Start a shared space for your trip, club, or project, and invite the people you trust.</p>
                </div>
            </div>
            <div class="card step purple">
                <div class="step-number">2</div>
                <div>
                    <h3>Send and split requests</h3>
                    <p>Send one-time or recurring requests with notes, attachments, and automatic reminders.</p>
                </div>
            </div>
            <div class="card step purple">
                <div class="step-number">3</div>
                <div>
                    <h3>Settle instantly</h3>
                    <p>Track who paid, release payouts fast, and sync receipts wherever you manage money.</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        Crafted for teams that want less busywork and more trust. {{ config('app.name', 'TogetherPay') }} keeps everyone in sync.
    </footer>
</main>
</body>
</html>
