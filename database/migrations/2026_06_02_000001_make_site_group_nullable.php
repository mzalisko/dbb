<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `group`/`group_color` were NOT NULL, so ungrouping a site (set group = null)
     * — done when a group is deleted — threw an integrity error. null is the
     * canonical "no group" value (render filters on whereNotNull('group')), so
     * make the columns nullable. Defaults stay so new sites land in 'production'.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('group', 30)->nullable()->default('production')->change();
            $table->string('group_color', 10)->nullable()->default('#5a8a3c')->change();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('group', 30)->default('production')->change();
            $table->string('group_color', 10)->default('#5a8a3c')->change();
        });
    }
};
