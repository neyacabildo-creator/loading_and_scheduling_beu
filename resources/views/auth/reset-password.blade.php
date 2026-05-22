<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SPUP - Enter Reset Code</title>
    @include('partials.auth-guest-styles')
</head>
<body>
<div class="login-wrap">
    <div class="login-left">
        <div class="seal-container">
            <img src="{{ asset('images/spup-seal.png') }}" class="seal-img" alt="SPUP Seal">
        </div>
    </div>
    <div class="login-right">
        <div class="login-box">
            <div class="mobile-seal">
                <img src="{{ asset('images/spup-seal.png') }}" alt="SPUP Seal">
            </div>
            <div class="login-heading">
                <h1>ENTER CODE</h1>
                <p>Check your Gmail inbox for the 6-digit reset code</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-error">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success">
                    <p>{{ session('status') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email ?? $request->email ?? '') }}" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="code">Reset Code</label>
                    <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6-digit code" required autocomplete="one-time-code">
                    <p class="form-hint">The code expires in 15 minutes.</p>
                </div>
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input id="password" type="password" name="password" placeholder="Enter new password" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm new password" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn-login">Reset Password</button>
            </form>

            <div class="auth-links">
                <a href="{{ route('password.request') }}">Request a new code</a>
                &nbsp;·&nbsp;
                <a href="{{ route('login') }}">Back to Login</a>
            </div>

            <div class="login-footer">
                &copy; {{ date('Y') }} St. Paul University Philippines. All rights reserved.
            </div>
        </div>
    </div>
</div>
</body>
</html>
