<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_entries', function (Blueprint $table) {
            $table->string('geo_tag', 3)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('contact_entries', function (Blueprint $table) {
            $table->dropColumn('geo_tag');
        });
    }
};
