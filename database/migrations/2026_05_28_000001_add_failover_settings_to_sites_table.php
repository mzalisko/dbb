<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->boolean('failover_enabled')->default(true)->after('notes');
            $table->string('failover_interval', 10)->default('5min')->after('failover_enabled');
            $table->tinyInteger('failover_threshold')->default(3)->after('failover_interval');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['failover_enabled', 'failover_interval', 'failover_threshold']);
        });
    }
};
