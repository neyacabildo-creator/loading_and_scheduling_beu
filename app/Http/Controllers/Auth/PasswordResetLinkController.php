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
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('We could not find an account with that email address.')]);
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
            if (! Schema::hasTable('password_reset_codes')) {
                throw new \RuntimeException('Password reset is not configured. Run database migrations.');
            }

            $user->notify(new PasswordResetCodeNotification($code));
        } catch (\Throwable $e) {
            Log::error('Password reset mail failed: ' . $e->getMessage(), ['email' => $user->email]);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('Unable to send the reset code. Verify Gmail SMTP in .env (MAIL_MAILER=smtp) and try again.')]);
        }

        return redirect()->route('password.reset', ['email' => $user->email])
            ->with('status', __('We sent a 6-digit reset code to :email. Enter it below within 15 minutes.', ['email' => $user->email]));
    }
}
