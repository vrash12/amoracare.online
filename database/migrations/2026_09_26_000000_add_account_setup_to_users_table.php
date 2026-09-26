<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('password_changed_at')->nullable();
            $table->string('terms_accepted_version', 50)->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['must_change_password', 'password_changed_at', 'terms_accepted_version', 'terms_accepted_at']);
        });
    }
};
