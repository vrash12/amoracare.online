<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonInterface;

class UserAccountStatusService
{
    public function inactivityDays(): int
    {
        return max(1, (int) config('accounts.inactivity_days', 60));
    }

    public function inactivityCutoff(): CarbonInterface
    {
        return now()->subDays($this->inactivityDays());
    }

    public function deactivateIfDormant(User $user): bool
    {
        if (! $user->hasExceededInactivityLimit($this->inactivityDays())) {
            return false;
        }

        $user->forceFill([
            'status' => User::STATUS_INACTIVE,
        ])->saveQuietly();

        return true;
    }

    public function deactivateDormantUsers(): int
    {
        $cutoff = $this->inactivityCutoff();

        return User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->where('created_at', '<=', $cutoff)
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('last_login_at')
                    ->orWhere('last_login_at', '<=', $cutoff);
            })
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('activated_at')
                    ->orWhere('activated_at', '<=', $cutoff);
            })
            ->update([
                'status' => User::STATUS_INACTIVE,
                'updated_at' => now(),
            ]);
    }
}
