<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait ReleasesDeletedEmail
{
    public static function bootReleasesDeletedEmail(): void
    {
        static::restoring(function ($user): void {
            if ($user->deleted_email === null) {
                return;
            }

            $email = strtolower(trim($user->deleted_email));
            if (static::withTrashed()->whereKeyNot($user->getKey())
                ->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'This email is already used by another account. The deleted account cannot be restored.',
                ]);
            }
            $user->email = $email;
            $user->deleted_email = null;
        });
    }

    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value) => strtolower(trim($value)));
    }

    protected function runSoftDelete()
    {
        // Release the unique email and delete the account in one atomic update.
        // Keeping the row and its ID preserves historical case and audit links.
        $time = $this->freshTimestamp();
        $columns = [
            $this->getDeletedAtColumn() => $this->fromDateTime($time),
            'deleted_email' => $this->deleted_email ?? $this->email,
            'email' => 'deleted-'.$this->getKey().'-'.Str::uuid().'@deleted.invalid',
            'remember_token' => null,
        ];
        if ($this->usesTimestamps() && $this->getUpdatedAtColumn() !== null) {
            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $this->setKeysForSaveQuery($this->newModelQuery())->update($columns);
        $this->forceFill($columns)->syncOriginalAttributes(array_keys($columns));
        $this->fireModelEvent('trashed', false);
    }
}
