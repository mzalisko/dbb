<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers the *original* primary of a failover group. After a failover the
 * reserve takes the primary role, so role=primary alone can no longer tell you
 * which number was the canonical one — this anchor survives the swap and lets the
 * queue mark it with a dot. Null = this entry is its own anchor (never failed over).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('failover_anchor_id')->nullable()->after('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_entries', function (Blueprint $table) {
            $table->dropColumn('failover_anchor_id');
        });
    }
};
