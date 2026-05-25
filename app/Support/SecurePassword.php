<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Shared password rules for principal/admin-created accounts and resets.
 */
class SecurePassword
{
    /**
     * @return array<int, mixed>
     */
    public static function rules(bool $confirmed = true): array
    {
        $rule = Password::min(10)
            ->letters()
            ->mixedCase()
            ->numbers();

        $rules = ['required', 'string', $rule];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * @return array<int, mixed>
     */
    public static function optionalRules(): array
    {
        $rule = Password::min(10)
            ->letters()
            ->mixedCase()
            ->numbers();

        return ['nullable', 'string', $rule, 'confirmed'];
    }
}
