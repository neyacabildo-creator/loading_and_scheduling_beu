{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SPUP - Login</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Figtree', sans-serif; }
        .university-gradient-green {
            background: linear-gradient(135deg, #2d7a50 0%, #1a5336 50%, #0f3d26 100%);
        }
        .seal-glow {
            animation: pulse-glow 3s ease-in-out infinite;
            filter: drop-shadow(0 0 30px rgba(230, 198, 92, 0.4));
        }
        @keyframes pulse-glow {
            0%, 100% { filter: drop-shadow(0 0 30px rgba(230, 198, 92, 0.3)); }
            50% { filter: drop-shadow(0 0 50px rgba(230, 198, 92, 0.6)); }
        }
        .seal-container {
            position: relative;
        }
        .seal-container::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120%;
            height: 120%;
            background: radial-gradient(circle, rgba(230, 198, 92, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            z-index: -1;
        }
    </style>
</head>
<body class="min-h-screen university-gradient-green">
    <div class="min-h-screen flex">
        <!-- Left Side - University Seal Image -->
        <div class="hidden lg:flex lg:w-1/2 items-center justify-center p-12">
            <div class="seal-container">
                <img 
                    src="{{ asset('images/spup-seal.png') }}" 
                    alt="St. Paul University Philippines Seal" 
                    class="seal-glow w-80 h-80 object-contain"
                >
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="lg:hidden flex justify-center mb-8">
                    <img 
                        src="{{ asset('images/spup-seal.png') }}" 
                        alt="St. Paul University Philippines Seal" 
                        class="w-32 h-32 object-contain seal-glow"
                    >
                </div>

                <!-- Login Header -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-yellow-400 mb-2">LOGIN</h1>
                    <p class="text-green-200">Welcome to BEU Scheduling System</p>
                </div>

                <!-- Status Message -->
                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-500/20 border border-green-400/50 rounded-lg">
                        <p class="text-green-200 text-sm">{{ session('status') }}</p>
                    </div>
                @endif

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-500/20 border border-red-400/50 rounded-lg">
                        @foreach ($errors->all() as $error)
                            <p class="text-red-200 text-sm">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-yellow-300 mb-2">
                            Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}"
                            required 
                            autofocus
                            class="w-full px-4 py-3 bg-white/10 border border-green-400/30 rounded-lg text-white placeholder-green-300/50 focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/30 transition"
                            placeholder="Enter your email"
                        >
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-yellow-300 mb-2">
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 bg-white/10 border border-green-400/30 rounded-lg text-white placeholder-green-300/50 focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/30 transition"
                            placeholder="Enter your password"
                        >
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            name="remember"
                            class="w-4 h-4 rounded border-green-400/50 bg-white/10 text-yellow-500 focus:ring-yellow-400/30"
                        >
                        <label for="remember" class="ml-2 text-sm text-green-200">
                            Remember me
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full py-3 px-4 bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-400 hover:to-yellow-500 text-green-900 font-bold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                    >
                        LOGIN
                    </button>

                    <!-- Links -->
                    <div class="flex items-center justify-between pt-4">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-yellow-200 hover:text-yellow-100 text-sm font-semibold transition">
                                Forgot password?
                            </a>
                        @endif
                        
                        <div class="text-sm">
                            <span class="text-green-300">Don't have an account?</span>
                            <a href="{{ route('register') }}" class="text-yellow-300 hover:text-yellow-100 font-semibold ml-1 transition">
                                Register here
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Footer -->
                <div class="mt-8 text-center">
                    <p class="text-green-300/60 text-xs">
                        © {{ date('Y') }} St. Paul University Philippines. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
