<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\AuthSession;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $existing = User::where('email', $this->input('email'))->first();
        if ($existing && AuthSession::hasActiveSessionElsewhere($existing)) {
            throw ValidationException::withMessages([
                'email' => 'This account is already signed in on another browser or device. Log out there first, or wait for that session to expire.',
            ]);
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 300);
            RateLimiter::hit('login-ip|'.$this->ip(), 300);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear('login-ip|'.$this->ip());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $emailKey = $this->throttleKey();
        $ipKey = 'login-ip|'.$this->ip();

        if (RateLimiter::tooManyAttempts($emailKey, 5)) {
            $this->throwThrottleResponse($emailKey);
        }

        if (RateLimiter::tooManyAttempts($ipKey, 15)) {
            $this->throwThrottleResponse($ipKey);
        }
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwThrottleResponse(string $key): void
    {
        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => max(1, (int) ceil($seconds / 60)),
            ]),
        ]);
    }

    /**
     * Per-email + IP throttle key (max 5 failures before lockout).
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
