<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
        min-height: 100vh;
        background: linear-gradient(135deg, #2d7a50 0%, #1a5336 55%, #0f3d26 100%);
        display: flex;
        align-items: stretch;
    }

    .login-wrap { display: flex; width: 100%; min-height: 100vh; }

    .login-left {
        display: none;
        flex: 1;
        align-items: center;
        justify-content: center;
        padding: 3rem;
    }

    .seal-container { position: relative; display: flex; align-items: center; justify-content: center; }
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

    .login-right {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2.5rem 1.5rem;
    }

    .login-box { width: 100%; max-width: 420px; }

    .mobile-seal { display: flex; justify-content: center; margin-bottom: 2rem; }
    .mobile-seal img {
        width: 110px;
        height: 110px;
        object-fit: contain;
        animation: sealGlow 3s ease-in-out infinite;
        filter: drop-shadow(0 0 18px rgba(230,198,92,.4));
    }

    .login-heading { text-align: center; margin-bottom: 2rem; }
    .login-heading h1 {
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: .08em;
        color: #e6c65c;
        margin-bottom: .4rem;
    }
    .login-heading p { font-size: .9rem; color: rgba(187,230,210,.85); }

    .alert {
        padding: .85rem 1rem;
        border-radius: .5rem;
        margin-bottom: 1.25rem;
        font-size: .85rem;
    }
    .alert-error { background: rgba(239,68,68,.18); border: 1px solid rgba(239,68,68,.4); color: #fca5a5; }
    .alert-success { background: rgba(34,197,94,.15); border: 1px solid rgba(34,197,94,.35); color: #86efac; }

    .form-group { margin-bottom: 1.25rem; }
    .form-group label {
        display: block;
        font-size: .82rem;
        font-weight: 700;
        color: #fde68a;
        margin-bottom: .5rem;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .form-group input[type="email"],
    .form-group input[type="password"],
    .form-group input[type="text"] {
        width: 100%;
        padding: .8rem 1rem;
        background: rgba(255,255,255,.10);
        border: 1px solid rgba(134,239,172,.30);
        border-radius: .5rem;
        color: #fff;
        font-size: .95rem;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .form-group input::placeholder { color: rgba(187,230,210,.5); }
    .form-group input:focus {
        border-color: #e6c65c;
        box-shadow: 0 0 0 3px rgba(230,198,92,.20);
    }

    .form-hint { font-size: .78rem; color: rgba(187,230,210,.75); margin-top: .35rem; line-height: 1.45; }

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
        text-transform: uppercase;
    }

    .btn-login:hover {
        opacity: .93;
        transform: translateY(-1px);
        box-shadow: 0 6px 24px rgba(230,198,92,.50);
    }

    .auth-links {
        margin-top: 1.25rem;
        text-align: center;
        font-size: .84rem;
    }
    .auth-links a { color: #fde68a; text-decoration: none; }
    .auth-links a:hover { text-decoration: underline; }

    .login-footer {
        margin-top: 2rem;
        text-align: center;
        font-size: .75rem;
        color: rgba(134,239,172,.5);
    }

    @media (min-width: 1024px) {
        .login-left  { display: flex; }
        .login-right { width: 50%; flex: none; }
        .mobile-seal { display: none; }
    }
</style>
