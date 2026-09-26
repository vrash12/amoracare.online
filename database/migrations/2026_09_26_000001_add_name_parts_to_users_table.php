<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 70)->nullable();
            $table->string('middle_name', 70)->nullable();
            $table->string('last_name', 70)->nullable();
            $table->string('name_extension', 10)->nullable();
        });
        // Do not guess how to split existing names or rewrite historical records.
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'name_extension']);
        });
    }
};
