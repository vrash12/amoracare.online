<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Abort rather than merge or discard accounts with conflicting addresses.
        $collision = DB::table('users')->whereNull('deleted_at')
            ->selectRaw('LOWER(TRIM(email)) AS normalized_email, COUNT(*) AS total')
            ->groupByRaw('LOWER(TRIM(email))')->havingRaw('COUNT(*) > 1')->exists();
        if ($collision) {
            throw new RuntimeException('Existing accounts have conflicting normalized emails. Resolve these before migrating.');
        }

        if (! Schema::hasColumn('users', 'deleted_email')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('deleted_email', 255)->nullable());
        }

        DB::transaction(function (): void {
            DB::table('users')->whereNotNull('deleted_at')->whereNull('deleted_email')
                ->orderBy('id')->chunkById(100, function ($users): void {
                    foreach ($users as $user) {
                        DB::table('users')->where('id', $user->id)->update([
                            'deleted_email' => $user->email,
                            'email' => 'deleted-'.$user->id.'-'.Str::uuid().'@deleted.invalid',
                            'remember_token' => null,
                        ]);
                    }
                });
            DB::table('users')->whereNull('deleted_at')->update(['email' => DB::raw('LOWER(TRIM(email))')]);
        });
    }

    public function down(): void
    {
        // Retain the archive if an address has been reused; never overwrite a new account.
        DB::transaction(function (): void {
            $users = DB::table('users')->whereNotNull('deleted_email')->lockForUpdate()->get();
            foreach ($users as $user) {
                if (DB::table('users')->where('id', '!=', $user->id)
                    ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($user->deleted_email))])->exists()) {
                    throw new RuntimeException('Cannot roll back: a deleted email has been reused. Keep the archive column.');
                }
            }
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update(['email' => $user->deleted_email, 'deleted_email' => null]);
            }
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('deleted_email'));
    }
};
