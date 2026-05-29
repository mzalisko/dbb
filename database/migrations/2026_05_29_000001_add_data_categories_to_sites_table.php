<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            // Enabled data categories shown in the Data tab.
            // null => default set (phones, messengers, prices). Phones + messengers are always required.
            $table->json('data_categories')->nullable()->after('geo_rules');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('data_categories');
        });
    }
};
