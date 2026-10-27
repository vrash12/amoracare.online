<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class AvailableAccountEmail implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));
        if (str_ends_with($email, '@deleted.invalid')) {
            $fail('Please enter a usable email address.');

            return;
        }
        if (DB::table('users')->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->when($this->ignoreId !== null, fn ($query) => $query->where('id', '!=', $this->ignoreId))
            ->exists()) {
            $fail('An account with this email address already exists.');
        }
    }
}
