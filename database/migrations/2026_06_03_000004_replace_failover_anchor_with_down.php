<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Failover is a FIXED priority list (head = базовий/головний number, then reserves
 * by order). A number is either up or "down" — the served number is the
 * highest-priority one that is up, so the base reclaims its spot the moment it
 * recovers. Replaces the earlier role-swap model (failover_anchor_id), which
 * reshuffled the priority and lost the base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_entries', function (Blueprint $table) {
            $table->boolean('failover_down')->default(false)->after('parent_id');
        });

        // Undo earlier role-swap experiments: restore each group's original primary
        // (its anchor) as role=primary; everyone else becomes a reserve under it.
        if (Schema::hasColumn('contact_entries', 'failover_anchor_id')) {
            $anchorIds = DB::table('contact_entries')
                ->whereNotNull('failover_anchor_id')
                ->distinct()
                ->pluck('failover_anchor_id');

            foreach ($anchorIds as $anchorId) {
                if (! DB::table('contact_entries')->where('id', $anchorId)->exists()) {
                    continue;
                }
                DB::table('contact_entries')->where('id', $anchorId)
                    ->update(['role' => 'primary', 'parent_id' => null]);
                DB::table('contact_entries')
                    ->where('failover_anchor_id', $anchorId)
                    ->where('id', '!=', $anchorId)
                    ->update(['role' => 'backup', 'parent_id' => $anchorId]);
            }

            Schema::table('contact_entries', function (Blueprint $table) {
                $table->dropColumn('failover_anchor_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('contact_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('failover_anchor_id')->nullable()->after('parent_id');
            $table->dropColumn('failover_down');
        });
    }
};
