<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_entries', function (Blueprint $table) {
            // Soft-delete so bulk delete is recoverable (Кошик + Undo). Sites already have this.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('contact_entries', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
