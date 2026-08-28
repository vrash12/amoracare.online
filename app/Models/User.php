<?php

// laravel-app/app/Models/User.php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'phone_number',
        'status',
        'last_login_at',
        'activated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'activated_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->status === self::STATUS_ACTIVE && ! $user->activated_at) {
                $user->activated_at = now();
            }
        });

        static::updating(function (User $user): void {
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            if (
                $user->isDirty('status')
                && $user->status === self::STATUS_ACTIVE
                && $user->getOriginal('status') !== self::STATUS_ACTIVE
            ) {
                $user->activated_at = now();
            }
        });

        static::updated(function (User $user): void {
            if ($user->wasChanged('email')) {
                $user->emailVerificationCode()->delete();
            }
        });
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => Str::of((string) $value)
                ->squish()
                ->lower()
                ->title()
                ->toString()
        );
    }

    public function inactivityReferenceAt(): ?CarbonInterface
    {
        return collect([
            $this->last_login_at,
            $this->activated_at,
            $this->created_at,
        ])
            ->filter()
            ->sortByDesc(fn (CarbonInterface $date) => $date->getTimestamp())
            ->first();
    }

    public function inactivityDeadline(?int $days = null): ?CarbonInterface
    {
        $reference = $this->inactivityReferenceAt();

        if (! $reference) {
            return null;
        }

        $inactivityDays = $days ?? max(1, (int) config('accounts.inactivity_days', 60));

        return $reference->copy()->addDays($inactivityDays);
    }

    public function hasExceededInactivityLimit(?int $days = null): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $deadline = $this->inactivityDeadline($days);

        return $deadline !== null && now()->greaterThanOrEqualTo($deadline);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->role?->slug === $roleSlug;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAdministrator(): bool
    {
        return $this->isAdmin();
    }

    public function isStaff(): bool
    {
        return $this->hasRole('staff') || $this->hasRole('social_worker');
    }

    public function isProspectiveParent(): bool
    {
        return $this->hasRole('prospective_parent');
    }

    public function isExternalReviewer(): bool
    {
        return $this->hasRole('external_reviewer');
    }

    public function adoptionCasesAsProspectiveParent(): HasMany
    {
        return $this->hasMany(AdoptionCase::class, 'prospective_parent_id');
    }

    public function assignedAdoptionCases(): HasMany
    {
        return $this->hasMany(AdoptionCase::class, 'assigned_social_worker_id');
    }

    public function createdAdoptionCases(): HasMany
    {
        return $this->hasMany(AdoptionCase::class, 'created_by');
    }

    public function matchingProfile(): HasOne
    {
        return $this->hasOne(ParentMatchingProfile::class);
    }

    public function emailVerificationCode(): HasOne
    {
        return $this->hasOne(EmailVerificationCode::class);
    }
}
