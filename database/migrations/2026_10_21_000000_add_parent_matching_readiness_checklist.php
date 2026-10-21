<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_matching_profiles', function (Blueprint $table) {
            // Existing profiles require staff confirmation; do not infer readiness.
            $table->boolean('physically_capable')->default(false);
            $table->boolean('financially_capable')->default(false);
            $table->boolean('willing_to_support_child')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('parent_matching_profiles', function (Blueprint $table) {
            $table->dropColumn(['physically_capable', 'financially_capable', 'willing_to_support_child']);
        });
    }
};
