{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>SPUP - Login</title>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #2d7a50 0%, #1a5336 55%, #0f3d26 100%);
            display: flex;
            align-items: stretch;
        }

        /* ── Layout ── */
        .login-wrap {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left panel — seal */
        .login-left {
            display: none; /* hidden on mobile */
            flex: 1;
            align-items: center;
            justify-content: center;
            padding: 3rem;
        }

        .seal-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .seal-container::before {
            content: '';
            position: absolute;
            width: 130%;
            height: 130%;
            background: radial-gradient(circle, rgba(230,198,92,.18) 0%, transparent 70%);
            border-radius: 50%;
        }

        .seal-img {
            width: 320px;
            height: 320px;
            object-fit: contain;
            position: relative;
            animation: sealGlow 3s ease-in-out infinite;
            filter: drop-shadow(0 0 28px rgba(230,198,92,.35));
        }

        @keyframes sealGlow {
            0%, 100% { filter: drop-shadow(0 0 24px rgba(230,198,92,.30)); }
            50%       { filter: drop-shadow(0 0 52px rgba(230,198,92,.60)); }
        }

        /* Right panel — form */
        .login-right {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
        }

        /* Mobile-only seal */
        .mobile-seal {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .mobile-seal img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            animation: sealGlow 3s ease-in-out infinite;
            filter: drop-shadow(0 0 18px rgba(230,198,92,.4));
        }

        /* Heading */
        .login-heading {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-heading h1 {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: .08em;
            color: #e6c65c;
            margin-bottom: .4rem;
        }

        .login-heading p {
            font-size: .9rem;
            color: rgba(187,230,210,.85);
        }

        /* Alerts */
        .alert {
            padding: .85rem 1rem;
            border-radius: .5rem;
            margin-bottom: 1.25rem;
            font-size: .85rem;
        }

        .alert-error {
            background: rgba(239,68,68,.18);
            border: 1px solid rgba(239,68,68,.4);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(34,197,94,.15);
            border: 1px solid rgba(34,197,94,.35);
            color: #86efac;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: .82rem;
            font-weight: 700;
            color: #fde68a;
            margin-bottom: .5rem;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .form-group input[type="email"] {
            width: 100%;
            padding: .8rem 1rem;
            background: rgba(255,255,255,.10);
            border: 1px solid rgba(134,239,172,.30);
            border-radius: .5rem;
            color: #fff;
            font-size: .95rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            box-sizing: border-box;
        }

        .password-wrap {
            position: relative;
            width: 100%;
            display: block;
        }

        .password-wrap input {
            width: 100%;
            display: block;
            padding: .8rem 2.75rem .8rem 1rem;
            background: rgba(255,255,255,.10);
            border: 1px solid rgba(134,239,172,.30);
            border-radius: .5rem;
            color: #fff;
            font-size: .95rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            box-sizing: border-box;
        }

        .form-group input::placeholder,
        .password-wrap input::placeholder {
            color: rgba(187,230,210,.5);
        }

        .form-group input:focus,
        .password-wrap input:focus {
            border-color: #e6c65c;
            box-shadow: 0 0 0 3px rgba(230,198,92,.20);
        }

        .password-toggle {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            color: rgba(187,230,210,.9);
            padding: 0.35rem;
            line-height: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }
        .password-toggle:hover { color: #e6c65c; }
        .password-toggle svg { width: 20px; height: 20px; pointer-events: none; }

        /* Remember */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: 1.5rem;
        }

        .remember-left {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .forgot-link {
            color: #fde68a;
            font-size: .84rem;
            text-decoration: none;
            white-space: nowrap;
        }

        .remember-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #e6c65c;
            cursor: pointer;
        }

        .remember-row span {
            font-size: .85rem;
            color: rgba(187,230,210,.85);
        }

        /* Submit button */
        .btn-login {
            width: 100%;
            padding: .85rem 1rem;
            background: linear-gradient(135deg, #d4a017, #e6c65c);
            color: #1a3a1a;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: .06em;
            border: none;
            border-radius: .5rem;
            cursor: pointer;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 18px rgba(230,198,92,.35);
        }

        .btn-login:hover {
            opacity: .93;
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(230,198,92,.50);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Footer */
        .login-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: .75rem;
            color: rgba(134,239,172,.5);
        }

        /* ── Desktop breakpoint ── */
        @media (min-width: 1024px) {
            .login-left  { display: flex; }
            .login-right { width: 50%; flex: none; }
            .mobile-seal { display: none; }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .login-right { padding: 3rem 4rem; }
        }
    </style>
</head>

<body>
<div class="login-wrap">

    <!-- Left — University Seal -->
    <div class="login-left">
        <div class="seal-container">
            <img src="{{ asset('images/spup-seal.png') }}" class="seal-img" alt="SPUP Seal">
        </div>
    </div>

    <!-- Right — Login Form -->
    <div class="login-right">
        <div class="login-box">

            <!-- Mobile seal -->
            <div class="mobile-seal">
                <img src="{{ asset('images/spup-seal.png') }}" alt="SPUP Seal">
            </div>

            <!-- Heading -->
            <div class="login-heading">
                <h1>LOGIN</h1>
                <p>Welcome to BEU Scheduling System</p>
            </div>

            <!-- Validation errors -->
            @if ($errors->any())
                <div class="alert alert-error">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Session status -->
            @if (session('status'))
                <div class="alert alert-success">
                    <p>{{ session('status') }}</p>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login') }}" id="login-form" autocomplete="off">
                @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value=""
                        placeholder="Enter your email"
                        required
                        autocomplete="off"
                        autocapitalize="off"
                        autocorrect="off"
                        spellcheck="false"
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" id="password-toggle" aria-label="Show password" title="Show password">
                            <svg id="eye-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg id="eye-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858 3.029a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M3 3l18 18"/></svg>
                        </button>
                    </div>
                </div>

                <div class="remember-row">
                    <div class="remember-left">
                        <input type="checkbox" id="remember" name="remember_credentials" value="1">
                        <span><label for="remember" style="cursor:pointer;">Remember me</label></span>
                    </div>
                    <a href="{{ route('password.request') }}" class="forgot-link">
                        Forgot your password?
                    </a>
                </div>

                <button type="submit" class="btn-login">LOGIN</button>

                {{-- Accounts are issued by the institution — no self-registration. --}}
            </form>

            <div class="login-footer">
                &copy; {{ date('Y') }} St. Paul University Philippines. All rights reserved.
            </div>

        </div>
    </div>

</div>
<script>
    const storageKey = 'spup-login-credentials';

    function loadSavedCredentials() {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
            if (!saved) return;

            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            const rememberField = document.getElementById('remember');

            if (saved.email && emailField) emailField.value = saved.email;
            if (saved.password && passwordField) passwordField.value = saved.password;
            if (rememberField) rememberField.checked = Boolean(saved.email || saved.password);
        } catch (e) {}
    }

    function syncSavedCredentials() {
        try {
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            const rememberField = document.getElementById('remember');
            if (!emailField || !passwordField || !rememberField) return;

            if (rememberField.checked) {
                localStorage.setItem(storageKey, JSON.stringify({
                    email: emailField.value,
                    password: passwordField.value,
                }));
            } else {
                localStorage.removeItem(storageKey);
            }
        } catch (e) {}
    }

    loadSavedCredentials();
    document.getElementById('login-form')?.addEventListener('submit', syncSavedCredentials);
    document.getElementById('remember')?.addEventListener('change', syncSavedCredentials);

    (function initPasswordToggle() {
        const input = document.getElementById('password');
        const btn = document.getElementById('password-toggle');
        const eyeShow = document.getElementById('eye-show');
        const eyeHide = document.getElementById('eye-hide');
        if (!input || !btn) return;
        btn.addEventListener('click', function() {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            if (eyeShow) eyeShow.style.display = show ? 'none' : 'block';
            if (eyeHide) eyeHide.style.display = show ? 'block' : 'none';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.title = show ? 'Hide password' : 'Show password';
        });
    })();

    // Prevent 419 PAGE EXPIRED errors by keeping the CSRF token fresh.
    function refreshCsrfToken() {
        fetch('/csrf-refresh')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.querySelectorAll('input[name="_token"]').forEach(function(el) {
                    el.value = data.token;
                });
                var meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.content = data.token;
            })
            .catch(function() {});
    }

    // Refresh token when tab becomes visible again (user switched tabs and came back)
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            refreshCsrfToken();
        }
    });

    // Refresh token if page was restored from browser back/forward cache
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            refreshCsrfToken();
        }
    });

    // Refresh token every 30 minutes in case the page is left open
    setInterval(refreshCsrfToken, 30 * 60 * 1000);
</script>
</body>
</html>