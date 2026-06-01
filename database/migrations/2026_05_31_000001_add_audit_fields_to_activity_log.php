<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // critic B4: semantic codes (domain.object.verb) are longer than 50.
            $table->string('action', 100)->change();
            // 0=info, 1=warn, 2=critical — real severity, not str_contains.
            $table->unsignedTinyInteger('severity')->default(0)->after('action');
            // groups all per-row work of one bulk operation under a single event.
            $table->uuid('batch_id')->nullable()->after('properties')->index();
            // web | console | system — where the mutation came from.
            $table->string('context', 20)->nullable()->after('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn(['severity', 'batch_id', 'context']);
            $table->string('action', 50)->change();
        });
    }
};
