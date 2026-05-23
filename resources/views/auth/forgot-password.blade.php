<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SPUP - Forgot Password</title>
    @include('partials.spup-favicon')
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
                <h1>RESET PASSWORD</h1>
                <p>We will email a 6-digit code to your registered Gmail</p>
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

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Enter your active Gmail" required autofocus autocomplete="email">
                    <p class="form-hint">Use the same email address registered in BEU Scheduling System.</p>
                </div>
                <button type="submit" class="btn-login">Send Reset Code</button>
            </form>

            <div class="auth-links">
                <a href="{{ route('login') }}">← Back to Login</a>
            </div>

            <div class="login-footer">
                &copy; {{ date('Y') }} St. Paul University Philippines. All rights reserved.
            </div>
        </div>
    </div>
</div>
</body>
</html>
