<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PasswordResetDeliverySupport
{
    /**
     * Resolve user by email or phone (digits-only match for phone).
     */
    public static function findUserByIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $identifier)->first();
        }

        if (! Schema::hasColumn('users', 'phone')) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $identifier);
        if ($digits === '') {
            return null;
        }

        $like = '%' . $digits . '%';

        return User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', '') LIKE ?",
                [$like]
            )
            ->first();
    }

    /**
     * Send reset code immediately via email and/or SMS log channel.
     *
     * @return array{sent: bool, channels: list<string>, error: ?string}
     */
    public static function deliverCode(User $user, string $code): array
    {
        $channels = [];
        $lastError = null;

        try {
            $user->notifyNow(new PasswordResetCodeNotification($code));
            $channels[] = 'email';
        } catch (\Throwable $e) {
            $lastError = $e->getMessage();
            Log::error('Password reset email failed: ' . $lastError, ['user_id' => $user->id]);
        }

        if (Schema::hasColumn('users', 'phone') && ! empty($user->phone)) {
            try {
                self::sendSmsCode($user->phone, $code);
                $channels[] = 'sms';
            } catch (\Throwable $e) {
                $lastError = $lastError ?: $e->getMessage();
                Log::warning('Password reset SMS failed: ' . $e->getMessage(), ['user_id' => $user->id]);
            }
        }

        if ($channels === [] && config('mail.default') === 'log') {
            Log::info('Password reset code (dev/log mailer)', [
                'email' => $user->email,
                'code'  => $code,
            ]);
            $channels[] = 'log';
        }

        return [
            'sent'      => $channels !== [],
            'channels'  => $channels,
            'error'     => $channels === [] ? $lastError : null,
        ];
    }

    private static function sendSmsCode(string $phone, string $code): void
    {
        $message = "SPUP Scheduling: Your password reset code is {$code}. It expires in 15 minutes.";
        Log::info('Password reset SMS', ['to' => $phone, 'body' => $message]);
    }
}
