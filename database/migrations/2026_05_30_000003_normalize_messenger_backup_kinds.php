<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contact_entries')
            ->where('type', 'messenger')
            ->where('role', 'backup')
            ->whereNotNull('parent_id')
            ->orderBy('id')
            ->select(['id', 'parent_id', 'kind'])
            ->chunkById(100, function ($children): void {
                foreach ($children as $child) {
                    $parentKind = DB::table('contact_entries')
                        ->where('id', $child->parent_id)
                        ->where('type', 'messenger')
                        ->value('kind');

                    if ($parentKind && $child->kind !== $parentKind) {
                        DB::table('contact_entries')
                            ->where('id', $child->id)
                            ->update(['kind' => $parentKind]);
                    }
                }
            });
    }

    public function down(): void
    {
        // This normalizes inconsistent historical data and cannot be reversed safely.
    }
};
