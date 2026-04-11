<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> — Přihlášení</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent: #8b5cf6;
            --accent-glow: rgba(139,92,246,.35);
            --bg: #0f1117;
            --surface: #1a1d27;
            --border: #2a2d3a;
            --text: #e4e4e7;
            --muted: #71717a;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Background glow */
        .login-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }
        .login-bg::before {
            content: '';
            position: absolute;
            top: -40%;
            left: -20%;
            width: 80vw;
            height: 80vw;
            border-radius: 50%;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
            animation: bgPulse 8s ease-in-out infinite;
        }
        .login-bg::after {
            content: '';
            position: absolute;
            bottom: -30%;
            right: -10%;
            width: 60vw;
            height: 60vw;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59,130,246,.15) 0%, transparent 70%);
            animation: bgPulse 10s ease-in-out infinite reverse;
        }
        @keyframes bgPulse {
            0%, 100% { opacity: .4; transform: scale(1); }
            50% { opacity: .7; transform: scale(1.05); }
        }

        /* Card */
        .login-card {
            position: relative;
            z-index: 1;
            width: 400px;
            max-width: 90vw;
            animation: cardIn .6s cubic-bezier(.16,1,.3,1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(30px) scale(.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Logo + branding */
        .login-brand {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-logo {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: var(--accent);
            color: #fff;
            font-size: 30px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 0 40px var(--accent-glow), 0 8px 32px rgba(0,0,0,.4);
            animation: logoGlow 3s ease-in-out infinite;
        }
        @keyframes logoGlow {
            0%, 100% { box-shadow: 0 0 40px var(--accent-glow), 0 8px 32px rgba(0,0,0,.4); }
            50% { box-shadow: 0 0 60px var(--accent-glow), 0 8px 40px rgba(0,0,0,.5); }
        }
        .login-title {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }
        .login-subtitle {
            font-size: 14px;
            color: var(--muted);
        }

        /* Form */
        .login-form {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
        }
        .login-field {
            margin-bottom: 18px;
        }
        .login-field label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            margin-bottom: 8px;
        }
        .login-field input {
            width: 100%;
            padding: 12px 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .login-field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(139,92,246,.15);
        }
        .login-field input::placeholder {
            color: var(--muted);
            opacity: .6;
        }
        .login-btn {
            width: 100%;
            padding: 13px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, transform .1s, box-shadow .2s;
            margin-top: 6px;
        }
        .login-btn:hover {
            background: #9167f5;
            box-shadow: 0 4px 20px var(--accent-glow);
        }
        .login-btn:active {
            transform: scale(.98);
        }
        .login-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }
        .login-error {
            background: rgba(248,113,113,.1);
            border: 1px solid rgba(248,113,113,.2);
            color: #f87171;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
            align-items: center;
            gap: 8px;
        }
        .login-error.visible {
            display: flex;
        }

        /* Spinner in button */
        .login-btn .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .5s linear infinite;
            margin: 0 auto;
        }
        .login-btn.loading .spinner { display: block; }
        .login-btn.loading .btn-text { display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="login-bg"></div>

<div class="login-card">
    <div class="login-brand">
        <div class="login-logo">A</div>
        <div class="login-title"><?= APP_NAME ?></div>
        <div class="login-subtitle">Kadeřnický salon</div>
    </div>

    <form class="login-form" id="login-form" autocomplete="on">
        <div class="login-error" id="login-error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span id="login-error-msg"></span>
        </div>
        <div class="login-field">
            <label for="login-user">Uživatel</label>
            <input type="text" id="login-user" name="username" placeholder="Uživatelské jméno" autocomplete="username" required autofocus>
        </div>
        <div class="login-field">
            <label for="login-pass">Heslo</label>
            <input type="password" id="login-pass" name="password" placeholder="Heslo" autocomplete="current-password" required>
        </div>
        <button type="submit" class="login-btn" id="login-btn">
            <span class="btn-text">Přihlásit se</span>
            <div class="spinner"></div>
        </button>
    </form>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const btn = document.getElementById('login-btn');
    const errBox = document.getElementById('login-error');
    const errMsg = document.getElementById('login-error-msg');

    btn.classList.add('loading');
    btn.disabled = true;
    errBox.classList.remove('visible');

    try {
        const res = await fetch('/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: document.getElementById('login-user').value,
                password: document.getElementById('login-pass').value
            })
        });
        const data = await res.json();

        if (res.ok) {
            // Success — redirect to app
            window.location.href = '/';
        } else {
            errMsg.textContent = data.error || 'Přihlášení se nezdařilo';
            errBox.classList.add('visible');
            document.getElementById('login-pass').value = '';
            document.getElementById('login-pass').focus();
        }
    } catch (e) {
        errMsg.textContent = 'Chyba připojení k serveru';
        errBox.classList.add('visible');
    } finally {
        btn.classList.remove('loading');
        btn.disabled = false;
    }
});
</script>
</body>
</html>
