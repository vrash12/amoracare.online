<?php
// laravel-app/app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'phone_number',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

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
}