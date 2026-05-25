<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * Always returns the same message (does not reveal whether the email exists).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $genericStatus = __('If that email address is registered, we sent a 6-digit reset code. Check your inbox (and spam folder) within 15 minutes.');

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return back()->with('status', $genericStatus);
        }

        if (! Schema::hasTable('password_reset_codes')) {
            Log::error('Password reset table missing — run migrations.');

            return back()->with('status', $genericStatus);
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_codes')->updateOrInsert(
            ['email' => $user->email],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        try {
            $user->notify(new PasswordResetCodeNotification($code));
        } catch (\Throwable $e) {
            Log::error('Password reset mail failed: '.$e->getMessage(), ['email' => $user->email]);
        }

        return redirect()->route('password.reset', ['email' => $request->input('email')])
            ->with('status', $genericStatus);
    }
}
