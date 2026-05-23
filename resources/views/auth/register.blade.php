{{-- resources/views/auth/register.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SPUP - Register</title>
    @include('partials.spup-favicon')

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

    <!-- Left Side -->
    <div class="hidden lg:flex lg:w-1/2 items-center justify-center p-12">
        <div class="seal-container">
            <img src="{{ asset('images/spup-seal.png') }}" 
                 class="seal-glow w-80 h-80 object-contain">
        </div>
    </div>

    <!-- Right Side -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
        <div class="w-full max-w-md">

            <!-- Mobile Logo -->
            <div class="lg:hidden flex justify-center mb-8">
                <img src="{{ asset('images/spup-seal.png') }}" 
                     class="w-32 h-32 object-contain seal-glow">
            </div>

            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-yellow-400 mb-2">REGISTER</h1>
                <p class="text-green-200">Create your account</p>
            </div>

            <!-- Errors -->
            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-500/20 border border-red-400/50 rounded-lg">
                    @foreach ($errors->all() as $error)
                        <p class="text-red-200 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('register') }}" class="space-y-6">
                @csrf

                <!-- First Name -->
                <div>
                    <label class="block text-sm font-semibold text-yellow-300 mb-2">First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required placeholder="Enter your first name"
                        class="w-full px-4 py-3 bg-white/10 border border-green-400/30 rounded-lg text-white placeholder-green-300/50 focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/30 transition">
                </div>

                <!-- Last Name -->
                <div>
                    <label class="block text-sm font-semibold text-yellow-300 mb-2">Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="Enter your last name"
                        class="w-full px-4 py-3 bg-white/10 border border-green-400/30 rounded-lg text-white placeholder-green-300/50 focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/30 transition">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-yellow-300 mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email address"
                        class="w-full px-4 py-3 bg-white/10 border border-green-400/30 rounded-lg text-white placeholder-green-300/50 focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/30 transition">
                </div>

                <!-- Role Dropdown (FIXED TEXT VISIBILITY) -->
                <div>
                    <label class="block text-sm font-semibold text-yellow-300 mb-2">Role</label>
                    <select name="role_id" required
                        class="w-full px-4 py-3 bg-white/10 border border-green-400/30 rounded-lg text-white focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/30 transition">

                        <option value="" class="bg-green-900 text-white">Select a role</option>

                        @if(isset($roles) && $roles->count() > 0)
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" class="bg-green-900 text-white"
                                    {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->display_name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Position -->
                <div>
                    <label class="block text-sm font-semibold text-yellow-300 mb-2">Position</label>
                    <input type="text" name="position" value="{{ old('position') }}" placeholder="Enter your position"
                        class="w-full px-4 py-3 bg-white/10 border border-green-400/30 rounded-lg text-white placeholder-green-300/50 focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/30 transition">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-yellow-300 mb-2">Password</label>
                    <input type="password" name="password" required placeholder="Enter your password"
                        class="w-full px-4 py-3 bg-white/10 border border-green-400/30 rounded-lg text-white placeholder-green-300/50 focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/30 transition">
                </div>

                <!-- Confirm Password -->
                <div>
                    
                    <label class="block text-sm font-semibold text-yellow-300 mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" required placeholder="Enter your confirm password"
                        class="w-full px-4 py-3 bg-white/10 border border-green-400/30 rounded-lg text-white placeholder-green-300/50 focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/30 transition">
                </div>

                <!-- Submit -->
                <button type="submit"
                    class="w-full py-3 px-4 bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-400 hover:to-yellow-500 text-green-900 font-bold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    REGISTER
                </button>

                <!-- Login -->
                <div class="text-center text-sm">
                    <span class="text-green-300">Already have an account?</span>
                    <a href="{{ route('login') }}" class="text-yellow-300 hover:text-yellow-100 font-semibold ml-1">
                        Login here
                    </a>
                </div>
            </form>

            <!-- Footer -->
            <div class="mt-8 text-center text-xs text-green-300/60">
                © {{ date('Y') }} St. Paul University Philippines. All rights reserved.
            </div>

        </div>
    </div>
</div>
</body>
</html>