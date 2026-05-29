<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Custom permission matrix override (null = use role defaults)
            $table->json('permissions')->nullable()->after('role');
            // Site access scope: 'all' = every site, 'limited' = only selected groups/sites
            $table->string('access_scope', 10)->default('all')->after('permissions');
            // Allowed site-group names when scope = limited
            $table->json('group_access')->nullable()->after('access_scope');
            // Allowed individual site ids when scope = limited
            $table->json('site_access')->nullable()->after('group_access');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['permissions', 'access_scope', 'group_access', 'site_access']);
        });
    }
};
