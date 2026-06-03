<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A temporary access password lets an admin/manager sign in *as* a user without
 * touching that user's real password — the user keeps logging in with their own
 * credentials. Stored hashed, time-boxed; cleared the moment the real password
 * is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('temp_password')->nullable()->after('password');
            $table->timestamp('temp_password_expires_at')->nullable()->after('temp_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['temp_password', 'temp_password_expires_at']);
        });
    }
};
